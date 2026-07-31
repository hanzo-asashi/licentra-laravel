<?php

namespace Licentra\LicentraLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array activate(?string $domain = null, ?string $machineHash = null)
 * @method static bool ping(?string $nonce = null)
 * @method static bool hasFeature(string $featureName, ?array $activeFeatures = null)
 * @method static array checkForUpdates(?string $currentVersion = null, ?string $productSlug = null)
 * @method static array checkInSeat(string $sessionId, ?string $userIdentifier = null)
 * @method static bool checkOutSeat(string $sessionId)
 * @method static ?string getPublicKey()
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
