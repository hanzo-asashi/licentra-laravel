<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Illuminate\Console\Command;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Licentra\LicentraLaravel\Support\HwidGenerator;

class LicentraStatusCommand extends Command
{
    public $signature = 'licentra:status';

    public $description = 'Display current Licentra software license status';

    public function handle(): int
    {
        /** @var string $licenseKey */
        $licenseKey = config('licentra-laravel.license_key', '');
        /** @var string $baseUrl */
        $baseUrl = config('licentra-laravel.url', '');

        if (empty($licenseKey)) {
            $this->warn('LICENTRA_LICENSE_KEY is not configured in environment or config/licentra-laravel.php.');
        }

        $this->info('Checking Licentra License Status...');
        $isValid = LicentraLaravel::ping();
        $hwid = HwidGenerator::generate();

        $this->table(['Setting', 'Value'], [
            ['Server URL', $baseUrl],
            ['License Key', $licenseKey ?: 'Not Configured'],
            ['Status', $isValid ? '<fg=green;options=bold>VALID</>' : '<fg=red;options=bold>INVALID / EXPIRED</>'],
            ['Machine HWID', $hwid],
            ['Public Key Loaded', LicentraLaravel::getPublicKey() ? 'Yes' : 'No'],
        ]);

        return self::SUCCESS;
    }
}
