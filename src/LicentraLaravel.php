<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Licentra\LicentraLaravel\Support\CryptoVerifier;
use Licentra\LicentraLaravel\Support\HwidGenerator;

class LicentraLaravel
{
    private string $baseUrl;

    private string $licenseKey;

    private ?string $publicKey;

    private bool $verifySsl;

    public function __construct(?string $baseUrl = null, ?string $licenseKey = null, ?string $publicKey = null, ?bool $verifySsl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('licentra-laravel.url', 'https://licentra.test'), '/');
        $this->licenseKey = $licenseKey ?? (string) config('licentra-laravel.license_key', '');
        $this->publicKey = $publicKey ?? config('licentra-laravel.public_key');
        $this->verifySsl = $verifySsl ?? (bool) config('licentra-laravel.verify_ssl', false);
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
                $response = Http::withOptions(['verify' => $this->verifySsl])
                    ->get("{$this->baseUrl}/api/license/public-key");

                if ($response->successful()) {
                    /** @var string|null $pubKey */
                    $pubKey = $response->json('public_key');
                    $this->publicKey = $pubKey;
                }
            } catch (Exception $e) {
                // Ignore fetch error if offline
            }
        }

        return $this->publicKey;
    }

    /**
     * Activate license online with RSA signature verification and automatic HWID binding.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function activate(?string $domain = null, ?string $machineHash = null): array
    {
        $machineHash = $machineHash ?? HwidGenerator::generate();
        $nonce = bin2hex(random_bytes(8));
        $timestamp = now()->toIso8601String();

        $response = Http::withOptions(['verify' => $this->verifySsl])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/api/license/activate", array_filter([
                'license_key' => $this->licenseKey,
                'domain' => $domain,
                'machine_hash' => $machineHash,
                'nonce' => $nonce,
                'timestamp' => $timestamp,
            ]));

        if ($response->failed()) {
            throw new Exception((string) $response->json('message', 'License activation failed'));
        }

        /** @var array<string, mixed> $data */
        $data = $response->json('data', []);
        /** @var string|null $signature */
        $signature = $response->json('signature');

        // RSA Signature Verification
        $pubKey = $this->getPublicKey();
        if (! empty($signature) && ! empty($pubKey)) {
            $payloadJson = json_encode($data);
            if ($payloadJson === false || ! CryptoVerifier::verifySignature($payloadJson, $signature, $pubKey)) {
                throw new Exception('License activation response failed RSA signature verification.');
            }
        }

        // Cache active status
        Cache::put("licentra_status_{$this->licenseKey}", true, config('licentra-laravel.cache_ttl', 3600));
        Cache::put("licentra_data_{$this->licenseKey}", $data, config('licentra-laravel.cache_ttl', 3600));

        if (isset($data['features']) && is_array($data['features'])) {
            Cache::put("licentra_features_{$this->licenseKey}", $data['features'], config('licentra-laravel.cache_ttl', 3600));
        }

        return $data;
    }

    /**
     * Perform license ping with RSA signature verification, replay protection & grace period fallback.
     */
    public function ping(?string $nonce = null): bool
    {
        $cacheKey = "licentra_ping_{$this->licenseKey}";

        if (Cache::has($cacheKey)) {
            return (bool) Cache::get($cacheKey);
        }

        $sentNonce = $nonce ?? bin2hex(random_bytes(8));

        try {
            $response = Http::withOptions(['verify' => $this->verifySsl])
                ->timeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/api/license/ping", [
                    'license_key' => $this->licenseKey,
                    'nonce' => $sentNonce,
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful() && $response->json('data.status') === 'valid') {
                /** @var array<string, mixed> $data */
                $data = $response->json('data', []);
                /** @var string|null $signature */
                $signature = $response->json('signature');

                // Verify RSA Signature if available
                $pubKey = $this->getPublicKey();
                if (! empty($signature) && ! empty($pubKey)) {
                    $payloadJson = json_encode($data);
                    if ($payloadJson === false || ! CryptoVerifier::verifySignature($payloadJson, $signature, $pubKey)) {
                        Cache::put($cacheKey, false, 60);

                        return false;
                    }
                }

                // Verify replay protection nonce
                if (isset($data['nonce']) && $data['nonce'] !== $sentNonce) {
                    Cache::put($cacheKey, false, 60);

                    return false;
                }

                Cache::put($cacheKey, true, config('licentra-laravel.cache_ttl', 3600));
                Cache::put("licentra_last_successful_ping_{$this->licenseKey}", now(), now()->addDays(30));

                if (isset($data['features']) && is_array($data['features'])) {
                    Cache::put("licentra_features_{$this->licenseKey}", $data['features'], config('licentra-laravel.cache_ttl', 3600));
                }

                return true;
            }
        } catch (Exception $e) {
            // Grace period fallback if server is unreachable
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
     *
     * @param  array<int, string>|null  $activeFeatures
     */
    public function hasFeature(string $featureName, ?array $activeFeatures = null): bool
    {
        /** @var array<int, string> $features */
        $features = $activeFeatures ?? Cache::get("licentra_features_{$this->licenseKey}", []);

        return in_array($featureName, $features, true);
    }

    /**
     * Request HWID Reset for machine binding.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function requestHwidReset(string $reason): array
    {
        $response = Http::withOptions(['verify' => $this->verifySsl])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/api/license/hwid-reset", [
                'license_key' => $this->licenseKey,
                'reason' => $reason,
            ]);

        if ($response->failed()) {
            throw new Exception((string) $response->json('message', 'HWID reset request failed'));
        }

        /** @var array<string, mixed> */
        return $response->json() ?? [];
    }

    /**
     * Check for software updates.
     *
     * @return array<string, mixed>
     */
    public function checkForUpdates(?string $currentVersion = null, ?string $productSlug = null): array
    {
        $product = $productSlug ?? (string) config('licentra-laravel.product_slug', 'aquanusa');
        $version = $currentVersion ?? '1.0.0';

        $url = "{$this->baseUrl}/api/releases/check?".http_build_query([
            'license_key' => $this->licenseKey,
            'product' => $product,
            'current_version' => $version,
        ]);

        $response = Http::withOptions(['verify' => $this->verifySsl])
            ->withHeaders(['Accept' => 'application/json'])
            ->get($url);

        /** @var array<string, mixed> */
        return $response->json() ?? [];
    }

    /**
     * Download software release update file.
     *
     * @throws Exception
     */
    public function downloadRelease(string $version, string $saveToPath, ?string $signature = null): bool
    {
        if (empty($signature)) {
            $updateInfo = $this->checkForUpdates();
            /** @var string|null $downloadUrl */
            $downloadUrl = $updateInfo['download_url'] ?? null;
            if (empty($downloadUrl)) {
                throw new Exception('No download URL available for release.');
            }
        } else {
            $downloadUrl = route('api.releases.download', [
                'license_key' => $this->licenseKey,
                'version' => $version,
                'signature' => $signature,
            ]);
        }

        $response = Http::withOptions(['verify' => $this->verifySsl])
            ->get($downloadUrl);

        if ($response->failed()) {
            throw new Exception((string) $response->json('message', 'Release download failed'));
        }

        File::ensureDirectoryExists(dirname($saveToPath));
        File::put($saveToPath, $response->body());

        return true;
    }

    /**
     * Verify offline license file (.lic) contents using RSA Public Key.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function verifyOfflineLicense(string $fileContentOrPath): array
    {
        $pubKey = $this->getPublicKey();
        if (empty($pubKey)) {
            throw new Exception('RSA Public Key is required for offline license verification.');
        }

        $content = File::exists($fileContentOrPath) ? File::get($fileContentOrPath) : $fileContentOrPath;

        return CryptoVerifier::verifyOfflineLicense($content, $pubKey);
    }

    /**
     * Verify RS256 JWT license token using RSA Public Key.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function verifyJwt(string $jwtToken): array
    {
        $pubKey = $this->getPublicKey();
        if (empty($pubKey)) {
            throw new Exception('RSA Public Key is required for RS256 JWT verification.');
        }

        return CryptoVerifier::verifyJwt($jwtToken, $pubKey);
    }

    /**
     * Check-in concurrent seat.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function checkInSeat(string $sessionId, ?string $userIdentifier = null): array
    {
        $response = Http::withOptions(['verify' => $this->verifySsl])
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
            throw new Exception((string) $response->json('message', 'Seat check-in failed'));
        }

        /** @var array<string, mixed> */
        return $response->json() ?? [];
    }

    /**
     * Send heartbeat/ping to keep concurrent seat alive.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function keepSeatAlive(string $sessionId, ?string $userIdentifier = null): array
    {
        return $this->checkInSeat($sessionId, $userIdentifier);
    }

    /**
     * Check-out concurrent seat.
     */
    public function checkOutSeat(string $sessionId): bool
    {
        $response = Http::withOptions(['verify' => $this->verifySsl])
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
