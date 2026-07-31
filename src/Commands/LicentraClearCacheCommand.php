<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Illuminate\Console\Command;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;

class LicentraClearCacheCommand extends Command
{
    public $signature = 'licentra:clear-cache {license_key? : Specific license key to purge}';

    public $description = 'Purge cached local Licentra license data';

    public function handle(): int
    {
        /** @var string|null $key */
        $key = $this->argument('license_key');

        LicentraLaravel::clearCache($key);

        $this->info('Licentra license cache successfully cleared.');

        return self::SUCCESS;
    }
}
