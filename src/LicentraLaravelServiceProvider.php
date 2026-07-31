<?php

namespace Licentra\LicentraLaravel;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Licentra\LicentraLaravel\Commands\LicentraActivateCommand;
use Licentra\LicentraLaravel\Commands\LicentraClearCacheCommand;
use Licentra\LicentraLaravel\Commands\LicentraStatusCommand;
use Licentra\LicentraLaravel\Http\Controllers\WebhookController;
use Licentra\LicentraLaravel\Listeners\CheckOutSeatOnLogout;
use Licentra\LicentraLaravel\Middleware\EnsureFeatureIsActive;
use Licentra\LicentraLaravel\Middleware\EnsureLicenseIsValid;
use Licentra\LicentraLaravel\Middleware\KeepSeatAliveMiddleware;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LicentraLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('licentra-laravel')
            ->hasConfigFile()
            ->hasCommands([
                LicentraActivateCommand::class,
                LicentraStatusCommand::class,
                LicentraClearCacheCommand::class,
            ]);
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
            $router->aliasMiddleware('licentra.seat_alive', KeepSeatAliveMiddleware::class);
        }
    }

    public function packageBooted(): void
    {
        // Register Webhook Route if enabled
        if (config('licentra-laravel.webhook.enabled', true)) {
            /** @var string $webhookPath */
            $webhookPath = config('licentra-laravel.webhook.path', '/licentra/webhook');
            Route::post($webhookPath, WebhookController::class)
                ->middleware('api')
                ->name('licentra.webhook');
        }

        // Register Logout Event listener for Seat Check-out
        Event::listen(Logout::class, CheckOutSeatOnLogout::class);

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
