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

        if (! config('wire-cloak.enabled', true)) {
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

        if (config('wire-cloak.strip_html_comments', true)) {
            $content = $this->stripHtmlComments($content);
        }

        if (config('wire-cloak.strip_build_hash', true)) {
            $content = $this->stripBuildHash($content);
        }

        if (config('wire-cloak.strip_wire_name', true)) {
            $content = $this->stripWireName($content);
        }

        if (config('wire-cloak.strip_console_warnings', true)) {
            $content = $this->stripConsoleWarnings($content);
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
