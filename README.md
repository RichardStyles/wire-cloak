# Wire Cloak

A Laravel package that reduces Livewire's passive fingerprinting surface. Strips identifiable markers from HTML responses that scanners use to detect Livewire installations and determine their version.

Companion to [Wire Shield](https://github.com/richardstyles/wire-shield) — Wire Shield detects active exploit payloads, Wire Cloak hardens against passive reconnaissance.

## Installation

```bash
composer require richardstyles/wire-cloak
```

The package auto-discovers and registers its middleware into the `web` middleware group. Zero configuration required.

To publish the config file:

```bash
php artisan vendor:publish --tag=wire-cloak-config
```

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Livewire 3 or 4

## How It Works

Passive scanners identify Livewire by probing for known markers in HTML responses — version hashes in script URLs, debug comments, component names in attributes, and recognisable attribute prefixes.

Wire Cloak registers a single response middleware (`CloakLivewireFingerprints`) in the `web` group. After Livewire has rendered the page, it strips or renames identifiable patterns in the HTML before the response reaches the browser. You write standard Blade with normal `wire:` directives — the middleware transforms the output on the way out. Each transformation can be independently toggled.

## What It Does

| Vector | What Scanners See | What Wire Cloak Does | Config |
|--------|------------------|---------------------|--------|
| HTML comments | `<!-- Livewire Scripts -->` | Stripped | `strip_html_comments` |
| Build hash | `?id=cfc5c1ae` on script src | Stripped | `strip_build_hash` |
| Component names | `wire:name="counter"` | Stripped | `strip_wire_name` |
| Console warnings | `console.warn('Livewire: ...')` | Stripped | `strip_console_warnings` |
| Script config | `window.livewireScriptConfig` | Renamed → `window._wc` | `script_config_alias` |
| Data attributes | `data-csrf`, `data-update-uri`, `data-module-url` | Renamed → `data-wc-*` | `data_attribute_prefix` |
| Wire prefix | `wire:id`, `wire:snapshot`, `wire:click`, etc. | Renamed → `wc:*` | `wire_prefix_alias` |
| CSS selectors | `[wire\:loading]` | Renamed → `[wc\:loading]` | `wire_prefix_alias` |

### Stripping vs Renaming

Some markers (comments, build hash, `wire:name`, console warnings) serve no runtime purpose and are simply removed.

Other markers (`wire:id`, `wire:snapshot`, `data-csrf`, etc.) are required for Livewire to function. These are **renamed** — the middleware renames them in the HTML response, and the `wire-cloak:obfuscate` command applies the same renames to the published JS files so both sides match. Scanners looking for `wire:` or `data-csrf` patterns find nothing.

### Development workflow

You always write standard Blade with normal `wire:` directives. The middleware only transforms the HTML response — your templates, components, and development experience are unchanged. Set `enabled` to `false` or any alias to `null` to disable individual renames.

## Asset Obfuscation

The response middleware handles HTML output, but Livewire's JavaScript files contain additional fingerprinting markers — source map references, `.map` files exposing internal structure, and a deterministic manifest hash that maps to specific versions.

The `wire-cloak:obfuscate` command publishes Livewire's static assets and scrubs them:

```bash
php artisan wire-cloak:obfuscate
```

This will:

1. **Publish Livewire assets** to `public/vendor/livewire/` if not already present
2. **Delete `.map` files** — source maps expose 60+ internal file paths
3. **Strip `//# sourceMappingURL`** references from JS files
4. **Rename `livewireScriptConfig`** in JS files to match `script_config_alias`
5. **Rename data attributes** (`data-csrf` → `data-wc-csrf`, etc.) to match `data_attribute_prefix`
6. **Rename `wire:` prefix** in JS files to match `wire_prefix_alias`
7. **Randomise the manifest hash** — replaces the deterministic build hash with a random value

Once published, Livewire automatically serves the scrubbed copies instead of the originals. Run the command after each `composer update` that bumps Livewire.

### Source maps on unpublished assets

By default Livewire serves JS and source maps via PHP routes from `vendor/`. The response middleware already strips the `?id=` hash from HTML, but the source map files themselves are served outside the middleware pipeline. If you don't want to publish assets, block maps at your web server instead:

**Nginx:**

```nginx
location ~* livewire.*\.js\.map$ {
    return 404;
}
```

**Apache (.htaccess):**

```apache
<FilesMatch "\.js\.map$">
    Require all denied
</FilesMatch>
```

## Configuration

```php
return [
    'enabled' => true,                  // Master switch
    'strip_html_comments' => true,      // <!-- Livewire Scripts/Styles -->
    'strip_build_hash' => true,         // ?id=XXXXXXXX from script src
    'strip_wire_name' => true,          // wire:name="..." attributes
    'strip_console_warnings' => true,   // console.warn('Livewire: ...')
    'script_config_alias' => '_wc',     // Rename window.livewireScriptConfig (null to disable)
    'data_attribute_prefix' => 'wc',    // Rename data-csrf → data-wc-csrf (null to disable)
    'wire_prefix_alias' => 'wc',        // Rename wire: → wc: (null to disable)
];
```

All features are enabled by default. Environment variable override:

```env
WIRE_CLOAK_ENABLED=true
```

## Testing & Quality

```bash
# Run tests
vendor/bin/pest

# Static analysis (level 8)
vendor/bin/phpstan analyse

# Code formatting
vendor/bin/pint
```

All source files use `declare(strict_types=1)`. PHPStan is configured at level 8 with Larastan.

## License

MIT
