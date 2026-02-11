<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-page', function () {
        return response(
            '<!DOCTYPE html><html><head></head><body>'
            .'<!-- Livewire Styles -->'
            .'<style>[wire\:loading]{display:none;}</style>'
            .'<!-- Livewire Scripts -->'
            .'<script src="/livewire-abcd1234/livewire.min.js?id=cfc5c1ae" data-csrf="token" data-module-url="/livewire-abcd1234" data-update-uri="/livewire-abcd1234/update"></script>'
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
    expect($content)->toContain('livewire.min.js"');
});

test('strips wire:name attributes', function () {
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

test('preserves functional attributes', function () {
    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('wire:id="abc123"')
        ->toContain('data-csrf="token"')
        ->toContain('data-module-url=')
        ->toContain('data-update-uri=')
        ->toContain('wire:snapshot=')
        ->toContain('wire:effects=');
});

test('does nothing when all features disabled', function () {
    config([
        'wire-cloak.strip_html_comments' => false,
        'wire-cloak.strip_build_hash' => false,
        'wire-cloak.strip_wire_name' => false,
        'wire-cloak.strip_console_warnings' => false,
    ]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('<!-- Livewire Scripts -->')
        ->toContain('?id=cfc5c1ae')
        ->toContain('wire:name="counter"');
});

test('individual config toggles work independently', function () {
    config([
        'wire-cloak.strip_html_comments' => true,
        'wire-cloak.strip_build_hash' => false,
        'wire-cloak.strip_wire_name' => false,
        'wire-cloak.strip_console_warnings' => false,
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
