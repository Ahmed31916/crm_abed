<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LicenseKeyService
{
    protected string $environment; // 'test' or 'production'

    // Test environment credentials
    protected string $testApiUrl;
    protected string $testApiUsername;
    protected string $testApiPassword;
    protected string $testProductId;
    protected string $testRemoteApiKey;
    protected bool $testVerifySsl;

    // Production environment credentials
    protected string $prodApiUrl;
    protected string $prodApiUsername;
    protected string $prodApiPassword;
    protected string $prodProductId;
    protected string $prodRemoteApiKey;
    protected bool $prodVerifySsl;

    // Active credentials (resolved based on environment)
    protected string $apiUrl;
    protected string $apiUsername;
    protected string $apiPassword;
    protected string $productId;
    protected string $remoteApiKey;
    protected bool $verifySsl;

    protected int $loginTimeout = 45;
    protected int $apiTimeout = 45;

    public function __construct(string $environment = 'production')
    {
        // Load TEST credentials
        $this->testApiUrl = rtrim(env('VITAL_TEST_API_URL', env('VITAL_API_URL', '')), '/');
        $this->testApiUsername = env('VITAL_TEST_API_USERNAME', env('VITAL_API_USERNAME', env('VITAL_API_EMAIL', '')));
        $this->testApiPassword = env('VITAL_TEST_API_PASSWORD', env('VITAL_API_PASSWORD', ''));
        $this->testProductId = env('VITAL_TEST_API_PRODUCT_ID', env('VITAL_API_PRODUCT_ID', ''));
        $this->testRemoteApiKey = env('VITAL_TEST_API_REMOTE_KEY', env('VITAL_API_REMOTE_KEY', 'pm_super_secret_api_key'));
        $this->testVerifySsl = env('VITAL_TEST_API_VERIFY_SSL', env('VITAL_API_VERIFY_SSL', true));

        // Load PRODUCTION credentials
        $this->prodApiUrl = rtrim(env('VITAL_PROD_API_URL', ''), '/');
        $this->prodApiUsername = env('VITAL_PROD_API_USERNAME', '');
        $this->prodApiPassword = env('VITAL_PROD_API_PASSWORD', '');
        $this->prodProductId = env('VITAL_PROD_API_PRODUCT_ID', '');
        $this->prodRemoteApiKey = env('VITAL_PROD_API_REMOTE_KEY', 'pm_super_secret_api_key');
        $this->prodVerifySsl = env('VITAL_PROD_API_VERIFY_SSL', true);

        // Set the environment (defaults to production - only explicit 'test' uses test API)
        $this->setEnvironment($environment);
    }

    /**
     * Set the API environment and resolve active credentials.
     * 'test' = test.vitalexperts.co (when desktop app sends env=test)
     * 'production' = production API (when no env parameter is sent)
     */
    public function setEnvironment(string $environment): self
    {
        // Default to 'production' for any invalid/empty value
        // Business rule: no env parameter = production (only explicit 'test' = test)
        $this->environment = $environment === 'test' ? 'test' : 'production';

        if ($this->environment === 'production') {
            $this->apiUrl = $this->prodApiUrl;
            $this->apiUsername = $this->prodApiUsername;
            $this->apiPassword = $this->prodApiPassword;
            $this->productId = $this->prodProductId;
            $this->remoteApiKey = $this->prodRemoteApiKey;
            $this->verifySsl = $this->prodVerifySsl;
        } else {
            $this->apiUrl = $this->testApiUrl;
            $this->apiUsername = $this->testApiUsername;
            $this->apiPassword = $this->testApiPassword;
            $this->productId = $this->testProductId;
            $this->remoteApiKey = $this->testRemoteApiKey;
            $this->verifySsl = $this->testVerifySsl;
        }

        Log::info('LicenseKeyService: Environment set', [
            'environment' => $this->environment,
            'api_url' => $this->apiUrl,
            'username' => $this->apiUsername,
            'password_set' => !empty($this->apiPassword),
            'product_id_set' => !empty($this->productId),
            'remote_key' => $this->remoteApiKey,
            'verify_ssl' => $this->verifySsl,
        ]);

        return $this;
    }

    /**
     * Get the current environment.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Create a new instance for a specific environment.
     */
    public static function forEnvironment(string $environment): self
    {
        return new self($environment);
    }

    /**
     * Resolve environment from a request parameter.
     * If env=test is present, return 'test'. Otherwise, return 'production'.
     */
    public static function resolveEnvironmentFromRequest($request): string
    {
        $env = $request->input('env', $request->query('env', ''));

        if (strtolower(trim($env)) === 'test') {
            return 'test';
        }

        return 'production';
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
     * Get the cache key for the current environment's auth token.
     */
    protected function getTokenCacheKey(): string
    {
        return 'vital_api_token_' . $this->environment;
    }

    protected function getPublicHeaders(): array
    {
        return [
            'RemoteManagementApiKey' => $this->remoteApiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

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
     * Caches the token per environment for 50 minutes.
     */
    protected function getAuthToken(bool $forceFresh = false): ?string
    {
        if (empty($this->apiUrl) || empty($this->apiUsername) || empty($this->apiPassword)) {
            Log::error('Vital API: Missing configuration for login', [
                'environment' => $this->environment,
                'api_url_empty' => empty($this->apiUrl),
                'username_empty' => empty($this->apiUsername),
                'password_empty' => empty($this->apiPassword),
            ]);
            return null;
        }

        $cacheKey = $this->getTokenCacheKey();

        // Check cache first (unless forceFresh)
        if (!$forceFresh) {
            $cachedToken = Cache::get($cacheKey);
            if ($cachedToken) {
                Log::debug('Vital API: Using cached token', ['environment' => $this->environment]);
                return $cachedToken;
            }
        }

        $loginUrl = $this->apiUrl . '/api/user/login';

        // Define login strategies
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

        foreach ($strategies as $strategy) {
            try {
                Log::info('Vital API: Trying login strategy', [
                    'environment' => $this->environment,
                    'strategy' => $strategy['name'],
                    'url' => $loginUrl,
                    'payload_keys' => array_keys($strategy['payload']),
                    'header_keys' => array_keys($strategy['headers']),
                ]);

                $request = Http::withHeaders($strategy['headers'])
                    ->timeout($this->loginTimeout)
                    ->connectTimeout(10);

                $request = $this->applySslSettings($request);

                $response = $request->post($loginUrl, $strategy['payload']);

                Log::info('Vital API login response', [
                    'environment' => $this->environment,
                    'strategy' => $strategy['name'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'body_length' => strlen($response->body()),
                ]);

                if ($response->successful()) {
                    $token = $this->extractTokenFromResponse($response);
                    if ($token) {
                        Cache::put($cacheKey, $token, now()->addMinutes(50));
                        Log::info('Vital API: Login successful', [
                            'environment' => $this->environment,
                            'strategy' => $strategy['name'],
                            'token_preview' => substr($token, 0, 20) . '...',
                        ]);
                        return $token;
                    }

                    Log::error('Vital API login: Token not found in successful response', [
                        'environment' => $this->environment,
                        'strategy' => $strategy['name'],
                        'response_keys' => array_keys($response->json()),
                    ]);
                    continue;
                }

                if ($response->status() === 401) {
                    Log::info('Vital API: Strategy returned 401, trying next strategy...', [
                        'environment' => $this->environment,
                    ]);
                    continue;
                }

                Log::warning('Vital API: Login got non-401 error, trying next strategy', [
                    'environment' => $this->environment,
                    'status' => $response->status(),
                ]);
                continue;

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Vital API login connection error', [
                    'environment' => $this->environment,
                    'strategy' => $strategy['name'],
                    'error' => $e->getMessage(),
                ]);
                continue;
            } catch (\Exception $e) {
                Log::error('Vital API login exception', [
                    'environment' => $this->environment,
                    'strategy' => $strategy['name'],
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::error('Vital API: ALL login strategies failed', [
            'environment' => $this->environment,
            'strategies_tried' => count($strategies),
            'username' => $this->apiUsername,
            'url' => $loginUrl,
        ]);
        return null;
    }

    protected function extractTokenFromResponse($response): ?string
    {
        $data = $response->json();

        return $data['token']
            ?? $data['accessToken']
            ?? $data['access_token']
            ?? $data['data']['token']
            ?? $data['data']['accessToken']
            ?? $data['data']['access_token']
            ?? null;
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
                Log::error('Vital API: API URL is not configured', ['environment' => $this->environment]);
                return [
                    'success' => false,
                    'message' => __('License API is not configured for :env environment. Please set the API URL in .env', ['env' => $this->environment]),
                ];
            }

            if (empty($this->productId)) {
                Log::error('Vital API: Product ID is not configured', ['environment' => $this->environment]);
                return [
                    'success' => false,
                    'message' => __('License API product is not configured for :env environment. Please set the Product ID in .env', ['env' => $this->environment]),
                ];
            }

            // Validate productId is a valid UUID/GUID format
            $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
            if (!preg_match($uuidPattern, $this->productId)) {
                Log::error('Vital API: Product ID is not a valid UUID/GUID format', [
                    'environment' => $this->environment,
                    'product_id' => $this->productId,
                ]);
                return [
                    'success' => false,
                    'message' => __('Product ID for :env must be a valid UUID format. Current value: :value', [
                        'env' => $this->environment,
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

            // Get auth token
            $token = $this->getAuthToken();
            if (!$token) {
                Log::error('Vital API: Could not obtain auth token', ['environment' => $this->environment]);
                return [
                    'success' => false,
                    'message' => __('Could not authenticate with the :env license server. Please check API credentials.', ['env' => $this->environment]),
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
                'maxActiveUsersCount' => $plan->max_users ?? 1,
            ];

            // ====== ATTEMPT 1: Bearer token ONLY ======
            $headers = $this->getAuthHeaders($token);

            Log::info('Vital API: Creating license (Attempt 1: Bearer only)', [
                'environment' => $this->environment,
                'url' => $this->apiUrl . '/api/licenses/create',
                'payload' => $payload,
            ]);

            try {
                $request1 = Http::withHeaders($headers)
                    ->timeout($this->apiTimeout)
                    ->connectTimeout(10);
                $request1 = $this->applySslSettings($request1);
                $response = $request1->post($this->apiUrl . '/api/licenses/create', $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Vital API: Connection error (Attempt 1)', [
                    'environment' => $this->environment,
                    'error' => $e->getMessage(),
                ]);
                return [
                    'success' => false,
                    'message' => __('Could not connect to the :env license server. Error: :error', [
                        'env' => $this->environment,
                        'error' => $e->getMessage(),
                    ]),
                ];
            }

            Log::info('Vital API: License creation response (Attempt 1)', [
                'environment' => $this->environment,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return $this->processLicenseResponse($response, $user, $planId, $expirationDate);
            }

            // If 401, try with RemoteManagementApiKey
            if ($response->status() === 401) {
                Log::info('Vital API: Bearer-only got 401, trying with RemoteManagementApiKey...', [
                    'environment' => $this->environment,
                ]);

                // ====== ATTEMPT 2: Bearer + RemoteManagementApiKey ======
                $headers2 = array_merge($this->getAuthHeaders($token), [
                    'RemoteManagementApiKey' => $this->remoteApiKey,
                ]);

                try {
                    $request2 = Http::withHeaders($headers2)
                        ->timeout($this->apiTimeout)
                        ->connectTimeout(10);
                    $request2 = $this->applySslSettings($request2);
                    $response2 = $request2->post($this->apiUrl . '/api/licenses/create', $payload);
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::error('Vital API: Connection error (Attempt 2)', [
                        'environment' => $this->environment,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (isset($response2)) {
                    Log::info('Vital API: License creation response (Attempt 2)', [
                        'environment' => $this->environment,
                        'status' => $response2->status(),
                        'body' => $response2->body(),
                    ]);

                    if ($response2->successful()) {
                        return $this->processLicenseResponse($response2, $user, $planId, $expirationDate);
                    }
                }

                // ====== ATTEMPT 3: Fresh login + Bearer-only ======
                Log::info('Vital API: Attempt 2 failed, trying fresh login...', [
                    'environment' => $this->environment,
                ]);
                Cache::forget($this->getTokenCacheKey());
                $freshToken = $this->getAuthToken(true);

                if ($freshToken) {
                    $headers3 = $this->getAuthHeaders($freshToken);

                    try {
                        $request3 = Http::withHeaders($headers3)
                            ->timeout($this->apiTimeout)
                            ->connectTimeout(10);
                        $request3 = $this->applySslSettings($request3);
                        $response3 = $request3->post($this->apiUrl . '/api/licenses/create', $payload);
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        Log::error('Vital API: Connection error (Attempt 3)', [
                            'environment' => $this->environment,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if (isset($response3)) {
                        Log::info('Vital API: License creation response (Attempt 3)', [
                            'environment' => $this->environment,
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

                    try {
                        $request4 = Http::withHeaders($headers4)
                            ->timeout($this->apiTimeout)
                            ->connectTimeout(10);
                        $request4 = $this->applySslSettings($request4);
                        $response4 = $request4->post($this->apiUrl . '/api/licenses/create', $payload);
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        Log::error('Vital API: Connection error (Attempt 4)', [
                            'environment' => $this->environment,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if (isset($response4)) {
                        Log::info('Vital API: License creation response (Attempt 4)', [
                            'environment' => $this->environment,
                            'status' => $response4->status(),
                            'body' => $response4->body(),
                        ]);

                        if ($response4->successful()) {
                            return $this->processLicenseResponse($response4, $user, $planId, $expirationDate);
                        }
                    }
                }

                Log::error('Vital API: ALL license creation attempts failed', [
                    'environment' => $this->environment,
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                ]);

                return [
                    'success' => false,
                    'message' => __('Failed to generate license key via :env API after multiple attempts.', ['env' => $this->environment]),
                ];
            }

            // Non-401 error (e.g., 400, 422, 500)
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['title'] ?? __('Failed to generate license key via API');

            Log::error('Vital API license creation error', [
                'environment' => $this->environment,
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
                'environment' => $this->environment,
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
                'api_environment' => $this->environment,
            ]);

            Log::info('License key generated and SAVED via Vital API', [
                'environment' => $this->environment,
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
                'environment' => $this->environment,
                'message' => __('License key generated successfully'),
                'data' => $data,
            ];
        }

        Log::error('Vital API: License key not found in successful response', [
            'environment' => $this->environment,
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

            $activateRequest = Http::withHeaders($this->getPublicHeaders())
                ->timeout($this->apiTimeout)
                ->connectTimeout(10);
            $activateRequest = $this->applySslSettings($activateRequest);
            $response = $activateRequest->post($this->apiUrl . '/api/licenses/activate', $payload);

            Log::info('Vital API: License activation response', [
                'environment' => $this->environment,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('License activated via Vital API', [
                    'environment' => $this->environment,
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
                'environment' => $this->environment,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => __('License activation failed'),
            ];

        } catch (\Exception $e) {
            Log::error('License activation exception', [
                'environment' => $this->environment,
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

            $validateRequest = Http::withHeaders([
                    'RemoteManagementApiKey' => $this->remoteApiKey,
                ])
                ->timeout($this->apiTimeout)
                ->connectTimeout(10)
                ->asMultipart();
            $validateRequest = $this->applySslSettings($validateRequest);
            $response = $validateRequest->post($this->apiUrl . '/api/licenses/validate', $multipart);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'valid' => false,
                'message' => __('License validation failed'),
            ];

        } catch (\Exception $e) {
            Log::error('License validation exception', [
                'environment' => $this->environment,
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

            $trialRequest = Http::withHeaders($this->getPublicHeaders())
                ->timeout($this->apiTimeout)
                ->connectTimeout(10);
            $trialRequest = $this->applySslSettings($trialRequest);
            $response = $trialRequest->post($this->apiUrl . '/api/trial-requests', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Trial request submitted via Vital API', [
                    'environment' => $this->environment,
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
                'environment' => $this->environment,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Unable to submit trial request'),
            ];
        }
    }
}
