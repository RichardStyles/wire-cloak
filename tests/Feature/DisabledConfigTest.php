<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-page', function () {
        return response(
            '<!DOCTYPE html><html><head></head><body>'
            .'<!-- Livewire Scripts -->'
            .'<script src="/livewire-abcd1234/livewire.min.js?id=cfc5c1ae" data-csrf="token" data-module-url="/livewire-abcd1234" data-update-uri="/livewire-abcd1234/update"></script>'
            .'<script data-navigate-once="true">window.livewireScriptConfig = {"csrf":"token"};</script>'
            .'<div wire:id="abc123" wire:name="counter">Count: 0</div>'
            .'</body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    })->middleware('web');
});

test('globally disabled wire-cloak passes html through unchanged', function () {
    config(['wire-cloak.enabled' => false]);

    $response = $this->get('/test-page');

    $content = $response->getContent();
    expect($content)
        ->toContain('<!-- Livewire Scripts -->')
        ->toContain('?id=cfc5c1ae')
        ->toContain('wire:name="counter"')
        ->toContain('wire:id="abc123"')
        ->toContain('data-csrf="token"')
        ->toContain('data-module-url=')
        ->toContain('data-update-uri=')
        ->toContain('window.livewireScriptConfig');
});
