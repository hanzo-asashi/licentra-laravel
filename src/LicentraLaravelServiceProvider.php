<?php

namespace Licentra\LicentraLaravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LicentraLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('licentra-laravel')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LicentraLaravel::class, function ($app) {
            return new LicentraLaravel(
                baseUrl: config('licentra-laravel.url', 'https://licentra.test'),
                licenseKey: config('licentra-laravel.license_key', ''),
                publicKey: config('licentra-laravel.public_key')
            );
        });
    }
}
