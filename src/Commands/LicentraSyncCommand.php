<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Illuminate\Console\Command;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Throwable;

class LicentraSyncCommand extends Command
{
    public $signature = 'licentra:sync {--force : Force bypass ping cache during sync}';

    public $description = 'Sync Licentra software license ping and CRL status in background';

    public function handle(): int
    {
        /** @var string $licenseKey */
        $licenseKey = (string) config('licentra-laravel.license_key', '');

        if (empty($licenseKey)) {
            $this->error('LICENTRA_LICENSE_KEY is missing in environment/config. Sync aborted.');

            return self::FAILURE;
        }

        if ($this->option('force')) {
            $this->info('Force clearing local ping cache...');
            LicentraLaravel::clearCache($licenseKey);
        }

        $this->info('Syncing license status with Licentra server...');

        // Perform ping sync
        $isValid = LicentraLaravel::ping();

        // Perform CRL sync
        $isRevoked = false;
        try {
            $crlData = LicentraLaravel::fetchCrl();
            $isRevoked = LicentraLaravel::isRevoked($licenseKey, $crlData);
        } catch (Throwable $e) {
            $this->warn('Could not update CRL from server: '.$e->getMessage());
        }

        if ($isRevoked) {
            $this->error("License [{$licenseKey}] is REVOKED according to CRL!");

            return self::FAILURE;
        }

        if (! $isValid) {
            $this->error('License ping failed or license is expired/invalid.');

            return self::FAILURE;
        }

        $this->info("License [{$licenseKey}] status synced successfully. Status: VALID");

        return self::SUCCESS;
    }
}
