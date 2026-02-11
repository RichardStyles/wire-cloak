<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use RichardStyles\WireCloak\Http\Middleware\BlockSourceMaps;
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

        // BlockSourceMaps must be global middleware because Livewire's
        // source map routes are registered without a middleware group.
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(BlockSourceMaps::class);
    }
}
