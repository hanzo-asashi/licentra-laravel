<?php

namespace Licentra\LicentraLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> activate(?string $domain = null, ?string $machineHash = null)
 * @method static bool ping(?string $nonce = null)
 * @method static bool hasFeature(string $featureName, ?array<int, string> $activeFeatures = null)
 * @method static mixed getLimit(string $limitKey, mixed $default = null, ?array<string, mixed> $activeLimits = null)
 * @method static bool hasReachedLimit(string $limitKey, int|float $currentUsage, ?array<string, mixed> $activeLimits = null)
 * @method static array<string, mixed> fetchCrl()
 * @method static bool isRevoked(?string $targetLicenseKey = null, ?array<string, mixed> $crlData = null)
 * @method static array<string, mixed> requestHwidReset(string $reason)
 * @method static array<string, mixed> checkForUpdates(?string $currentVersion = null, ?string $productSlug = null)
 * @method static bool downloadRelease(string $version, string $saveToPath, ?string $signature = null)
 * @method static array<string, mixed> verifyOfflineLicense(string $fileContentOrPath)
 * @method static bool saveOfflineLicense(string $fileContentOrPath, ?string $targetPath = null)
 * @method static array<string, mixed> loadOfflineLicense(?string $path = null)
 * @method static array<string, mixed> verifyJwt(string $jwtToken)
 * @method static array<string, mixed> checkInSeat(string $sessionId, ?string $userIdentifier = null)
 * @method static array<string, mixed> keepSeatAlive(string $sessionId, ?string $userIdentifier = null)
 * @method static bool checkOutSeat(string $sessionId)
 * @method static void clearCache(?string $licenseKey = null)
 * @method static ?string getPublicKey()
 * @method static string getLicenseKey()
 * @method static void resolveLicenseKeyUsing(callable $resolver)
 * @method static \Licentra\LicentraLaravel\LicentraLaravel forLicenseKey(string $licenseKey)
 * @method static \Licentra\LicentraLaravel\LicentraLaravel forTenant(mixed $tenant, string $licenseKeyAttribute = 'license_key')
 *
 * @see \Licentra\LicentraLaravel\LicentraLaravel
 */
class LicentraLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Licentra\LicentraLaravel\LicentraLaravel::class;
    }
}
