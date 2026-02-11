<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->assetPath = public_path('vendor/livewire');

    // Clean up any previous test runs.
    if (File::isDirectory($this->assetPath)) {
        File::deleteDirectory($this->assetPath);
    }
});

afterEach(function () {
    if (File::isDirectory($this->assetPath)) {
        File::deleteDirectory($this->assetPath);
    }
});

test('publishes assets when not present and obfuscates them', function () {
    expect(File::exists($this->assetPath.'/manifest.json'))->toBeFalse();

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    expect(File::exists($this->assetPath.'/manifest.json'))->toBeTrue();
});

test('deletes source map files', function () {
    $this->artisan('livewire:publish', ['--assets' => true]);

    // Confirm maps exist before obfuscation.
    $mapsBefore = File::glob($this->assetPath.'/*.map');
    expect($mapsBefore)->not->toBeEmpty();

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    $mapsAfter = File::glob($this->assetPath.'/*.map');
    expect($mapsAfter)->toBeEmpty();
});

test('strips sourceMappingURL references from js files', function () {
    $this->artisan('livewire:publish', ['--assets' => true]);

    // Confirm sourceMappingURL exists in at least one JS file before.
    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hadReference = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), '//# sourceMappingURL=')) {
            $hadReference = true;

            break;
        }
    }

    expect($hadReference)->toBeTrue();

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    // No JS file should contain sourceMappingURL.
    foreach (File::glob($this->assetPath.'/*.js') as $file) {
        expect(File::get($file))->not->toContain('//# sourceMappingURL=');
    }
});

test('randomises the manifest hash', function () {
    $this->artisan('livewire:publish', ['--assets' => true]);

    $originalManifest = json_decode(File::get($this->assetPath.'/manifest.json'), true);
    $originalHash = $originalManifest['/livewire.js'];

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    $updatedManifest = json_decode(File::get($this->assetPath.'/manifest.json'), true);
    $updatedHash = $updatedManifest['/livewire.js'];

    expect($updatedHash)
        ->not->toBe($originalHash)
        ->toMatch('/^[a-f0-9]{8}$/');
});

test('is idempotent', function () {
    $this->artisan('wire-cloak:obfuscate')->assertSuccessful();
    $this->artisan('wire-cloak:obfuscate')->assertSuccessful();

    // No maps, no sourceMappingURL, valid manifest.
    expect(File::glob($this->assetPath.'/*.map'))->toBeEmpty();

    foreach (File::glob($this->assetPath.'/*.js') as $file) {
        expect(File::get($file))->not->toContain('//# sourceMappingURL=');
    }

    $manifest = json_decode(File::get($this->assetPath.'/manifest.json'), true);
    expect($manifest['/livewire.js'])->toMatch('/^[a-f0-9]{8}$/');
});
