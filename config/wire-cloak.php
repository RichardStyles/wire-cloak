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
    | Block Source Maps
    |--------------------------------------------------------------------------
    |
    | Return 404 for requests to Livewire .js.map files served via PHP
    | routes (the default). Source maps are only used by browser DevTools
    | for debugging and expose the full internal Livewire file structure.
    |
    | Note: If you have published Livewire assets (artisan vendor:publish
    | --tag=livewire:assets), the .map files in public/vendor/livewire/
    | are served directly by your web server and bypass PHP entirely.
    | Delete them manually or exclude them in your web server config.
    |
    */

    'block_source_maps' => env('WIRE_CLOAK_BLOCK_MAPS', true),

];
