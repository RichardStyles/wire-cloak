<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

test('blocks source map requests', function () {
    $mapPath = EndpointResolver::mapPath(csp: false);

    $response = $this->get($mapPath);

    $response->assertNotFound();
});

test('blocks csp source map requests', function () {
    $mapPath = EndpointResolver::mapPath(csp: true);

    $response = $this->get($mapPath);

    $response->assertNotFound();
});

test('allows source maps when disabled', function () {
    config(['wire-cloak.block_source_maps' => false]);

    $mapPath = EndpointResolver::mapPath(csp: false);

    $response = $this->get($mapPath);

    expect($response->getStatusCode())->not->toBe(404);
});

test('does not block non-livewire js map files', function () {
    Route::get('/assets/app.js.map', function () {
        return response('source map content', 200);
    })->middleware('web');

    $response = $this->get('/assets/app.js.map');

    $response->assertOk();
});
