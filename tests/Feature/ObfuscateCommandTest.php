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

test('renames livewireScriptConfig in js files', function () {
    $this->artisan('livewire:publish', ['--assets' => true]);

    // Confirm the original string exists before obfuscation.
    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hadOriginal = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), 'livewireScriptConfig')) {
            $hadOriginal = true;

            break;
        }
    }

    expect($hadOriginal)->toBeTrue();

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    // No JS file should contain the original name.
    foreach (File::glob($this->assetPath.'/*.js') as $file) {
        $content = File::get($file);
        expect($content)->not->toContain('livewireScriptConfig');
        expect($content)->toContain('_wc');
    }
});

test('skips script config rename when alias is null', function () {
    config(['wire-cloak.script_config_alias' => null]);

    $this->artisan('livewire:publish', ['--assets' => true]);

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    // Original name should still be present.
    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hasOriginal = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), 'livewireScriptConfig')) {
            $hasOriginal = true;

            break;
        }
    }

    expect($hasOriginal)->toBeTrue();
});

test('renames data attributes in js files', function () {
    $this->artisan('livewire:publish', ['--assets' => true]);

    // Confirm original data attributes exist before obfuscation.
    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hadOriginal = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), 'data-csrf')) {
            $hadOriginal = true;

            break;
        }
    }

    expect($hadOriginal)->toBeTrue();

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    // No JS file should contain original data attribute names.
    foreach (File::glob($this->assetPath.'/*.js') as $file) {
        $content = File::get($file);
        expect($content)
            ->not->toContain('"data-csrf"')
            ->not->toContain('"data-update-uri"')
            ->not->toContain('"data-module-url"')
            ->toContain('data-wc-csrf')
            ->toContain('data-wc-update-uri')
            ->toContain('data-wc-module-url');
    }
});

test('skips data attribute rename when prefix is null', function () {
    config(['wire-cloak.data_attribute_prefix' => null]);

    $this->artisan('livewire:publish', ['--assets' => true]);

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hasOriginal = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), 'data-csrf')) {
            $hasOriginal = true;

            break;
        }
    }

    expect($hasOriginal)->toBeTrue();
});

test('renames wire: prefix in js files', function () {
    $this->artisan('livewire:publish', ['--assets' => true]);

    // Confirm the wire: prefix exists before obfuscation.
    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hadOriginal = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), 'wire:')) {
            $hadOriginal = true;

            break;
        }
    }

    expect($hadOriginal)->toBeTrue();

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    // No JS file should contain the original wire: prefix.
    foreach (File::glob($this->assetPath.'/*.js') as $file) {
        $content = File::get($file);
        expect($content)->not->toContain('wire:');
        expect($content)->toContain('wc:');
    }
});

test('skips wire prefix rename when alias is null', function () {
    config(['wire-cloak.wire_prefix_alias' => null]);

    $this->artisan('livewire:publish', ['--assets' => true]);

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful();

    $jsFiles = File::glob($this->assetPath.'/*.js');
    $hasOriginal = false;

    foreach ($jsFiles as $file) {
        if (str_contains(File::get($file), 'wire:')) {
            $hasOriginal = true;

            break;
        }
    }

    expect($hasOriginal)->toBeTrue();
});

test('offers to add wire-cloak:obfuscate to composer post-update-cmd when missing', function () {
    File::put(base_path('composer.json'), json_encode([
        'scripts' => [
            'post-update-cmd' => [
                '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
            ],
        ],
    ]));

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful()
        ->expectsOutputToContain('wire-cloak:obfuscate is not in your composer.json')
        ->expectsConfirmation('Add it automatically?', 'yes');

    /** @var array{scripts: array{post-update-cmd: list<string>}} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true);

    expect($composer['scripts']['post-update-cmd'])->toContain('@php artisan wire-cloak:obfuscate --ansi');

    File::delete(base_path('composer.json'));
});

test('does not modify composer.json when user declines', function () {
    File::put(base_path('composer.json'), json_encode([
        'scripts' => [
            'post-update-cmd' => [
                '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
            ],
        ],
    ]));

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful()
        ->expectsConfirmation('Add it automatically?', 'no');

    /** @var array{scripts: array{post-update-cmd: list<string>}} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true);

    expect($composer['scripts']['post-update-cmd'])->not->toContain('@php artisan wire-cloak:obfuscate --ansi');

    File::delete(base_path('composer.json'));
});

test('preserves existing post-update-cmd scripts when adding', function () {
    $existing = '@php artisan vendor:publish --tag=laravel-assets --ansi --force';

    File::put(base_path('composer.json'), json_encode([
        'scripts' => [
            'post-update-cmd' => [$existing],
        ],
    ]));

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful()
        ->expectsConfirmation('Add it automatically?', 'yes');

    /** @var array{scripts: array{post-update-cmd: list<string>}} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true);

    expect($composer['scripts']['post-update-cmd'])
        ->toContain($existing)
        ->toContain('@php artisan wire-cloak:obfuscate --ansi');

    File::delete(base_path('composer.json'));
});

test('does not prompt when wire-cloak:obfuscate is already in composer post-update-cmd', function () {
    File::put(base_path('composer.json'), json_encode([
        'scripts' => [
            'post-update-cmd' => [
                '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
                '@php artisan wire-cloak:obfuscate --ansi',
            ],
        ],
    ]));

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful()
        ->doesntExpectOutputToContain('wire-cloak:obfuscate is not in your composer.json');

    File::delete(base_path('composer.json'));
});

test('adds to empty post-update-cmd when composer.json has no scripts section', function () {
    File::put(base_path('composer.json'), json_encode([
        'name' => 'test/app',
    ]));

    $this->artisan('wire-cloak:obfuscate')
        ->assertSuccessful()
        ->expectsConfirmation('Add it automatically?', 'yes');

    /** @var array{scripts: array{post-update-cmd: list<string>}} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true);

    expect($composer['scripts']['post-update-cmd'])->toContain('@php artisan wire-cloak:obfuscate --ansi');

    File::delete(base_path('composer.json'));
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
