<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Illuminate\Console\Command;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Throwable;

class LicentraInstallLicenseCommand extends Command
{
    public $signature = 'licentra:install-license {file : Path to .lic file or raw JSON content} {--path= : Custom destination path for storing license file}';

    public $description = 'Verify RSA signature and install an offline license file (.lic)';

    public function handle(): int
    {
        /** @var string $fileInput */
        $fileInput = (string) $this->argument('file');
        $pathOption = $this->option('path');
        $customPath = is_string($pathOption) && $pathOption !== '' ? $pathOption : null;

        $this->info('Verifying offline license signature...');

        try {
            $payload = LicentraLaravel::verifyOfflineLicense($fileInput);
        } catch (Throwable $e) {
            $this->error('Offline license verification failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $saved = LicentraLaravel::saveOfflineLicense($fileInput, $customPath);

        if (! $saved) {
            $this->error('Failed to save offline license file to local storage.');

            return self::FAILURE;
        }

        $this->info('Offline license verified and installed successfully!');

        $targetPath = $customPath ?? storage_path('app/licentra/license.lic');

        $this->table(['Property', 'Value'], [
            ['License Key', $payload['license_key'] ?? 'N/A'],
            ['Product Slug', $payload['product_slug'] ?? 'N/A'],
            ['License Type', $payload['license_type'] ?? 'N/A'],
            ['Valid Until', $payload['valid_until'] ?? 'Lifetime'],
            ['Saved Location', $targetPath],
            ['RSA Signature', '<fg=green;options=bold>VERIFIED</>'],
        ]);

        return self::SUCCESS;
    }
}
