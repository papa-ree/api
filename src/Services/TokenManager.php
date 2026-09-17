<?php

namespace Bale\Api\Services;

use Bale\Api\Models\ApiToken;
use Illuminate\Support\Str;

class TokenManager
{
    /**
     * Issue a new API token.
     *
     * The plain token is returned exactly once (never stored). Only its hash
     * (config `api.token.hash_algo`) is persisted.
     *
     * @return array{plain: string, model: ApiToken}
     */
    public function issue(
        string $name,
        array $abilities = [],
        array $allowedIps = [],
        array $allowedHosts = [],
        ?string $expiresAt = null,
    ): array {
        $plain = config('api.token.prefix', 'rkc_').Str::random(40);

        $model = ApiToken::create([
            'name' => $name,
            'token' => $this->hashToken($plain),
            'abilities' => $abilities,
            'allowed_ips' => $allowedIps,
            'allowed_hosts' => $allowedHosts,
            'expires_at' => $expiresAt,
        ]);

        return ['plain' => $plain, 'model' => $model];
    }

    /**
     * Resolve a plain token to its active model (not revoked, not expired).
     */
    public function resolve(string $plain): ?ApiToken
    {
        return ApiToken::query()
            ->where('token', $this->hashToken($plain))
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->first();
    }

    /**
     * Revoke a token. Accepts the model or its primary key (UUID string).
     */
    public function revoke(ApiToken|string $token): bool
    {
        if (is_string($token)) {
            $token = ApiToken::query()->find($token);
        }

        if (! $token instanceof ApiToken || $token->revoked_at !== null) {
            return false;
        }

        $token->revoked_at = now();

        return $token->save();
    }

    public function touchUsage(ApiToken $token): void
    {
        $token->last_used_at = now();
        $token->save();
    }

    public function hashToken(string $plain): string
    {
        return hash(config('api.token.hash_algo', 'sha256'), $plain);
    }
}
