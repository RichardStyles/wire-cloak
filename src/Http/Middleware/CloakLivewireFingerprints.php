<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use RichardStyles\WireCloak\Concerns\TransformsResponse;
use Symfony\Component\HttpFoundation\Response;

class CloakLivewireFingerprints
{
    use TransformsResponse;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        /** @var array{enabled?: bool, strip_html_comments?: bool, strip_build_hash?: bool, strip_wire_name?: bool, strip_console_warnings?: bool, script_config_alias?: string|null, data_attribute_prefix?: string|null, wire_prefix_alias?: string|null, livewire_alias?: string|null} $cfg */
        $cfg = config('wire-cloak');

        if (! ($cfg['enabled'] ?? true)) {
            return $response;
        }

        if (! $response instanceof LaravelResponse) {
            return $response;
        }

        if (! $this->isHtmlResponse($response)) {
            return $response;
        }

        $content = (string) $response->getContent();

        if ($content === '') {
            return $response;
        }

        // Single regex pass for all strip operations.
        $content = $this->stripFingerprints($content, [
            'strip_html_comments' => $cfg['strip_html_comments'] ?? true,
            'strip_build_hash' => $cfg['strip_build_hash'] ?? true,
            'strip_wire_name' => $cfg['strip_wire_name'] ?? true,
            'strip_console_warnings' => $cfg['strip_console_warnings'] ?? true,
        ]);

        $alias = $cfg['script_config_alias'] ?? null;

        if (is_string($alias) && $alias !== '') {
            $content = $this->renameScriptConfig($content, $alias);
        }

        $dataPrefix = $cfg['data_attribute_prefix'] ?? null;

        if (is_string($dataPrefix) && $dataPrefix !== '') {
            $content = $this->renameDataAttributes($content, $dataPrefix);
        }

        $wireAlias = $cfg['wire_prefix_alias'] ?? null;

        if (is_string($wireAlias) && $wireAlias !== '') {
            $content = $this->renameWirePrefix($content, $wireAlias);
        }

        $livewireAlias = $cfg['livewire_alias'] ?? null;

        if (is_string($livewireAlias) && $livewireAlias !== '') {
            $content = $this->renameLivewireIdentifiers($content, $livewireAlias);
        }

        $response->setContent($content);

        $response->headers->remove('Content-Length');

        return $response;
    }

    private function isHtmlResponse(LaravelResponse $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return str_contains((string) $contentType, 'text/html');
    }
}
