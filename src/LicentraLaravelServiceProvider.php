<?php

namespace Licentra\LicentraLaravel;

use Illuminate\Support\Facades\Blade;
use Licentra\LicentraLaravel\Middleware\EnsureFeatureIsActive;
use Licentra\LicentraLaravel\Middleware\EnsureLicenseIsValid;
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
            /** @var string $baseUrl */
            $baseUrl = config('licentra-laravel.url', 'https://licentra.test');
            /** @var string $licenseKey */
            $licenseKey = config('licentra-laravel.license_key', '');
            /** @var string|null $publicKey */
            $publicKey = config('licentra-laravel.public_key');
            /** @var bool $verifySsl */
            $verifySsl = config('licentra-laravel.verify_ssl', false);

            return new LicentraLaravel(
                baseUrl: $baseUrl,
                licenseKey: $licenseKey,
                publicKey: $publicKey,
                verifySsl: $verifySsl
            );
        });

        // Register Middleware aliases
        $router = $this->app['router'];
        if ($router) {
            $router->aliasMiddleware('licentra.valid', EnsureLicenseIsValid::class);
            $router->aliasMiddleware('licentra.feature', EnsureFeatureIsActive::class);
        }
    }

    public function packageBooted(): void
    {
        // Register Blade Directives
        Blade::if('hasFeature', function (string $feature) {
            /** @var LicentraLaravel $licentra */
            $licentra = app(LicentraLaravel::class);

            return $licentra->hasFeature($feature);
        });

        Blade::if('licenseValid', function () {
            /** @var LicentraLaravel $licentra */
            $licentra = app(LicentraLaravel::class);

            return $licentra->ping();
        });
    }
}
