<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LicenseApiService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $productId;

    public function __construct()
    {
        $this->baseUrl = config('services.license_api.base_url');
        $this->username = config('services.license_api.username');
        $this->password = config('services.license_api.password');
        $this->productId = config('services.license_api.product_id');
    }

    /**
     * Get an authenticated access token from the license API.
     * Tokens are cached for 50 minutes (they typically expire in 60 min).
     */
    private function getAccessToken(): string
    {
        return Cache::remember('license_api_token', now()->addMinutes(50), function () {
            $response = Http::post("{$this->baseUrl}/api/user/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if (!$response->successful()) {
                Log::error('License API login failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to authenticate with the license API');
            }

            $data = $response->json();

            if (empty($data['accessToken'])) {
                throw new \Exception('No access token received from the license API');
            }

            return $data['accessToken'];
        });
    }

    /**
     * Create a new license via the external API.
     *
     * @param string $issuedTo  The company name the license is issued to
     * @param string $licenseType  "Subscription" or "Trial"
     * @param string $expirationDate  ISO 8601 datetime string (e.g. "2025-12-31T23:59:59Z")
     * @param int|null $maxActiveUsersCount  Optional max concurrent users
     * @return array  The full CreateLicenseResponseDto from the API
     * @throws \Exception
     */
    public function createLicense(
        string $issuedTo,
        string $licenseType,
        string $expirationDate,
        ?int $maxActiveUsersCount = null
    ): array {
        $token = $this->getAccessToken();

        $payload = [
            'issuedTo' => $issuedTo,
            'licenseType' => $licenseType,   // "Subscription" or "Trial"
            'expirationDate' => $expirationDate,
        ];

        // Add optional productId if configured
        if ($this->productId) {
            $payload['productId'] = $this->productId;
        }

        // Add optional maxActiveUsersCount
        if ($maxActiveUsersCount !== null) {
            $payload['maxActiveUsersCount'] = $maxActiveUsersCount;
        }

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/api/licenses/create", $payload);

        if (!$response->successful()) {
            Log::error('License API create license failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            // If unauthorized, clear the cached token and retry once
            if ($response->status() === 401) {
                Cache::forget('license_api_token');

                $token = $this->getAccessToken();

                $response = Http::withToken($token)
                    ->post("{$this->baseUrl}/api/licenses/create", $payload);

                if (!$response->successful()) {
                    throw new \Exception('Failed to create license after retry: ' . $response->body());
                }
            } else {
                throw new \Exception('Failed to create license: ' . $response->body());
            }
        }

        return $response->json();
    }

    /**
     * Renew an existing license via the external API.
     *
     * @param string $licenseKey
     * @param string $expirationDate  New expiration date (ISO 8601)
     * @return array
     * @throws \Exception
     */
    public function renewLicense(string $licenseKey, string $expirationDate): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'licenseKey' => $licenseKey,
            'newExpirationDate' => $expirationDate,
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/api/licenses/renew", $payload);

        if (!$response->successful()) {
            Log::error('License API renew license failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to renew license: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Revoke a license via the external API.
     *
     * @param string $licenseKey
     * @return array
     * @throws \Exception
     */
    public function revokeLicense(string $licenseKey): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'licenseKey' => $licenseKey,
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/api/licenses/revoke", $payload);

        if (!$response->successful()) {
            Log::error('License API revoke license failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to revoke license: ' . $response->body());
        }

        return $response->json();
    }
}
