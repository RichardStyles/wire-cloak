<?php

declare(strict_types=1);

namespace RichardStyles\WireCloak\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSourceMaps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('wire-cloak.enabled', true)) {
            return $next($request);
        }

        if (! config('wire-cloak.block_source_maps', true)) {
            return $next($request);
        }

        if ($request->isMethod('GET') && $this->isLivewireSourceMapRequest($request)) {
            abort(404);
        }

        return $next($request);
    }

    private function isLivewireSourceMapRequest(Request $request): bool
    {
        $path = $request->path();

        return str_contains($path, 'livewire') && str_ends_with($path, '.js.map');
    }
}
