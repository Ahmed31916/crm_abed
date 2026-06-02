<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LicenseKeyService
{
    /**
     * The base URL for the Vital.Manager API.
     * Configure in .env: VITAL_API_URL=https://your-api-domain.com
     */
    protected string $apiUrl;
    
    /**
     * The admin email for API authentication.
     * Configure in .env: VITAL_API_EMAIL=admin@example.com
     */
    protected string $apiEmail;
    
    /**
     * The admin password for API authentication.
     * Configure in .env: VITAL_API_PASSWORD=your-password
     */
    protected string $apiPassword;

    /**
     * The default product ID for license creation.
     * Configure in .env: VITAL_API_PRODUCT_ID=uuid-of-product
     */
    protected string $productId;

    public function __construct()
    {
        $this->apiUrl = rtrim(env('VITAL_API_URL', ''), '/');
        $this->apiEmail = env('VITAL_API_EMAIL', '');
        $this->apiPassword = env('VITAL_API_PASSWORD', '');
        $this->productId = env('VITAL_API_PRODUCT_ID', '');
    }

    /**
     * Get authentication token from the Vital.Manager API.
     * Caches the token for 50 minutes (tokens typically last 60 min).
     */
    protected function getAuthToken(): ?string
    {
        if (empty($this->apiUrl) || empty($this->apiEmail) || empty($this->apiPassword)) {
            return null;
        }

        // Check cache first
        $cachedToken = Cache::get('vital_api_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            $response = Http::timeout(15)->post($this->apiUrl . '/api/user/login', [
                'email' => $this->apiEmail,
                'password' => $this->apiPassword,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Try different possible token field names from the response
                $token = $data['token'] 
                    ?? $data['accessToken'] 
                    ?? $data['access_token'] 
                    ?? $data['data']['token'] 
                    ?? $data['data']['accessToken']
                    ?? null;

                if ($token) {
                    // Cache for 50 minutes
                    Cache::put('vital_api_token', $token, now()->addMinutes(50));
                    return $token;
                }

                Log::error('Vital API login: Token not found in response', [
                    'response_keys' => array_keys($data),
                ]);
                return null;
            }

            Log::error('Vital API login failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Vital API login exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate a license key for a user and plan using the Vital.Manager API.
     *
     * Swagger Endpoint: POST /api/licenses/create
     * 
     * @param User $user
     * @param int $planId
     * @param string $billingCycle 'monthly' or 'yearly'
     * @return array{success: bool, license_key?: string, license_id?: string, message?: string, data?: mixed}
     */
    public function generateLicenseKey(User $user, int $planId, string $billingCycle = 'monthly'): array
    {
        try {
            // If no API URL configured, generate local license key
            if (empty($this->apiUrl)) {
                return $this->generateLocalLicenseKey($user, $planId, $billingCycle);
            }

            $plan = Plan::find($planId);
            if (!$plan) {
                return [
                    'success' => false,
                    'message' => __('Plan not found'),
                ];
            }

            // Get auth token
            $token = $this->getAuthToken();
            if (!$token) {
                Log::warning('Vital API: No auth token, falling back to local generation');
                return $this->generateLocalLicenseKey($user, $planId, $billingCycle);
            }

            // Calculate expiration date based on billing cycle
            $expirationDate = $billingCycle === 'yearly'
                ? now()->addYear()->format('Y-m-d\TH:i:s\Z')
                : now()->addMonth()->format('Y-m-d\TH:i:s\Z');

            // Build the request payload matching CreateLicenseRequestDto
            $payload = [
                'issuedTo' => $user->company_name ?? $user->name,
                'licenseType' => 'Subscription',  // "Subscription" or "Trial"
                'expirationDate' => $expirationDate,
                'maxActiveUsersCount' => $plan->max_users ?? 1,
            ];

            // Add productId if configured
            if (!empty($this->productId)) {
                $payload['productId'] = $this->productId;
            }

            // Call POST /api/licenses/create
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl . '/api/licenses/create', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Extract license key from CreateLicenseResponseDto
                $licenseKey = $data['licenseKey'] ?? null;
                $licenseId = $data['licenseId'] ?? null;

                if ($licenseKey) {
                    // Save the license key to the user
                    $user->update([
                        'license_key' => $licenseKey,
                        'plan_is_active' => 1,
                        'plan_expire_date' => $expirationDate,
                    ]);

                    Log::info('License key generated via Vital API', [
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

                return [
                    'success' => false,
                    'message' => __('License key not found in API response'),
                    'data' => $data,
                ];
            }

            // If 401 Unauthorized, clear cached token and retry once
            if ($response->status() === 401) {
                Cache::forget('vital_api_token');
                Log::warning('Vital API token expired, retrying...');
                
                // Retry once with fresh token
                $newToken = $this->getAuthToken();
                if ($newToken && $newToken !== $token) {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $newToken,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(30)
                    ->post($this->apiUrl . '/api/licenses/create', $payload);

                    if ($response->successful()) {
                        $data = $response->json();
                        $licenseKey = $data['licenseKey'] ?? null;
                        $licenseId = $data['licenseId'] ?? null;

                        if ($licenseKey) {
                            $user->update([
                                'license_key' => $licenseKey,
                                'plan_is_active' => 1,
                                'plan_expire_date' => $expirationDate,
                            ]);

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
                    }
                }
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['title'] ?? __('Failed to generate license key');

            Log::error('Vital API license creation error', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'status' => $response->status(),
                'error' => $errorData,
            ]);

            // Fallback to local generation if API fails
            return $this->generateLocalLicenseKey($user, $planId, $billingCycle);

        } catch (\Exception $e) {
            Log::error('License key generation exception', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to local generation
            return $this->generateLocalLicenseKey($user, $planId, $billingCycle);
        }
    }

    /**
     * Activate a license on the Vital.Manager API.
     * Swagger Endpoint: POST /api/licenses/activate
     *
     * This is a PUBLIC endpoint (no auth required).
     *
     * @param string $hardwareId
     * @param string $licenseKey
     * @return array{success: bool, license_file_base64?: string, message?: string}
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

            // POST /api/licenses/activate (public endpoint - no auth needed)
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl . '/api/licenses/activate', $payload);

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
     * Swagger Endpoint: POST /api/licenses/validate
     *
     * This is a PUBLIC endpoint (no auth required).
     * Note: This endpoint uses multipart/form-data!
     *
     * @param string $licenseKey
     * @param string|null $hardwareId
     * @return array
     */
    public function validateLicenseKey(string $licenseKey, ?string $hardwareId = null): array
    {
        if (empty($this->apiUrl)) {
            // Local validation fallback
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
            // Note: Swagger says multipart/form-data for validate endpoint
            $multipart = [
                ['name' => 'licenseKey', 'contents' => $licenseKey],
            ];

            if ($hardwareId) {
                $multipart[] = ['name' => 'hardwareId', 'contents' => $hardwareId];
            }

            $response = Http::timeout(15)
                ->asMultipart()
                ->post($this->apiUrl . '/api/licenses/validate', $multipart);

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
     * Swagger Endpoint: POST /api/trial-requests
     *
     * This is a PUBLIC endpoint (no auth required).
     *
     * @param User $user
     * @return array
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

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl . '/api/trial-requests', $payload);

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

    /**
     * Generate a local license key as fallback.
     * Used when the Vital.Manager API is not configured or unreachable.
     */
    protected function generateLocalLicenseKey(User $user, int $planId, string $billingCycle = 'monthly'): array
    {
        $licenseKey = $this->createUniqueLicenseKey();
        $expireDate = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
        
        $user->update([
            'license_key' => $licenseKey,
            'plan_is_active' => 1,
            'plan_expire_date' => $expireDate,
        ]);

        return [
            'success' => true,
            'license_key' => $licenseKey,
            'message' => __('License key generated successfully'),
        ];
    }

    /**
     * Create a unique license key string.
     * Format: VTL-XXXX-XXXX-XXXX (Vital prefix for identification)
     */
    protected function createUniqueLicenseKey(): string
    {
        do {
            $segments = [];
            for ($i = 0; $i < 3; $i++) {
                $segments[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
            }
            $key = 'VTL-' . implode('-', $segments);
        } while (User::where('license_key', $key)->exists());

        return $key;
    }
}
