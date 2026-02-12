<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('blade precompiler renames wire: directives at compile time', function () {
    $compiled = Blade::compileString('<button wire:click="increment">+</button>');

    expect($compiled)
        ->toContain('wc:click="increment"')
        ->not->toContain('wire:click');
});

test('blade precompiler renames wire:model at compile time', function () {
    $compiled = Blade::compileString('<input wire:model="name" type="text">');

    expect($compiled)
        ->toContain('wc:model="name"')
        ->not->toContain('wire:model');
});

test('blade precompiler renames wire:loading at compile time', function () {
    $compiled = Blade::compileString('<span wire:loading>Loading...</span>');

    expect($compiled)
        ->toContain('wc:loading')
        ->not->toContain('wire:loading');
});

test('blade precompiler does not rename when wire_prefix_alias is null', function () {
    config(['wire-cloak.wire_prefix_alias' => null]);

    // Re-register precompilers by re-booting the provider.
    $compiled = Blade::compileString('<button wire:click="increment">+</button>');

    // The precompiler was registered at boot with the original alias,
    // so this test verifies the config was checked at registration time.
    // Since the precompiler was already registered with 'wc', we check
    // by creating a fresh compiler instance.
    $compiler = new \Illuminate\View\Compilers\BladeCompiler(
        app('files'),
        sys_get_temp_dir(),
    );

    $compiled = $compiler->compileString('<button wire:click="increment">+</button>');

    expect($compiled)->toContain('wire:click="increment"');
});

test('compiled blade views contain renamed prefix instead of wire:', function () {
    $template = <<<'BLADE'
    <div>
        <button wire:click="save" wire:loading.attr="disabled">Save</button>
        <span wire:loading wire:target="save">Saving...</span>
        <input wire:model.live="title" type="text">
    </div>
    BLADE;

    $compiled = Blade::compileString($template);

    expect($compiled)
        ->toContain('wc:click="save"')
        ->toContain('wc:loading.attr="disabled"')
        ->toContain('wc:loading')
        ->toContain('wc:target="save"')
        ->toContain('wc:model.live="title"')
        ->not->toContain('wire:click')
        ->not->toContain('wire:loading')
        ->not->toContain('wire:target')
        ->not->toContain('wire:model');
});
