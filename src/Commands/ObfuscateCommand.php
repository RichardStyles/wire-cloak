<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'wire-cloak:obfuscate')]
class ObfuscateCommand extends Command
{
    protected $signature = 'wire-cloak:obfuscate';

    protected $description = 'Publish and obfuscate Livewire assets to reduce passive fingerprinting';

    public function handle(): int
    {
        $assetPath = public_path('vendor/livewire');

        $this->publishAssetsIfNeeded($assetPath);

        if (! File::exists($assetPath.'/manifest.json')) {
            $this->components->error('Livewire assets could not be published.');

            return self::FAILURE;
        }

        $this->deleteSourceMaps($assetPath);
        $this->transformJsFiles($assetPath);
        $this->randomiseManifestHash($assetPath);

        $this->newLine();
        $this->components->info('Livewire assets obfuscated successfully.');

        $this->checkComposerPostUpdateHook();

        return self::SUCCESS;
    }

    private function publishAssetsIfNeeded(string $assetPath): void
    {
        if (File::exists($assetPath.'/manifest.json')) {
            $this->components->info('Published Livewire assets found.');

            return;
        }

        $this->components->info('Publishing Livewire assets...');

        Artisan::call('livewire:publish', ['--assets' => true]);
    }

    private function deleteSourceMaps(string $assetPath): void
    {
        /** @var list<string> $maps */
        $maps = File::glob($assetPath.'/*.map');

        if ($maps === []) {
            $this->components->twoColumnDetail('Source maps', 'None found');

            return;
        }

        foreach ($maps as $map) {
            File::delete($map);
        }

        $this->components->twoColumnDetail('Source maps', count($maps).' deleted');
    }

    /**
     * Apply all JS file transformations in a single loop.
     *
     * Each file is read once, all applicable transforms are applied
     * in sequence, and the result is written once. This reduces file
     * I/O from 5 read/write cycles per file to 1.
     *
     * Order matters — renameScriptConfig must run before scrubLivewireIdentifiers
     * so "livewireScriptConfig" is replaced precisely before the broad
     * "livewire" sweep catches remaining identifiers.
     */
    private function transformJsFiles(string $assetPath): void
    {
        /** @var list<string> $jsFiles */
        $jsFiles = File::glob($assetPath.'/*.js');

        /** @var string|null $scriptConfigAlias */
        $scriptConfigAlias = config('wire-cloak.script_config_alias');

        /** @var string|null $dataPrefix */
        $dataPrefix = config('wire-cloak.data_attribute_prefix');

        /** @var string|null $wireAlias */
        $wireAlias = config('wire-cloak.wire_prefix_alias');

        /** @var string|null $livewireAlias */
        $livewireAlias = config('wire-cloak.livewire_alias');

        $counts = [
            'source_maps' => 0,
            'script_config' => 0,
            'data_attrs' => 0,
            'wire_prefix' => 0,
            'identifiers' => 0,
        ];

        foreach ($jsFiles as $file) {
            $content = File::get($file);
            $original = $content;

            // 1. Strip sourceMappingURL references.
            $stripped = (string) preg_replace('/\/\/# sourceMappingURL=.*$/m', '', $content);

            if ($stripped !== $content) {
                $content = rtrim($stripped)."\n";
                $counts['source_maps']++;
            }

            // 2. Rename livewireScriptConfig (must precede broad livewire scrub).
            if (is_string($scriptConfigAlias) && $scriptConfigAlias !== '') {
                $replaced = str_replace('livewireScriptConfig', $scriptConfigAlias, $content);

                if ($replaced !== $content) {
                    $content = $replaced;
                    $counts['script_config']++;
                }
            }

            // 3. Rename data attributes.
            if (is_string($dataPrefix) && $dataPrefix !== '') {
                $replaced = str_replace(
                    ['data-csrf', 'data-update-uri', 'data-module-url', 'data-no-progress-bar'],
                    ["data-{$dataPrefix}-csrf", "data-{$dataPrefix}-update-uri", "data-{$dataPrefix}-module-url", "data-{$dataPrefix}-no-progress-bar"],
                    $content,
                );

                if ($replaced !== $content) {
                    $content = $replaced;
                    $counts['data_attrs']++;
                }
            }

            // 4. Rename wire: prefix.
            if (is_string($wireAlias) && $wireAlias !== '') {
                $replaced = str_replace(
                    ['wire:', 'wire\:'],
                    [$wireAlias.':', $wireAlias.'\:'],
                    $content,
                );

                if ($replaced !== $content) {
                    $content = $replaced;
                    $counts['wire_prefix']++;
                }
            }

            // 5. Scrub remaining livewire/Livewire identifiers (broad sweep, runs last).
            if (is_string($livewireAlias) && $livewireAlias !== '') {
                $replaced = str_replace(
                    ['Livewire', 'livewire'],
                    [ucfirst($livewireAlias), $livewireAlias],
                    $content,
                );

                if ($replaced !== $content) {
                    $content = $replaced;
                    $counts['identifiers']++;
                }
            }

            // Single write per file.
            if ($content !== $original) {
                File::put($file, $content);
            }
        }

        $this->components->twoColumnDetail('Source map references', $counts['source_maps'].' stripped');

        $this->reportStep('Script config rename', $scriptConfigAlias, $counts['script_config'], fn () => 'window.'.$scriptConfigAlias);

        $this->reportStep('Data attribute rename', $dataPrefix, $counts['data_attrs'], fn () => 'data-'.$dataPrefix.'-*');

        $this->reportStep('Wire prefix rename', $wireAlias, $counts['wire_prefix'], fn () => $wireAlias.':');

        $this->reportStep('Livewire identifiers', $livewireAlias, $counts['identifiers'], fn () => $livewireAlias.'/'.ucfirst((string) $livewireAlias));
    }

    /**
     * @param  \Closure(): string  $labelFn
     */
    private function reportStep(string $name, mixed $alias, int $count, \Closure $labelFn): void
    {
        if (! is_string($alias) || $alias === '') {
            $this->components->twoColumnDetail($name, 'Disabled');

            return;
        }

        $this->components->twoColumnDetail($name, $count.' JS files updated → '.$labelFn());
    }

    private function checkComposerPostUpdateHook(): void
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            return;
        }

        /** @var array{scripts?: array{post-update-cmd?: list<string>}} $composer */
        $composer = json_decode((string) file_get_contents($composerPath), true);

        $postUpdateScripts = $composer['scripts']['post-update-cmd'] ?? [];

        foreach ($postUpdateScripts as $script) {
            if (str_contains($script, 'wire-cloak:obfuscate')) {
                return;
            }
        }

        $this->newLine();
        $this->components->warn('wire-cloak:obfuscate is not in your composer.json post-update-cmd scripts.');

        if (! $this->components->confirm('Add it automatically?', true)) {
            return;
        }

        $composer['scripts']['post-update-cmd'] = [
            ...$postUpdateScripts,
            '@php artisan wire-cloak:obfuscate --ansi',
        ];

        file_put_contents(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );

        $this->components->info('Added wire-cloak:obfuscate to composer.json post-update-cmd.');
    }

    private function randomiseManifestHash(string $assetPath): void
    {
        $manifestPath = $assetPath.'/manifest.json';

        /** @var array<string, string> $manifest */
        $manifest = json_decode(File::get($manifestPath), true);

        $newHash = bin2hex(random_bytes(4));

        $manifest['/livewire.js'] = $newHash;

        File::put($manifestPath, (string) json_encode($manifest));

        $this->components->twoColumnDetail('Manifest hash', 'Randomised to '.$newHash);
    }
}
