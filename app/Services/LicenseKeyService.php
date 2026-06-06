<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LicenseKeyService
{
    protected string $apiUrl;
    protected string $apiUsername;
    protected string $apiPassword;
    protected string $productId;
    protected string $remoteApiKey;
    protected int $loginTimeout = 45;
    protected int $apiTimeout = 45;
    protected int $loginRetries = 3;

    public function __construct()
    {
        $this->apiUrl = rtrim(env('VITAL_API_URL', ''), '/');
        $this->apiUsername = env('VITAL_API_USERNAME', env('VITAL_API_EMAIL', ''));
        $this->apiPassword = env('VITAL_API_PASSWORD', '');
        $this->productId = env('VITAL_API_PRODUCT_ID', '');
        $this->remoteApiKey = env('VITAL_API_REMOTE_KEY', 'pm_super_secret_api_key');

        Log::info('LicenseKeyService initialized', [
            'api_url' => $this->apiUrl,
            'username' => $this->apiUsername,
            'password_set' => !empty($this->apiPassword),
            'product_id_set' => !empty($this->productId),
            'remote_key' => $this->remoteApiKey,
        ]);
    }

    /**
     * Headers for PUBLIC endpoints (activate, validate, trial-requests).
     * These only need RemoteManagementApiKey - no Bearer token.
     */
    protected function getPublicHeaders(): array
    {
        return [
            'RemoteManagementApiKey' => $this->remoteApiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Headers for AUTHENTICATED endpoints (login, licenses/create).
     * For login: only basic headers (no token yet).
     * For licenses/create: Bearer token only - NO RemoteManagementApiKey.
     *
     * The Vital.Manager API may reject requests that combine both
     * RemoteManagementApiKey and Authorization Bearer headers.
     * Authenticated endpoints use Bearer token auth.
     * Public endpoints use RemoteManagementApiKey.
     */
    protected function getAuthHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Get authentication token from the Vital.Manager API.
     * Caches the token for 50 minutes (tokens typically last 60 min).
     *
     * Tries multiple login strategies because the API behavior can vary:
     * - Strategy A: RemoteManagementApiKey header + username field
     * - Strategy B: No special header + username field
     * - Strategy C: RemoteManagementApiKey header + email field
     * - Strategy D: No special header + email field
     */
    protected function getAuthToken(bool $forceFresh = false): ?string
    {
        if (empty($this->apiUrl) || empty($this->apiUsername) || empty($this->apiPassword)) {
            Log::error('Vital API: Missing configuration for login', [
                'api_url_empty' => empty($this->apiUrl),
                'username_empty' => empty($this->apiUsername),
                'password_empty' => empty($this->apiPassword),
            ]);
            return null;
        }

        // Check cache first (unless forceFresh)
        if (!$forceFresh) {
            $cachedToken = Cache::get('vital_api_token');
            if ($cachedToken) {
                Log::debug('Vital API: Using cached token');
                return $cachedToken;
            }
        }

        $loginUrl = $this->apiUrl . '/api/user/login';

        // Define multiple login strategies to try
        // The Vital API may accept different field/header combinations
        $strategies = [
            [
                'name' => 'A: RemoteManagementApiKey + username field',
                'headers' => $this->getPublicHeaders(),
                'payload' => ['username' => $this->apiUsername, 'password' => $this->apiPassword],
            ],
            [
                'name' => 'B: No special header + username field',
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'payload' => ['username' => $this->apiUsername, 'password' => $this->apiPassword],
            ],
            [
                'name' => 'C: RemoteManagementApiKey + email field',
                'headers' => $this->getPublicHeaders(),
                'payload' => ['email' => $this->apiUsername, 'password' => $this->apiPassword],
            ],
            [
                'name' => 'D: No special header + email field',
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'payload' => ['email' => $this->apiUsername, 'password' => $this->apiPassword],
            ],
            [
                'name' => 'E: RemoteManagementApiKey + both username and email',
                'headers' => $this->getPublicHeaders(),
                'payload' => ['username' => $this->apiUsername, 'email' => $this->apiUsername, 'password' => $this->apiPassword],
            ],
        ];

        // First, try each strategy once (fast path - find the one that works)
        foreach ($strategies as $strategy) {
            try {
                Log::info('Vital API: Trying login strategy', [
                    'strategy' => $strategy['name'],
                    'url' => $loginUrl,
                    'payload_keys' => array_keys($strategy['payload']),
                    'header_keys' => array_keys($strategy['headers']),
                ]);

                $response = Http::withHeaders($strategy['headers'])
                    ->timeout($this->loginTimeout)
                    ->connectTimeout(10)
                    ->post($loginUrl, $strategy['payload']);

                Log::info('Vital API login response', [
                    'strategy' => $strategy['name'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'body_length' => strlen($response->body()),
                    'headers' => $response->headers(),
                ]);

                if ($response->successful()) {
                    $token = $this->extractTokenFromResponse($response);
                    if ($token) {
                        Cache::put('vital_api_token', $token, now()->addMinutes(50));
                        Log::info('Vital API: Login successful', [
                            'strategy' => $strategy['name'],
                            'token_preview' => substr($token, 0, 20) . '...',
                        ]);
                        return $token;
                    }

                    Log::error('Vital API login: Token not found in successful response', [
                        'strategy' => $strategy['name'],
                        'response_keys' => array_keys($response->json()),
                    ]);
                    continue;
                }

                // If 401, try next strategy - maybe this API version uses different fields
                if ($response->status() === 401) {
                    Log::info('Vital API: Strategy returned 401, trying next strategy...');
                    continue;
                }

                // For other errors (500, etc.), also try next strategy
                Log::warning('Vital API: Login got non-401 error, trying next strategy', [
                    'status' => $response->status(),
                ]);
                continue;

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Vital API login connection error', [
                    'strategy' => $strategy['name'],
                    'error' => $e->getMessage(),
                ]);
                continue;
            } catch (\Exception $e) {
                Log::error('Vital API login exception', [
                    'strategy' => $strategy['name'],
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::error('Vital API: ALL login strategies failed', [
            'strategies_tried' => count($strategies),
            'username' => $this->apiUsername,
            'url' => $loginUrl,
        ]);
        return null;
    }

    /**
     * Extract token from a successful login response.
     * Handles various response formats from the Vital.Manager API.
     */
    protected function extractTokenFromResponse($response): ?string
    {
        $data = $response->json();

        $token = $data['token']
            ?? $data['accessToken']
            ?? $data['access_token']
            ?? $data['data']['token']
            ?? $data['data']['accessToken']
            ?? $data['data']['access_token']
            ?? null;

        return $token;
    }

    /**
     * Generate a license key for a user and plan using the Vital.Manager API.
     *
     * POST /api/licenses/create (authenticated - Bearer token only)
     */
    public function generateLicenseKey(User $user, int $planId, string $billingCycle = 'monthly'): array
    {
        try {
            if (empty($this->apiUrl)) {
                Log::error('Vital API: VITAL_API_URL is not configured in .env');
                return [
                    'success' => false,
                    'message' => __('License API is not configured. Please set VITAL_API_URL in .env'),
                ];
            }

            if (empty($this->productId)) {
                Log::error('Vital API: VITAL_API_PRODUCT_ID is not configured in .env. The API requires a productId to create a license.');
                return [
                    'success' => false,
                    'message' => __('License API product is not configured. Please set VITAL_API_PRODUCT_ID in .env'),
                ];
            }

            // Validate productId is a valid UUID/GUID format
            $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
            if (!preg_match($uuidPattern, $this->productId)) {
                Log::error('Vital API: VITAL_API_PRODUCT_ID is not a valid UUID/GUID format', [
                    'product_id' => $this->productId,
                    'expected_format' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                ]);
                return [
                    'success' => false,
                    'message' => __('VITAL_API_PRODUCT_ID must be a valid UUID format (e.g. 123e4567-e89b-12d3-a456-426614174000). Current value: :value', ['value' => $this->productId]),
                ];
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
                Log::error('Vital API: Could not obtain auth token');
                return [
                    'success' => false,
                    'message' => __('Could not authenticate with the license server. Please check API credentials.'),
                ];
            }

            // Calculate expiration date based on billing cycle
            $expirationDate = $billingCycle === 'yearly'
                ? now()->addYear()->format('Y-m-d\TH:i:s\Z')
                : now()->addMonth()->format('Y-m-d\TH:i:s\Z');

            // Build the request payload matching CreateLicenseRequestDto
            // productId is REQUIRED by the Vital.Manager API
            $payload = [
                'productId' => $this->productId,
                'issuedTo' => $user->company_name ?? $user->name,
                'licenseType' => 'Subscription',
                'expirationDate' => $expirationDate,
                'maxActiveUsersCount' => $plan->max_users ?? 1,
            ];

            // ====== ATTEMPT 1: Bearer token ONLY (no RemoteManagementApiKey) ======
            // Authenticated endpoints should use only the Bearer token.
            // RemoteManagementApiKey may conflict with Bearer auth.
            $headers = $this->getAuthHeaders($token);

            Log::info('Vital API: Creating license (Attempt 1: Bearer only)', [
                'url' => $this->apiUrl . '/api/licenses/create',
                'payload' => $payload,
                'headers' => array_keys($headers),
            ]);

            try {
                $response = Http::withHeaders($headers)
                    ->timeout($this->apiTimeout)
                    ->connectTimeout(10)
                    ->post($this->apiUrl . '/api/licenses/create', $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Vital API: Connection error on license creation (Attempt 1)', [
                    'error' => $e->getMessage(),
                ]);
                return [
                    'success' => false,
                    'message' => __('Could not connect to the license server. The server may be temporarily unavailable. Error: :error', ['error' => $e->getMessage()]),
                ];
            }

            Log::info('Vital API: License creation response (Attempt 1)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // If Bearer-only works, process the result
            if ($response->successful()) {
                return $this->processLicenseResponse($response, $user, $planId, $expirationDate);
            }

            // If 401 with Bearer-only, try WITH RemoteManagementApiKey
            if ($response->status() === 401) {
                Log::info('Vital API: Bearer-only got 401, trying with RemoteManagementApiKey...');

                // ====== ATTEMPT 2: Bearer + RemoteManagementApiKey ======
                $headers2 = array_merge($this->getAuthHeaders($token), [
                    'RemoteManagementApiKey' => $this->remoteApiKey,
                ]);

                Log::info('Vital API: Creating license (Attempt 2: Bearer + RemoteKey)', [
                    'url' => $this->apiUrl . '/api/licenses/create',
                    'payload' => $payload,
                    'headers' => array_keys($headers2),
                ]);

                try {
                    $response2 = Http::withHeaders($headers2)
                        ->timeout($this->apiTimeout)
                        ->connectTimeout(10)
                        ->post($this->apiUrl . '/api/licenses/create', $payload);
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::error('Vital API: Connection error on license creation (Attempt 2)', [
                        'error' => $e->getMessage(),
                    ]);
                }

                if (isset($response2)) {
                    Log::info('Vital API: License creation response (Attempt 2)', [
                        'status' => $response2->status(),
                        'body' => $response2->body(),
                    ]);

                    if ($response2->successful()) {
                        return $this->processLicenseResponse($response2, $user, $planId, $expirationDate);
                    }
                }

                // ====== ATTEMPT 3: Fresh login + Bearer-only ======
                // Maybe the token was invalidated between login and this call
                Log::info('Vital API: Attempt 2 also failed, trying fresh login...');
                Cache::forget('vital_api_token');
                $freshToken = $this->getAuthToken(true);

                if ($freshToken) {
                    $headers3 = $this->getAuthHeaders($freshToken);

                    Log::info('Vital API: Creating license (Attempt 3: Fresh Bearer only)', [
                        'url' => $this->apiUrl . '/api/licenses/create',
                        'payload' => $payload,
                        'headers' => array_keys($headers3),
                    ]);

                    try {
                        $response3 = Http::withHeaders($headers3)
                            ->timeout($this->apiTimeout)
                            ->connectTimeout(10)
                            ->post($this->apiUrl . '/api/licenses/create', $payload);
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        Log::error('Vital API: Connection error on license creation (Attempt 3)', [
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if (isset($response3)) {
                        Log::info('Vital API: License creation response (Attempt 3)', [
                            'status' => $response3->status(),
                            'body' => $response3->body(),
                        ]);

                        if ($response3->successful()) {
                            return $this->processLicenseResponse($response3, $user, $planId, $expirationDate);
                        }
                    }

                    // ====== ATTEMPT 4: Fresh token + Bearer + RemoteManagementApiKey ======
                    $headers4 = array_merge($this->getAuthHeaders($freshToken), [
                        'RemoteManagementApiKey' => $this->remoteApiKey,
                    ]);

                    Log::info('Vital API: Creating license (Attempt 4: Fresh Bearer + RemoteKey)', [
                        'url' => $this->apiUrl . '/api/licenses/create',
                        'payload' => $payload,
                        'headers' => array_keys($headers4),
                    ]);

                    try {
                        $response4 = Http::withHeaders($headers4)
                            ->timeout($this->apiTimeout)
                            ->connectTimeout(10)
                            ->post($this->apiUrl . '/api/licenses/create', $payload);
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        Log::error('Vital API: Connection error on license creation (Attempt 4)', [
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if (isset($response4)) {
                        Log::info('Vital API: License creation response (Attempt 4)', [
                            'status' => $response4->status(),
                            'body' => $response4->body(),
                        ]);

                        if ($response4->successful()) {
                            return $this->processLicenseResponse($response4, $user, $planId, $expirationDate);
                        }
                    }
                }

                // All attempts failed
                Log::error('Vital API: ALL license creation attempts failed (4 attempts)', [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                ]);

                return [
                    'success' => false,
                    'message' => __('Failed to generate license key via API after multiple attempts. Check logs for details.'),
                ];
            }

            // Non-401 error (e.g., 400, 422, 500)
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
     * Extracts license key/id, saves to user, and optionally activates.
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
                'saved_license_key' => $user->fresh()->license_key,
                'saved_license_id' => $user->fresh()->license_id,
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
     * POST /api/licenses/activate (PUBLIC endpoint - RemoteManagementApiKey only)
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

            // Public endpoint: only RemoteManagementApiKey needed
            $response = Http::withHeaders($this->getPublicHeaders())
                ->timeout($this->apiTimeout)
                ->connectTimeout(10)
                ->post($this->apiUrl . '/api/licenses/activate', $payload);

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
     * POST /api/licenses/validate (PUBLIC endpoint, multipart/form-data)
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

            // Public endpoint: only RemoteManagementApiKey needed
            $response = Http::withHeaders([
                    'RemoteManagementApiKey' => $this->remoteApiKey,
                ])
                ->timeout($this->apiTimeout)
                ->connectTimeout(10)
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
     * POST /api/trial-requests (PUBLIC endpoint)
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

            // Public endpoint: only RemoteManagementApiKey needed
            $response = Http::withHeaders($this->getPublicHeaders())
                ->timeout($this->apiTimeout)
                ->connectTimeout(10)
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
}
