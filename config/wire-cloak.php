<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Cloaking
    |--------------------------------------------------------------------------
    |
    | Master toggle for all fingerprint cloaking transformations.
    |
    */

    'enabled' => env('WIRE_CLOAK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Strip HTML Comments
    |--------------------------------------------------------------------------
    |
    | Remove "<!-- Livewire Scripts -->" and "<!-- Livewire Styles -->"
    | comments injected when app.debug is true.
    |
    */

    'strip_html_comments' => true,

    /*
    |--------------------------------------------------------------------------
    | Strip Build Hash
    |--------------------------------------------------------------------------
    |
    | Remove the ?id=XXXXXXXX query parameter from Livewire's script src
    | URL. This hash is deterministic per Livewire release (from the build
    | manifest) and can be used to map installations to specific versions
    | via lookup tables.
    |
    */

    'strip_build_hash' => true,

    /*
    |--------------------------------------------------------------------------
    | Strip wire:name Attributes
    |--------------------------------------------------------------------------
    |
    | Remove wire:name="component-name" from component root elements.
    | This attribute leaks component class structure (e.g. "pages.settings")
    | but is NOT used by Livewire's JavaScript runtime — it uses wire:id
    | for component identification instead.
    |
    */

    'strip_wire_name' => true,

    /*
    |--------------------------------------------------------------------------
    | Strip Console Warnings
    |--------------------------------------------------------------------------
    |
    | Remove <script> tags containing Livewire console.warn messages about
    | published assets being out of date.
    |
    */

    'strip_console_warnings' => true,

    /*
    |--------------------------------------------------------------------------
    | Rename Script Config Variable
    |--------------------------------------------------------------------------
    |
    | Rename window.livewireScriptConfig to a shorter, non-descriptive alias
    | in the HTML response. The obfuscate command applies the same rename to
    | the published JS files so both sides match.
    |
    | Set to null to disable renaming.
    |
    */

    'script_config_alias' => '_wc',

    /*
    |--------------------------------------------------------------------------
    | Rename Data Attributes
    |--------------------------------------------------------------------------
    |
    | Livewire's script tag uses data-csrf, data-update-uri, data-module-url,
    | and data-no-progress-bar for endpoint discovery. These are identifiable
    | markers. Set a short prefix to rename them (e.g. "data-csrf" becomes
    | "data-wc-csrf"). The obfuscate command applies the same rename to
    | published JS files.
    |
    | Set to null to disable renaming.
    |
    */

    'data_attribute_prefix' => 'wc',

    /*
    |--------------------------------------------------------------------------
    | Rename wire: Attribute Prefix
    |--------------------------------------------------------------------------
    |
    | Every Livewire directive uses the "wire:" attribute prefix (wire:id,
    | wire:snapshot, wire:click, wire:model, etc.). Scanners pattern-match
    | on this prefix. Renaming it to a short alias (e.g. "wc:") changes
    | all wire: attributes in the HTML and the corresponding JS selectors
    | in published assets.
    |
    | The inline CSS selectors emitted by Livewire's @livewireStyles are
    | also updated by the middleware.
    |
    | Set to null to disable renaming.
    |
    */

    'wire_prefix_alias' => 'wc',

    /*
    |--------------------------------------------------------------------------
    | Scrub Livewire Identifiers
    |--------------------------------------------------------------------------
    |
    | Livewire's JS and HTML contain dozens of identifiable strings — event
    | names (livewire:init), DOM properties (__livewire), CSS variables
    | (--livewire-progress-bar-color), the window.Livewire global, error
    | messages, and more.
    |
    | Setting an alias here replaces "livewire" and "Livewire" throughout
    | the published JS files (obfuscate command) and the HTML response
    | (middleware) so a simple "contains livewire" check returns nothing.
    |
    | HTTP headers (X-Livewire) are NOT renamed — they only appear on POST
    | requests to the update endpoint, invisible to passive GET scanners.
    |
    | Set to null to disable.
    |
    */

    'livewire_alias' => 'wc',

];
