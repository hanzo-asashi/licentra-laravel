<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use ZipArchive;

class LicentraUpdateCommand extends Command
{
    public $signature = 'licentra:update {--force : Force update without confirmation prompt} {--current-version= : Current app version}';

    public $description = 'Automated software update installer from Licentra release server';

    public function handle(): int
    {
        $this->info('Checking for software updates...');

        /** @var string|null $versionOpt */
        $versionOpt = $this->option('current-version');
        $currentVersion = $versionOpt ?: '1.0.0';

        try {
            $updateInfo = LicentraLaravel::checkForUpdates($currentVersion);
        } catch (Exception $e) {
            $this->error("Failed to check for updates: {$e->getMessage()}");

            return self::FAILURE;
        }

        /** @var bool $updateAvailable */
        $updateAvailable = $updateInfo['update_available'] ?? false;
        /** @var string|null $latestVersion */
        $latestVersion = $updateInfo['latest_version'] ?? null;
        /** @var string|null $changelog */
        $changelog = $updateInfo['changelog'] ?? null;

        if (! $updateAvailable || empty($latestVersion)) {
            $this->info("Your application is up to date (Version {$currentVersion}).");

            return self::SUCCESS;
        }

        $this->info("New update available: v{$latestVersion} (Current: v{$currentVersion})");
        if (! empty($changelog)) {
            $this->line("Changelog:\n{$changelog}");
        }

        /** @var bool $force */
        $force = (bool) $this->option('force');

        if (! $force && ! $this->confirm("Do you want to download and install update v{$latestVersion} now?")) {
            $this->warn('Update installation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Downloading release package...');
        $tmpZipPath = storage_path("app/licentra/updates/release-{$latestVersion}.zip");

        try {
            LicentraLaravel::downloadRelease($latestVersion, $tmpZipPath);
        } catch (Exception $e) {
            $this->error("Download failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Entering maintenance mode...');
        Artisan::call('down', ['--message' => "System is installing software update v{$latestVersion}..."]);

        $installed = false;
        try {
            if (class_exists(ZipArchive::class)) {
                $zip = new ZipArchive;
                if ($zip->open($tmpZipPath) === true) {
                    $zip->extractTo(base_path());
                    $zip->close();
                    $installed = true;
                }
            }
        } catch (Exception $e) {
            $this->error("Extraction failed: {$e->getMessage()}");
        } finally {
            @File::delete($tmpZipPath);
        }

        if ($installed) {
            $this->info('Running database migrations...');
            Artisan::call('migrate', ['--force' => true]);

            $this->info('Clearing application cache...');
            LicentraLaravel::clearCache();
        }

        $this->info('Exiting maintenance mode...');
        Artisan::call('up');

        if ($installed) {
            $this->info("Application successfully updated to v{$latestVersion}!");

            return self::SUCCESS;
        }

        $this->error('Update installation encountered an error.');

        return self::FAILURE;
    }
}
