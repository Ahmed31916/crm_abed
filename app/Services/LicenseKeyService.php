<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseKeyService
{
    // Production-only credentials
    protected string $apiUrl;
    protected string $productId;
    protected string $remoteApiKey;
    protected bool $verifySsl;

    protected int $apiTimeout = 45;

    public function __construct()
    {
        // Load PRODUCTION credentials only
        $this->apiUrl = rtrim(env('VITAL_PROD_API_URL', ''), '/');
        $this->productId = env('VITAL_PROD_API_PRODUCT_ID', '');
        $this->remoteApiKey = env('VITAL_PROD_API_REMOTE_KEY', 'pm_super_secret_api_key');
        $this->verifySsl = env('VITAL_PROD_API_VERIFY_SSL', true);

        Log::info('LicenseKeyService: Initialized (production only)', [
            'api_url' => $this->apiUrl,
            'product_id_set' => !empty($this->productId),
            'remote_key_set' => !empty($this->remoteApiKey),
            'verify_ssl' => $this->verifySsl,
        ]);
    }

    /**
     * Apply SSL verification settings to an HTTP request.
     */
    protected function applySslSettings($request)
    {
        if (!$this->verifySsl) {
            return $request->withOptions(['verify' => false]);
        }
        return $request;
    }

    /**
     * Get the standard headers for all Vital.Manager API requests.
     * Uses only RemoteManagementApiKey — no username/password/login required.
     */
    protected function getApiHeaders(): array
    {
        return [
            'RemoteManagementApiKey' => $this->remoteApiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Generate a license key for a user and plan using the Vital.Manager API.
     *
     * POST /api/licenses/create
     * Authentication: RemoteManagementApiKey header only
     */
    public function generateLicenseKey(User $user, int $planId, string $billingCycle = 'monthly'): array
    {
        try {
            if (empty($this->apiUrl)) {
                Log::error('Vital API: API URL is not configured');
                return [
                    'success' => false,
                    'message' => __('License API is not configured. Please set VITAL_PROD_API_URL in .env'),
                ];
            }

            if (empty($this->productId)) {
                Log::error('Vital API: Product ID is not configured');
                return [
                    'success' => false,
                    'message' => __('License API product is not configured. Please set VITAL_PROD_API_PRODUCT_ID in .env'),
                ];
            }

            // Validate productId is a valid UUID/GUID format
            $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
            if (!preg_match($uuidPattern, $this->productId)) {
                Log::error('Vital API: Product ID is not a valid UUID/GUID format', [
                    'product_id' => $this->productId,
                ]);
                return [
                    'success' => false,
                    'message' => __('Product ID must be a valid UUID format. Current value: :value', [
                        'value' => $this->productId,
                    ]),
                ];
            }

            $plan = Plan::find($planId);
            if (!$plan) {
                return [
                    'success' => false,
                    'message' => __('Plan not found'),
                ];
            }

            // Calculate expiration date based on billing cycle
            $expirationDate = $billingCycle === 'yearly'
                ? now()->addYear()->format('Y-m-d\TH:i:s\Z')
                : now()->addMonth()->format('Y-m-d\TH:i:s\Z');

            $payload = [
                'productId' => $this->productId,
                'issuedTo' => $user->company_name ?? $user->name,
                'licenseType' => 'Subscription',
                'expirationDate' => $expirationDate,
                'maxActiveUsersCount' => 1,
            ];

            Log::info('Vital API: Creating license', [
                'url' => $this->apiUrl . '/api/licenses/create',
                'payload' => $payload,
            ]);

            try {
                $request = Http::withHeaders($this->getApiHeaders())
                    ->timeout($this->apiTimeout)
                    ->connectTimeout(10);
                $request = $this->applySslSettings($request);
                $response = $request->post($this->apiUrl . '/api/licenses/create', $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Vital API: Connection error during license creation', [
                    'error' => $e->getMessage(),
                ]);
                return [
                    'success' => false,
                    'message' => __('Could not connect to the license server. Error: :error', [
                        'error' => $e->getMessage(),
                    ]),
                ];
            }

            Log::info('Vital API: License creation response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return $this->processLicenseResponse($response, $user, $planId, $expirationDate);
            }

            // Handle error response
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['title'] ?? __('Failed to generate license key via API');

            Log::error('Vital API license creation error', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'status' => $response->status(),
                'error' => $errorData,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'api_status' => $response->status(),
                'api_error' => $errorData,
            ];

        } catch (\Exception $e) {
            Log::error('License key generation exception', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('License server error: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Process a successful license creation response.
     */
    protected function processLicenseResponse($response, User $user, int $planId, string $expirationDate): array
    {
        $data = $response->json();

        $licenseKey = $data['licenseKey'] ?? null;
        $licenseId = $data['licenseId'] ?? $data['id'] ?? null;

        if ($licenseKey) {
            $user->update([
                'license_key' => $licenseKey,
                'license_id' => $licenseId,
                'plan_is_active' => 1,
                'plan_expire_date' => $expirationDate,
            ]);

            Log::info('License key generated and SAVED via Vital API', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'license_id' => $licenseId,
                'license_key' => $licenseKey,
            ]);

            // If user has hardware_id, also activate the license
            if ($user->hardware_id && $licenseKey) {
                $this->activateLicense($user->hardware_id, $licenseKey);
            }

            return [
                'success' => true,
                'license_key' => $licenseKey,
                'license_id' => $licenseId,
                'message' => __('License key generated successfully'),
                'data' => $data,
            ];
        }

        Log::error('Vital API: License key not found in successful response', [
            'response_data' => $data,
        ]);

        return [
            'success' => false,
            'message' => __('License key not found in API response'),
            'data' => $data,
        ];
    }

    /**
     * Activate a license on the Vital.Manager API.
     *
     * POST /api/licenses/activate
     * Authentication: RemoteManagementApiKey header only
     */
    public function activateLicense(string $hardwareId, string $licenseKey): array
    {
        if (empty($this->apiUrl)) {
            return [
                'success' => false,
                'message' => __('API not configured'),
            ];
        }

        try {
            $payload = [
                'hardwareId' => $hardwareId,
                'licenseKey' => $licenseKey,
            ];

            $request = Http::withHeaders($this->getApiHeaders())
                ->timeout($this->apiTimeout)
                ->connectTimeout(10);
            $request = $this->applySslSettings($request);
            $response = $request->post($this->apiUrl . '/api/licenses/activate', $payload);

            Log::info('Vital API: License activation response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('License activated via Vital API', [
                    'hardware_id' => $hardwareId,
                    'is_successful' => $data['isSuccessful'] ?? false,
                ]);

                return [
                    'success' => $data['isSuccessful'] ?? false,
                    'license_file_base64' => $data['licenseFileBase64'] ?? null,
                    'message' => $data['message'] ?? __('License activation processed'),
                ];
            }

            Log::error('Vital API license activation error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => __('License activation failed'),
            ];

        } catch (\Exception $e) {
            Log::error('License activation exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Unable to activate license'),
            ];
        }
    }

    /**
     * Validate a license key against the Vital.Manager API.
     *
     * POST /api/licenses/validate (multipart/form-data)
     * Authentication: RemoteManagementApiKey header only
     */
    public function validateLicenseKey(string $licenseKey, ?string $hardwareId = null): array
    {
        if (empty($this->apiUrl)) {
            $user = User::where('license_key', $licenseKey)->first();

            if ($user && $user->plan_is_active) {
                return [
                    'valid' => true,
                    'message' => __('License key is valid'),
                ];
            }

            return [
                'valid' => false,
                'message' => __('Invalid license key'),
            ];
        }

        try {
            $multipart = [
                ['name' => 'licenseKey', 'contents' => $licenseKey],
            ];

            if ($hardwareId) {
                $multipart[] = ['name' => 'hardwareId', 'contents' => $hardwareId];
            }

            $request = Http::withHeaders([
                    'RemoteManagementApiKey' => $this->remoteApiKey,
                ])
                ->timeout($this->apiTimeout)
                ->connectTimeout(10)
                ->asMultipart();
            $request = $this->applySslSettings($request);
            $response = $request->post($this->apiUrl . '/api/licenses/validate', $multipart);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'valid' => false,
                'message' => __('License validation failed'),
            ];

        } catch (\Exception $e) {
            Log::error('License validation exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'message' => __('Unable to validate license key'),
            ];
        }
    }

    /**
     * Submit a trial license request via the Vital.Manager API.
     *
     * POST /api/trial-requests
     * Authentication: RemoteManagementApiKey header only
     */
    public function submitTrialRequest(User $user): array
    {
        if (empty($this->apiUrl)) {
            return [
                'success' => false,
                'message' => __('API not configured'),
            ];
        }

        try {
            $payload = [
                'userName' => $user->name,
                'email' => $user->email,
                'company' => $user->company_name,
                'phoneNumber' => $user->phone,
                'country' => $user->country?->name,
                'hardwareId' => $user->hardware_id,
            ];

            $request = Http::withHeaders($this->getApiHeaders())
                ->timeout($this->apiTimeout)
                ->connectTimeout(10);
            $request = $this->applySslSettings($request);
            $response = $request->post($this->apiUrl . '/api/trial-requests', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Trial request submitted via Vital API', [
                    'user_id' => $user->id,
                    'request_id' => $data['requestId'] ?? null,
                ]);

                return [
                    'success' => true,
                    'request_id' => $data['requestId'] ?? null,
                    'message' => $data['message'] ?? __('Trial request submitted successfully'),
                    'data' => $data,
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'message' => $errorData['message'] ?? __('Failed to submit trial request'),
            ];

        } catch (\Exception $e) {
            Log::error('Trial request submission exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Unable to submit trial request'),
            ];
        }
    }

    // ====================================================================
    // ===== NEW METHOD: validateAndImportLicense =========================
    // ====================================================================
    // تُستخدم هذه الدالة عند تسجيل مستخدم "قديم" (legacy) قام بإدخال license
    // key في تطبيق الديسكتوب. تتحقق من المفتاح عبر Vital.Manager API، ثم
    // تُورّد بيانات الخطة إلى سجل المستخدم (license_id, expiration_date,
    // plan_is_active, ...) فلا يحتاج للمرور بصفحة /plans.
    //
    // Use cases:
    //   1. من داخل RegisteredUserController@store بعد إنشاء المستخدم إن
    //      كان قد أتى من desktop app ومعه license_key.
    //   2. من أي مكان آخر يحتاج فيه استيراد بيانات خطة من مفتاح موجود.
    // ====================================================================

    /**
     * Validate a license key against the Vital.Manager API and import its
     * data (license_id, expiration_date, plan_is_active, ...) into the
     * given user's record.
     *
     * @param User   $user        The user to import the license into
     * @param string $licenseKey  The license key to validate & import
     * @param string|null $hardwareId  Optional hardware id (for activation)
     * @return array { success: bool, message: string, data?: array }
     */
    public function validateAndImportLicense(User $user, string $licenseKey, ?string $hardwareId = null): array
    {
        try {
            // 1) Basic sanity checks
            if (empty($licenseKey)) {
                return [
                    'success' => false,
                    'message' => __('License key is required.'),
                ];
            }

            // 2) Validate the license key against the API
            $validation = $this->validateLicenseKey($licenseKey, $hardwareId);

            // If API is configured and the validation returns a definite false, abort
            // (validateLicenseKey returns ['valid' => false, ...] when invalid)
            if (isset($validation['valid']) && $validation['valid'] === false) {
                Log::warning('LicenseKeyService@validateAndImportLicense: validation failed', [
                    'user_id' => $user->id,
                    'license_key' => $licenseKey,
                    'validation' => $validation,
                ]);

                return [
                    'success' => false,
                    'message' => $validation['message'] ?? __('License key is invalid or expired.'),
                    'validation' => $validation,
                ];
            }

            // 3) Build the update payload
            $updateData = [
                'license_key' => $licenseKey,
                'plan_is_active' => 1,
            ];

            // Import license_id if provided by the API
            $licenseId = $validation['licenseId']
                ?? $validation['license_id']
                ?? $validation['id']
                ?? null;

            if ($licenseId) {
                $updateData['license_id'] = $licenseId;
            }

            // Import expiration date if provided by the API
            $expirationRaw = $validation['expirationDate']
                ?? $validation['expiration_date']
                ?? $validation['expiresAt']
                ?? null;

            if ($expirationRaw) {
                try {
                    $expirationDate = \Illuminate\Support\Carbon::parse($expirationRaw);
                    $updateData['plan_expire_date'] = $expirationDate;

                    // If the expiration is in the past, mark the plan as inactive
                    if ($expirationDate->isPast()) {
                        $updateData['plan_is_active'] = 0;
                    }
                } catch (\Exception $e) {
                    Log::warning('LicenseKeyService@validateAndImportLicense: could not parse expiration date', [
                        'raw' => $expirationRaw,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Import trial status if provided
            if (isset($validation['licenseType'])) {
                $updateData['is_trial'] = ($validation['licenseType'] === 'Trial') ? 1 : 0;
            }
            if (isset($validation['trialExpireDate']) || isset($validation['trial_expire_date'])) {
                $trialExpireRaw = $validation['trialExpireDate'] ?? $validation['trial_expire_date'];
                try {
                    $updateData['trial_expire_date'] = \Illuminate\Support\Carbon::parse($trialExpireRaw);
                } catch (\Exception $e) {
                    // ignore parse failures for trial date
                }
            }

            // Save hardware_id if provided
            if ($hardwareId) {
                $updateData['hardware_id'] = $hardwareId;
            }

            // 4) Persist to the user record
            $user->update($updateData);

            Log::info('LicenseKeyService@validateAndImportLicense: license imported successfully', [
                'user_id' => $user->id,
                'license_id' => $licenseId,
                'expiration' => $expirationRaw,
                'is_trial' => $updateData['is_trial'] ?? null,
            ]);

            // 5) Optionally: activate the license on the hardware if we have both
            if (!empty($hardwareId) && !empty($licenseKey) && !empty($this->apiUrl)) {
                $this->activateLicense($hardwareId, $licenseKey);
            }

            return [
                'success'   => true,
                'message'   => __('License key validated and imported successfully.'),
                'license_id'=> $licenseId,
                'expiration'=> $expirationRaw,
                'data'      => $validation,
            ];

        } catch (\Exception $e) {
            Log::error('LicenseKeyService@validateAndImportLicense: exception', [
                'user_id'    => $user->id,
                'license_key'=> $licenseKey,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to import license: :error', ['error' => $e->getMessage()]),
            ];
        }
    }
}
