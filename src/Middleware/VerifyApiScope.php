<?php

namespace Bale\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan scope pada token API.
 *
 * Dipakai sebagai middleware route `scope:rakaca.form.read` (alias `api.ability`
 * dipertahankan untuk kompatibilitas). Bila route diberi beberapa scope,
 * token cukup memiliki salah satunya.
 */
class VerifyApiScope
{
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $token = auth('api-token')->user();

        if ($token === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        foreach ($scopes as $scope) {
            if ($token->hasAbility($scope)) {
                return $next($request);
            }
        }

        abort(403, config('api.harden.deny_message', 'Forbidden.'));
    }
}
