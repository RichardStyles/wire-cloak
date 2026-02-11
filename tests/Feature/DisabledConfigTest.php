<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

beforeEach(function () {
    Route::get('/test-page', function () {
        return response(
            '<!DOCTYPE html><html><head></head><body>'
            .'<!-- Livewire Scripts -->'
            .'<script src="/livewire-abcd1234/livewire.min.js?id=cfc5c1ae"></script>'
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
        ->toContain('wire:name="counter"');
});

test('globally disabled wire-cloak passes source maps through', function () {
    config(['wire-cloak.enabled' => false]);

    $mapPath = EndpointResolver::mapPath(csp: false);

    $response = $this->get($mapPath);

    expect($response->getStatusCode())->not->toBe(404);
});
