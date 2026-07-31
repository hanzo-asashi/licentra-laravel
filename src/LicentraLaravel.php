<?php

namespace Licentra\LicentraLaravel;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LicentraLaravel
{
    private string $baseUrl;

    private string $licenseKey;

    private ?string $publicKey;

    public function __construct(?string $baseUrl = null, ?string $licenseKey = null, ?string $publicKey = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('licentra-laravel.url', 'https://licentra.test'), '/');
        $this->licenseKey = $licenseKey ?? config('licentra-laravel.license_key', '');
        $this->publicKey = $publicKey ?? config('licentra-laravel.public_key');
    }

    /**
     * Set or override the license key dynamically.
     */
    public function setLicenseKey(string $licenseKey): self
    {
        $this->licenseKey = $licenseKey;

        return $this;
    }

    /**
     * Get current public key.
     */
    public function getPublicKey(): ?string
    {
        if (empty($this->publicKey)) {
            try {
                $response = Http::withOptions(['verify' => false])
                    ->get("{$this->baseUrl}/api/license/public-key");

                if ($response->successful()) {
                    $this->publicKey = $response->json('public_key');
                }
            } catch (Exception $e) {
                // Ignore fetch error if offline
            }
        }

        return $this->publicKey;
    }

    /**
     * Activate license online.
     */
    public function activate(?string $domain = null, ?string $machineHash = null): array
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/api/license/activate", array_filter([
                'license_key' => $this->licenseKey,
                'domain' => $domain,
                'machine_hash' => $machineHash,
                'nonce' => bin2hex(random_bytes(8)),
                'timestamp' => now()->toIso8601String(),
            ]));

        if ($response->failed()) {
            throw new Exception($response->json('message', 'License activation failed'));
        }

        $data = $response->json('data', []);

        // Cache the active license status
        Cache::put("licentra_status_{$this->licenseKey}", true, config('licentra-laravel.cache_ttl', 3600));
        Cache::put("licentra_data_{$this->licenseKey}", $data, config('licentra-laravel.cache_ttl', 3600));

        return $data;
    }

    /**
     * Perform license ping with caching & grace period fallback.
     */
    public function ping(?string $nonce = null): bool
    {
        $cacheKey = "licentra_ping_{$this->licenseKey}";

        if (Cache::has($cacheKey)) {
            return (bool) Cache::get($cacheKey);
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/license/ping", [
                    'license_key' => $this->licenseKey,
                    'nonce' => $nonce ?? bin2hex(random_bytes(8)),
                ]);

            if ($response->successful() && $response->json('data.status') === 'valid') {
                Cache::put($cacheKey, true, config('licentra-laravel.cache_ttl', 3600));
                Cache::put("licentra_last_successful_ping_{$this->licenseKey}", now(), now()->addDays(30));

                if ($features = $response->json('data.features')) {
                    Cache::put("licentra_features_{$this->licenseKey}", $features, config('licentra-laravel.cache_ttl', 3600));
                }

                return true;
            }
        } catch (Exception $e) {
            // Check grace period fallback if server is unreachable
            $lastPing = Cache::get("licentra_last_successful_ping_{$this->licenseKey}");
            $graceDays = (int) config('licentra-laravel.grace_period_days', 3);

            if ($lastPing && now()->diffInDays($lastPing) <= $graceDays) {
                return true;
            }
        }

        Cache::put($cacheKey, false, 60);

        return false;
    }

    /**
     * Check if a feature is enabled for the license.
     */
    public function hasFeature(string $featureName, ?array $activeFeatures = null): bool
    {
        $features = $activeFeatures ?? Cache::get("licentra_features_{$this->licenseKey}", []);

        return in_array($featureName, $features, true);
    }

    /**
     * Check for software updates.
     */
    public function checkForUpdates(?string $currentVersion = null, ?string $productSlug = null): array
    {
        $product = $productSlug ?? config('licentra-laravel.product_slug', 'aquanusa');
        $version = $currentVersion ?? '1.0.0';

        $url = "{$this->baseUrl}/api/releases/check?".http_build_query([
            'license_key' => $this->licenseKey,
            'product' => $product,
            'current_version' => $version,
        ]);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['Accept' => 'application/json'])
            ->get($url);

        return $response->json() ?? [];
    }

    /**
     * Check-in concurrent seat.
     */
    public function checkInSeat(string $sessionId, ?string $userIdentifier = null): array
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/api/license/seat/check-in", array_filter([
                'license_key' => $this->licenseKey,
                'session_id' => $sessionId,
                'user_identifier' => $userIdentifier,
            ]));

        if ($response->failed()) {
            throw new Exception($response->json('message', 'Seat check-in failed'));
        }

        return $response->json() ?? [];
    }

    /**
     * Check-out concurrent seat.
     */
    public function checkOutSeat(string $sessionId): bool
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/api/license/seat/check-out", [
                'license_key' => $this->licenseKey,
                'session_id' => $sessionId,
            ]);

        return $response->successful();
    }
}
