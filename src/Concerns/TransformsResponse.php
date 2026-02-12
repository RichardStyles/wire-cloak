<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak\Concerns;

trait TransformsResponse
{
    /**
     * Strip identifiable patterns from HTML in a single regex pass.
     *
     * Combines four patterns into one preg_replace call to minimise
     * passes over the HTML string:
     *
     * 1. <!-- Livewire Scripts/Styles --> comments
     * 2. ?id=XXXXXXXX build hash from script src URLs
     * 3. wire:name="..." attributes
     * 4. <script>console.warn('Livewire: ...')</script> blocks
     *
     * @param  array{strip_html_comments: bool, strip_build_hash: bool, strip_wire_name: bool, strip_console_warnings: bool}  $toggles
     */
    protected function stripFingerprints(string $html, array $toggles): string
    {
        $patterns = [];
        $replacements = [];

        if ($toggles['strip_html_comments']) {
            $patterns[] = '/<!--\s*Livewire\s+(Scripts|Styles)\s*-->\s*\n?/';
            $replacements[] = '';
        }

        if ($toggles['strip_build_hash']) {
            $patterns[] = '/(<script[^>]+src="[^"]*livewire[^"]*)[?&]id=[a-f0-9]+"/';
            $replacements[] = '$1"';
        }

        if ($toggles['strip_wire_name']) {
            $patterns[] = '/\s+wire:name="[^"]*"/';
            $replacements[] = '';
        }

        if ($toggles['strip_console_warnings']) {
            $patterns[] = '/<script[^>]*>\s*\n?\s*console\.warn\([\'"]Livewire:.*?<\/script>\s*\n?/s';
            $replacements[] = '';
        }

        if ($patterns === []) {
            return $html;
        }

        return (string) preg_replace($patterns, $replacements, $html);
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

    /**
     * Rename remaining "livewire" / "Livewire" identifiers in HTML output.
     *
     * Targets PHP-emitted strings: data-livewire-style, --livewire-progress-bar-color,
     * #livewire-error, and any remaining "Livewire" / "livewire" text in inline
     * scripts or style blocks.
     */
    protected function renameLivewireIdentifiers(string $html, string $alias): string
    {
        return str_replace(
            ['Livewire', 'livewire'],
            [ucfirst($alias), $alias],
            $html,
        );
    }
}
