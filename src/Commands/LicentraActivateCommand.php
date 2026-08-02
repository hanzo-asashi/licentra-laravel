<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Exception;
use Illuminate\Console\Command;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Licentra\LicentraLaravel\Support\LicenseKeyValidator;

class LicentraActivateCommand extends Command
{
    public $signature = 'licentra:activate {license_key? : The license key to activate} {--domain= : Domain name} {--machine-hash= : Machine HWID hash}';

    public $description = 'Activate software license online with Licentra server';

    public function handle(): int
    {
        /** @var string|null $licenseKey */
        $licenseKey = $this->argument('license_key') ?: config('licentra-laravel.license_key');

        if (empty($licenseKey)) {
            /** @var string $licenseKey */
            $licenseKey = $this->ask('Please enter your Licentra License Key');
        }

        if (empty($licenseKey)) {
            $this->error('License key is required for activation.');

            return self::FAILURE;
        }

        LicentraLaravel::setLicenseKey($licenseKey);

        if (! LicenseKeyValidator::isValid($licenseKey)) {
            $this->warn('Format kunci lisensi tidak sesuai standar (LCN-XXXX-XXXX-XXXX-XXXX). Melanjutkan...');
        }

        /** @var string|null $domain */
        $domain = $this->option('domain');
        /** @var string|null $machineHash */
        $machineHash = $this->option('machine-hash');

        $this->info("Activating license: {$licenseKey}...");

        try {
            $result = LicentraLaravel::activate($domain, $machineHash);

            $this->info('License activated successfully!');
            $this->table(['Attribute', 'Value'], [
                ['Status', $result['status'] ?? 'Active'],
                ['License Key', $result['license_key'] ?? $licenseKey],
                ['Valid Until', $result['valid_until'] ?? 'Lifetime'],
                ['Max Machines', $result['max_machines'] ?? 'Unlimited'],
                ['Active Machines', $result['active_machines'] ?? 1],
                ['Features', implode(', ', $result['features'] ?? [])],
            ]);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Activation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
