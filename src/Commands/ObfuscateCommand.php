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
        $this->stripSourceMapReferences($assetPath);
        $this->randomiseManifestHash($assetPath);

        $this->newLine();
        $this->components->info('Livewire assets obfuscated successfully.');

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

    private function stripSourceMapReferences(string $assetPath): void
    {
        /** @var list<string> $jsFiles */
        $jsFiles = File::glob($assetPath.'/*.js');

        $stripped = 0;

        foreach ($jsFiles as $file) {
            $content = File::get($file);
            $updated = (string) preg_replace('/\/\/# sourceMappingURL=.*$/m', '', $content);

            if ($updated !== $content) {
                File::put($file, rtrim($updated)."\n");
                $stripped++;
            }
        }

        $this->components->twoColumnDetail('Source map references', $stripped.' stripped from JS files');
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
