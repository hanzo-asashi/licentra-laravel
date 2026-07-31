<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;

class LicentraHealthCommand extends Command
{
    public $signature = 'licentra:health';

    public $description = 'Perform diagnostic health checks for Licentra SDK integration';

    public function handle(): int
    {
        $this->info('Starting Licentra Health & Diagnostic Check...');

        /** @var string $baseUrl */
        $baseUrl = config('licentra-laravel.url', 'https://licentra.test');
        /** @var string $licenseKey */
        $licenseKey = config('licentra-laravel.license_key', '');
        /** @var bool $verifySsl */
        $verifySsl = config('licentra-laravel.verify_ssl', false);

        $checks = [];

        // Check 1: PHP OpenSSL extension
        $hasOpenSsl = extension_loaded('openssl');
        $checks[] = [
            'Check',
            'PHP OpenSSL Extension',
            $hasOpenSsl ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
            $hasOpenSsl ? 'Loaded' : 'Extension missing',
        ];

        // Check 2: Storage Permissions
        $storageDir = storage_path('app/licentra');
        $writable = false;
        try {
            File::ensureDirectoryExists($storageDir);
            $testFile = $storageDir.'/.health_test';
            File::put($testFile, 'test');
            $writable = File::exists($testFile);
            @File::delete($testFile);
        } catch (Exception $e) {
            $writable = false;
        }
        $checks[] = [
            'Check',
            'Storage Write Access',
            $writable ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
            $writable ? 'Writable [storage/app/licentra]' : 'Permission denied',
        ];

        // Check 3: Public Key Validity
        $pubKey = LicentraLaravel::getPublicKey();
        $checks[] = [
            'Check',
            'RSA Public Key Status',
            ! empty($pubKey) ? '<fg=green>PASS</>' : '<fg=yellow>WARN</>',
            ! empty($pubKey) ? 'Loaded RSA Public Key' : 'Public key not loaded',
        ];

        // Check 4: Server Connectivity
        $serverReachable = false;
        $serverMessage = '';
        try {
            $response = Http::withOptions(['verify' => $verifySsl])->timeout(5)->get("{$baseUrl}/api/license/public-key");
            $serverReachable = $response->successful();
            $serverMessage = $serverReachable ? "HTTP {$response->status()} Connected" : "HTTP {$response->status()} Failed";
        } catch (Exception $e) {
            $serverReachable = false;
            $serverMessage = $e->getMessage();
        }
        $checks[] = [
            'Check',
            'Server Reachability',
            $serverReachable ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
            $serverMessage,
        ];

        // Check 5: License Key Configuration
        $checks[] = [
            'Check',
            'License Key Setting',
            ! empty($licenseKey) ? '<fg=green>PASS</>' : '<fg=yellow>WARN</>',
            ! empty($licenseKey) ? 'Configured' : 'LICENTRA_LICENSE_KEY is empty',
        ];

        $this->table(['Category', 'Diagnostic Item', 'Result', 'Details'], $checks);

        $hasFail = in_array('<fg=red>FAIL</>', array_column($checks, 2), true);
        if ($hasFail) {
            $this->error('Licentra health check completed with failures.');

            return self::FAILURE;
        }

        $this->info('All Licentra diagnostic checks passed successfully!');

        return self::SUCCESS;
    }
}
