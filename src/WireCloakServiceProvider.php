<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use RichardStyles\WireCloak\Commands\ObfuscateCommand;
use RichardStyles\WireCloak\Http\Middleware\CloakLivewireFingerprints;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WireCloakServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('wire-cloak')
            ->hasConfigFile()
            ->hasCommand(ObfuscateCommand::class);
    }

    public function packageBooted(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');

        $router->pushMiddlewareToGroup('web', CloakLivewireFingerprints::class);

        $this->registerBladePrecompiler();
    }

    /**
     * Register a Blade precompiler that renames wire: directives at compile time.
     *
     * Template-authored attributes (wire:click, wire:model, etc.) are renamed
     * in the raw Blade string before compilation, so the compiled view cache
     * already contains the alias. This avoids per-request str_replace overhead
     * for these attributes.
     *
     * Runtime-injected attributes (wire:id, wire:snapshot, wire:effects) and
     * the script/style tags are still handled by the response middleware.
     */
    private function registerBladePrecompiler(): void
    {
        if (! config('wire-cloak.enabled', true)) {
            return;
        }

        /** @var string|null $alias */
        $alias = config('wire-cloak.wire_prefix_alias');

        if (! is_string($alias) || $alias === '') {
            return;
        }

        Blade::precompiler(function (string $content) use ($alias): string {
            return str_replace('wire:', $alias.':', $content);
        });
    }
}
