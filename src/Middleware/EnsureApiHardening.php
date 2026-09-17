<?php

namespace Bale\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiHardening
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = auth('api-token')->user();

        if ($token === null) {
            return $next($request);
        }

        if (! $token->isIpAllowed($request->ip()) || ! $token->isHostAllowed($request->getHost())) {
            abort(403, config('api.harden.deny_message', 'Forbidden.'));
        }

        return $next($request);
    }
}
