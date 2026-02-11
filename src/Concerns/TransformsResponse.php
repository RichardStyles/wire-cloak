<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak\Concerns;

trait TransformsResponse
{
    /**
     * Strip Livewire HTML comments.
     *
     * Targets: <!-- Livewire Scripts --> and <!-- Livewire Styles -->
     */
    protected function stripHtmlComments(string $html): string
    {
        return (string) preg_replace(
            '/<!--\s*Livewire\s+(Scripts|Styles)\s*-->\s*\n?/',
            '',
            $html,
        );
    }

    /**
     * Strip the build manifest hash from Livewire script URLs.
     *
     * Targets: ?id=cfc5c1ae or &id=cfc5c1ae in script src attributes
     * pointing to a livewire path. The hash is deterministic per release
     * and can be mapped back to a specific Livewire version.
     */
    protected function stripBuildHash(string $html): string
    {
        return (string) preg_replace(
            '/(<script[^>]+src="[^"]*livewire[^"]*)[?&]id=[a-f0-9]+"/',
            '$1"',
            $html,
        );
    }

    /**
     * Strip wire:name attributes from component root elements.
     *
     * These leak component class names (e.g. "pages.settings.profile")
     * but are NOT used by Livewire's JavaScript runtime.
     */
    protected function stripWireName(string $html): string
    {
        return (string) preg_replace(
            '/\s+wire:name="[^"]*"/',
            '',
            $html,
        );
    }

    /**
     * Strip Livewire console.warn script tags.
     *
     * Targets: <script>console.warn('Livewire: ...')</script> blocks
     * injected when published assets are out of date.
     */
    protected function stripConsoleWarnings(string $html): string
    {
        return (string) preg_replace(
            '/<script[^>]*>\s*\n?\s*console\.warn\([\'"]Livewire:.*?<\/script>\s*\n?/s',
            '',
            $html,
        );
    }
}
