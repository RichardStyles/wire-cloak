<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak;

use Illuminate\Routing\Router;
use RichardStyles\WireCloak\Http\Middleware\CloakLivewireFingerprints;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WireCloakServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('wire-cloak')
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');

        $router->pushMiddlewareToGroup('web', CloakLivewireFingerprints::class);
    }
}
