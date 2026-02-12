<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-page', function () {
        return response(
            '<!DOCTYPE html><html><head></head><body>'
            .'<!-- Livewire Styles -->'
            .'<style data-livewire-style>[wire\:loading]{display:none;} :root{--livewire-progress-bar-color:#29d;} dialog#livewire-error::backdrop{background:rgba(0,0,0,.6);}</style>'
            .'<!-- Livewire Scripts -->'
            .'<script src="/livewire-abcd1234/livewire.min.js?id=cfc5c1ae" data-csrf="token" data-module-url="/livewire-abcd1234" data-update-uri="/livewire-abcd1234/update" data-no-progress-bar></script>'
            .'<script data-navigate-once="true">window.livewireScriptConfig = {"csrf":"token","uri":"/livewire-abcd1234/update"};</script>'
            .'<script>
                console.warn(\'Livewire: The published Livewire assets are out of date\n See: https://livewire.laravel.com/docs/installation\')
            </script>'
            .'<div wire:id="abc123" wire:name="counter" wire:snapshot="{}" wire:effects="{}">'
            .'Count: 0</div>'
            .'</body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    })->middleware('web');
});

test('strips livewire html comments', function () {
    $response = $this->get('/test-page');

    $response->assertDontSee('<!-- Livewire Scripts -->', false);
    $response->assertDontSee('<!-- Livewire Styles -->', false);
});

test('strips build hash from script src', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)->not->toContain('?id=cfc5c1ae');
});

test('strips wire:name attributes', function () {
    config([
        'wire-cloak.wire_prefix_alias' => null,
        'wire-cloak.livewire_alias' => null,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)->not->toContain('wire:name=');
    expect($content)->toContain('wire:id="abc123"');
    expect($content)->toContain('wire:snapshot=');
});

test('strips console warning scripts', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)->not->toContain("console.warn('Livewire:");
});

test('preserves functional attributes with renames applied', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('wc:id="abc123"')
        ->toContain('data-wc-csrf="token"')
        ->toContain('data-wc-module-url=')
        ->toContain('data-wc-update-uri=')
        ->toContain('wc:snapshot=')
        ->toContain('wc:effects=');
});

test('renames livewireScriptConfig to configured alias', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('window._wc =')
        ->not->toContain('window.livewireScriptConfig');
});

test('does not rename livewireScriptConfig when alias is null', function () {
    config([
        'wire-cloak.script_config_alias' => null,
        'wire-cloak.livewire_alias' => null,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)->toContain('window.livewireScriptConfig');
});

test('renames data attributes with configured prefix', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('data-wc-csrf="token"')
        ->toContain('data-wc-no-progress-bar')
        ->not->toContain('data-csrf="token"');
});

test('does not rename data attributes when prefix is null', function () {
    config([
        'wire-cloak.data_attribute_prefix' => null,
        'wire-cloak.livewire_alias' => null,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('data-csrf="token"')
        ->toContain('data-module-url=')
        ->toContain('data-update-uri=');
});

test('renames wire: prefix in attributes and css selectors', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('wc:id="abc123"')
        ->toContain('wc:snapshot=')
        ->toContain('wc:effects=')
        ->toContain('[wc\:loading]')
        ->not->toContain('wire:id=')
        ->not->toContain('wire:snapshot=')
        ->not->toContain('wire:effects=')
        ->not->toContain('[wire\:loading]');
});

test('does not rename wire: prefix when alias is null', function () {
    config([
        'wire-cloak.wire_prefix_alias' => null,
        'wire-cloak.livewire_alias' => null,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('wire:id="abc123"')
        ->toContain('wire:snapshot=')
        ->not->toContain('wc:id=');
});

test('wire:name is stripped before wire prefix rename', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->not->toContain('wire:name=')
        ->not->toContain('wc:name="counter"');
});

test('renames livewire identifiers in html response', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->not->toContain('livewire')
        ->not->toContain('Livewire')
        ->toContain('data-wc-style')
        ->toContain('--wc-progress-bar-color')
        ->toContain('dialog#wc-error');
});

test('does not rename livewire identifiers when alias is null', function () {
    config(['wire-cloak.livewire_alias' => null]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('data-livewire-style')
        ->toContain('--livewire-progress-bar-color')
        ->toContain('dialog#livewire-error');
});

test('html response contains zero livewire references with all defaults', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();

    // Case-insensitive check — no "livewire" anywhere in the output.
    expect(stripos($content, 'livewire'))->toBeFalse();
});

test('does nothing when all features disabled', function () {
    config([
        'wire-cloak.strip_html_comments' => false,
        'wire-cloak.strip_build_hash' => false,
        'wire-cloak.strip_wire_name' => false,
        'wire-cloak.strip_console_warnings' => false,
        'wire-cloak.script_config_alias' => null,
        'wire-cloak.data_attribute_prefix' => null,
        'wire-cloak.wire_prefix_alias' => null,
        'wire-cloak.livewire_alias' => null,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('<!-- Livewire Scripts -->')
        ->toContain('?id=cfc5c1ae')
        ->toContain('wire:name="counter"')
        ->toContain('window.livewireScriptConfig')
        ->toContain('data-csrf="token"')
        ->toContain('wire:id="abc123"')
        ->toContain('data-livewire-style')
        ->toContain('--livewire-progress-bar-color');
});

test('individual config toggles work independently', function () {
    config([
        'wire-cloak.strip_html_comments' => true,
        'wire-cloak.strip_build_hash' => false,
        'wire-cloak.strip_wire_name' => false,
        'wire-cloak.strip_console_warnings' => false,
        'wire-cloak.data_attribute_prefix' => null,
        'wire-cloak.wire_prefix_alias' => null,
        'wire-cloak.livewire_alias' => null,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->not->toContain('<!-- Livewire Scripts -->')
        ->toContain('?id=cfc5c1ae')
        ->toContain('wire:name="counter"');
});

test('does not transform json responses', function () {
    Route::get('/api/data', function () {
        return response()->json([
            'html' => '<!-- Livewire Scripts -->',
            'wire:name' => 'counter',
        ]);
    })->middleware('web');

    $response = $this->getJson('/api/data');

    $response->assertJson([
        'html' => '<!-- Livewire Scripts -->',
        'wire:name' => 'counter',
    ]);
});
