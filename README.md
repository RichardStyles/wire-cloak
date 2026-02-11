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

Passive scanners identify Livewire by probing for known markers in HTML responses — version hashes in script URLs, debug comments, and component names in attributes.

Wire Cloak registers a single response middleware (`CloakLivewireFingerprints`) in the `web` group. After Livewire has rendered the page, it strips identifiable patterns from the HTML before the response reaches the browser. Each transformation targets markers that are not required for Livewire to function and can be independently toggled.

## What It Strips

| Vector | What Scanners See | Config |
|--------|------------------|--------|
| HTML comments | `<!-- Livewire Scripts -->`, `<!-- Livewire Styles -->` | `strip_html_comments` |
| Build hash | `?id=cfc5c1ae` on script src | `strip_build_hash` |
| Component names | `wire:name="counter"` | `strip_wire_name` |
| Console warnings | `console.warn('Livewire: ...')` | `strip_console_warnings` |

### Why these are safe to remove

- **HTML comments** — Debug-only cosmetic markers. No functional purpose.
- **Build hash** — The `?id=` parameter is derived from Livewire's `dist/manifest.json` and is deterministic per release. Scanners maintain lookup tables mapping hashes to versions. Removing it affects browser caching but not functionality.
- **`wire:name`** — Leaks component class names (e.g. `pages.settings.profile`) but is not read by Livewire's JavaScript runtime — it uses `wire:id` for component identification.
- **Console warnings** — Informational messages about published assets being out of date.

### What is NOT stripped

These markers are required for Livewire to function and cannot be safely removed:

- `window.livewireScriptConfig` — The JS hardcodes this variable name
- `data-update-uri` / `data-module-url` — Script tag attributes the JS reads for endpoint discovery
- `wire:snapshot` / `wire:effects` / `wire:id` — Core component state attributes
- CSS selectors (`[wire\:loading]`, etc.) — Required for loading states and error dialogs

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
4. **Randomise the manifest hash** — replaces the deterministic build hash with a random value

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
    'enabled' => true,                // Master switch
    'strip_html_comments' => true,    // <!-- Livewire Scripts/Styles -->
    'strip_build_hash' => true,       // ?id=XXXXXXXX from script src
    'strip_wire_name' => true,        // wire:name="..." attributes
    'strip_console_warnings' => true, // console.warn('Livewire: ...')
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
