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

    /**
     * Rename window.livewireScriptConfig to a non-descriptive alias.
     *
     * The obfuscate command applies the same rename to the published
     * JS files, so both the HTML injection and JS reader match.
     */
    protected function renameScriptConfig(string $html, string $alias): string
    {
        return str_replace('window.livewireScriptConfig', 'window.'.$alias, $html);
    }

    /**
     * Rename Livewire's data-* attributes on the script tag.
     *
     * Targets: data-csrf, data-update-uri, data-module-url, data-no-progress-bar.
     * These are used by Livewire's JS for endpoint discovery and are identifiable
     * markers. The obfuscate command applies the same rename to the published JS.
     */
    protected function renameDataAttributes(string $html, string $prefix): string
    {
        return str_replace(
            ['data-csrf', 'data-update-uri', 'data-module-url', 'data-no-progress-bar'],
            ["data-{$prefix}-csrf", "data-{$prefix}-update-uri", "data-{$prefix}-module-url", "data-{$prefix}-no-progress-bar"],
            $html,
        );
    }

    /**
     * Rename the wire: attribute prefix to a non-descriptive alias.
     *
     * Replaces "wire:" in HTML attributes (wire:id, wire:snapshot, wire:click,
     * etc.) and in CSS selectors where the colon is escaped (wire\:loading).
     * The obfuscate command applies the same rename to published JS files.
     */
    protected function renameWirePrefix(string $html, string $alias): string
    {
        return str_replace(
            ['wire:', 'wire\:'],
            [$alias.':', $alias.'\:'],
            $html,
        );
    }
}
