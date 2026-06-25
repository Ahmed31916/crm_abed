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
    // ===== SIMPLIFIED METHOD: validateAndImportLicense ==================
    // ====================================================================
    // تتحقق من الـ license key عبر Vital.Manager API وتُرجع معلومات الـ
    // license فقط. لا تقوم بتحديث أي سجل مستخدم - المتحكم هو من يقرر
    // كيف يستخدم هذه المعلومات عند بناء userData قبل User::create().
    //
    // السيناريو: مستخدم قديم على تطبيق الديسكتوب، لديه hardware_id
    // و license_key يعملان. يأتي لتسجيل حساب في الويب. نتحقق من
    // الـ license عبر API، وإذا كان صالحاً نُرجع معلوماته (license_id،
    // expiration_date، ...) فيستخدمها المتحكم لإنشاء المستخدم ببيانات
    // خطة فعّالة فيُحوَّل مباشرة للداشبورد بدلاً من /plans.
    // ====================================================================

    /**
     * Validate a license key via the Vital.Manager API and return its info.
     *
     * This method does NOT update any user record. It only validates and
     * returns the license information so the caller (controller) can use
     * it to populate the user record before User::create().
     *
     * @param  string      $licenseKey  The license key to validate
     * @param  string|null $hardwareId  Optional hardware id (for activation check)
     * @return array {
     *     success: bool,                // هل التحقق نجح overall
     *     source: 'api'|'database'|'none',
     *     license_id: string|null,      // UUID of the license (from API)
     *     expiration_date: string|null, // ISO date string from API
     *     is_trial: bool,               // هل الـ license من نوع Trial
     *     trial_expire_date: string|null,
     *     is_valid: bool,               // هل الـ license صالح وغير منتهي
     *     message: string,              // رسالة للمستخدم
     *     data: array                   // الـ response الأصلي من API
     * }
     */
    public function validateAndImportLicense(string $licenseKey, ?string $hardwareId = null): array
    {
        try {
            // 1) Basic sanity check
            if (empty($licenseKey)) {
                return [
                    'success'           => false,
                    'source'            => 'none',
                    'license_id'        => null,
                    'expiration_date'   => null,
                    'is_trial'          => false,
                    'trial_expire_date' => null,
                    'is_valid'          => false,
                    'message'           => __('License key is required.'),
                    'data'              => [],
                ];
            }

            // 2) Validate via API (or fall back to local DB lookup)
            $validation = $this->validateLicenseKey($licenseKey, $hardwareId);

            // 3) If API explicitly returned invalid → abort
            if (isset($validation['valid']) && $validation['valid'] === false) {
                Log::warning('LicenseKeyService@validateAndImportLicense: validation failed', [
                    'license_key' => $licenseKey,
                    'validation'  => $validation,
                ]);

                return [
                    'success'           => false,
                    'source'            => empty($this->apiUrl) ? 'database' : 'api',
                    'license_id'        => null,
                    'expiration_date'   => null,
                    'is_trial'          => false,
                    'trial_expire_date' => null,
                    'is_valid'          => false,
                    'message'           => $validation['message'] ?? __('License key is invalid or expired.'),
                    'data'              => $validation,
                ];
            }

            // 4) Extract information from the API response
            $licenseId = $validation['licenseId']
                ?? $validation['license_id']
                ?? $validation['id']
                ?? null;

            $expirationRaw = $validation['expirationDate']
                ?? $validation['expiration_date']
                ?? $validation['expiresAt']
                ?? null;

            $isTrial = isset($validation['licenseType'])
                ? ($validation['licenseType'] === 'Trial')
                : false;

            $trialExpireRaw = $validation['trialExpireDate']
                ?? $validation['trial_expire_date']
                ?? null;

            // 5) Determine if the license is currently valid (not expired)
            $isValid = true;
            if ($expirationRaw) {
                try {
                    $expirationDate = \Illuminate\Support\Carbon::parse($expirationRaw);
                    if ($expirationDate->isPast()) {
                        $isValid = false;
                    }
                } catch (\Exception $e) {
                    Log::warning('LicenseKeyService@validateAndImportLicense: could not parse expiration date', [
                        'raw'   => $expirationRaw,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 6) Optionally activate the license on the hardware (don't block on failure)
            if (!empty($hardwareId) && !empty($this->apiUrl)) {
                $this->activateLicense($hardwareId, $licenseKey);
            }

            Log::info('LicenseKeyService@validateAndImportLicense: license validated', [
                'license_key'   => $licenseKey,
                'license_id'    => $licenseId,
                'expiration'    => $expirationRaw,
                'is_trial'      => $isTrial,
                'is_valid'      => $isValid,
            ]);

            return [
                'success'           => true,
                'source'            => empty($this->apiUrl) ? 'database' : 'api',
                'license_id'        => $licenseId,
                'expiration_date'   => $expirationRaw,
                'is_trial'          => $isTrial,
                'trial_expire_date' => $trialExpireRaw,
                'is_valid'          => $isValid,
                'message'           => __('License key validated successfully.'),
                'data'              => $validation,
            ];

        } catch (\Exception $e) {
            Log::error('LicenseKeyService@validateAndImportLicense: exception', [
                'license_key' => $licenseKey,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return [
                'success'           => false,
                'source'            => 'none',
                'license_id'        => null,
                'expiration_date'   => null,
                'is_trial'          => false,
                'trial_expire_date' => null,
                'is_valid'          => false,
                'message'           => __('Failed to validate license: :error', ['error' => $e->getMessage()]),
                'data'              => [],
            ];
        }
    }
}
