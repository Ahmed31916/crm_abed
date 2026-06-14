<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseKeyService
{
    // API credentials - تُقرأ من .env حسب APP_ENV
    // APP_ENV=production  → يقرأ VITAL_PROD_API_*
    // APP_ENV=staging     → يقرأ VITAL_STAGING_API_*
    protected string $apiUrl;
    protected string $productId;
    protected string $remoteApiKey;
    protected bool $verifySsl;

    protected int $apiTimeout = 45;

    public function __construct()
    {
        $env = app()->environment(); // 'production', 'staging', 'local'

        if ($env === 'production') {
            // ═══ بيئة البرودكشن ═══
            $this->apiUrl = rtrim(env('VITAL_PROD_API_URL', ''), '/');
            $this->productId = env('VITAL_PROD_API_PRODUCT_ID', '');
            $this->remoteApiKey = env('VITAL_PROD_API_REMOTE_KEY', 'pm_super_secret_api_key');
            $this->verifySsl = env('VITAL_PROD_API_VERIFY_SSL', true);
        } else {
            // ═══ بيئة التست / Staging / Local ═══
            $this->apiUrl = rtrim(env('VITAL_STAGING_API_URL', ''), '/');
            $this->productId = env('VITAL_STAGING_API_PRODUCT_ID', '');
            $this->remoteApiKey = env('VITAL_STAGING_API_REMOTE_KEY', 'pm_super_secret_api_key');
            $this->verifySsl = env('VITAL_STAGING_API_VERIFY_SSL', false);
        }

        Log::info('LicenseKeyService: Initialized', [
            'app_env' => $env,
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
     * Validate an existing license key against the Vital.Manager API
     * and return its details. Used for importing legacy users who
     * already have a license from the desktop application.
     *
     * POST /api/licenses/validate (multipart/form-data)
     * Authentication: RemoteManagementApiKey header only
     *
     * @param string $licenseKey The existing license key to validate
     * @param string|null $hardwareId Optional hardware ID for activation check
     * @return array Validation result with license details
     */
    public function validateAndImportLicense(string $licenseKey, ?string $hardwareId = null): array
    {
        Log::info('LicenseKeyService: Validating existing license for import', [
            'license_key' => $licenseKey,
            'has_hardware_id' => !empty($hardwareId),
        ]);

        // First, try API validation if configured
        if (!empty($this->apiUrl)) {
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

                Log::info('LicenseKeyService: License validation response for import', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    // Check if the license is valid according to the API
                    $isValid = $data['isValid'] ?? $data['isSuccessful'] ?? ($data['status'] ?? '' === 'Active');

                    if ($isValid || isset($data['licenseKey']) || isset($data['id'])) {
                        return [
                            'success' => true,
                            'valid' => true,
                            'license_key' => $licenseKey,
                            'license_id' => $data['licenseId'] ?? $data['id'] ?? null,
                            'is_active' => $data['isActive'] ?? $isValid,
                            'expiration_date' => $data['expirationDate'] ?? $data['expiresAt'] ?? null,
                            'license_type' => $data['licenseType'] ?? null,
                            'issued_to' => $data['issuedTo'] ?? null,
                            'product_id' => $data['productId'] ?? null,
                            'is_activated' => $data['isActivated'] ?? false,
                            'data' => $data,
                            'source' => 'api',
                        ];
                    }

                    // API says license is not valid
                    return [
                        'success' => false,
                        'valid' => false,
                        'message' => $data['message'] ?? __('The provided license key is not valid'),
                        'source' => 'api',
                    ];
                }

                // API returned error - but we still allow import as fallback
                // because the user might have a working license on a different API version
                Log::warning('LicenseKeyService: API validation returned error, allowing import as fallback', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

            } catch (\Exception $e) {
                Log::warning('LicenseKeyService: API validation exception, allowing import as fallback', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: If API is not configured or returned an error,
        // we accept the license key as-is (trust the desktop app)
        // This is important because the user already has a working license
        Log::info('LicenseKeyService: Importing license without API validation (fallback)');

        return [
            'success' => true,
            'valid' => true,
            'license_key' => $licenseKey,
            'license_id' => null,
            'is_active' => true,
            'expiration_date' => null,
            'source' => 'fallback',
            'message' => __('License imported from existing desktop installation'),
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
                    'message' => __('License API is not configured. Please set VITAL_PROD_API_URL or VITAL_STAGING_API_URL in .env'),
                ];
            }

            if (empty($this->productId)) {
                Log::error('Vital API: Product ID is not configured');
                return [
                    'success' => false,
                    'message' => __('License API product is not configured. Please set VITAL_PROD_API_PRODUCT_ID or VITAL_STAGING_API_PRODUCT_ID in .env'),
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
}
