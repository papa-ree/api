<?php

namespace Bale\Api\Middleware;

use Bale\Api\Models\ApiToken;
use Bale\Api\Services\TokenManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function __construct(protected TokenManager $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $this->tokenFromRequest($request);

        $token = $plain === null ? null : $this->tokens->resolve($plain);

        if (! $token instanceof ApiToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        auth('api-token')->setUser($token);

        $this->tokens->touchUsage($token);

        return $next($request);
    }

    protected function tokenFromRequest(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (! is_string($authorization)) {
            return null;
        }

        if (preg_match('/Bearer\s+(\S+)/i', $authorization, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
