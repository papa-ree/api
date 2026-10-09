<?php

namespace Bale\Api\Services;

use Bale\Api\Models\ApiToken;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Activitylog\Models\Activity;

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

        $this->logEvent($model, 'created');

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

        $saved = $token->save();

        if ($saved) {
            $this->logEvent($token, 'revoked');
        }

        return $saved;
    }

    public function touchUsage(ApiToken $token): void
    {
        $token->last_used_at = now();
        $token->save();
    }

    /**
     * Rotate a token's secret in place.
     *
     * The same row keeps its id, abilities, and hardening rules — important
     * because consumers reference tokens by id (e.g. `wara_clients.api_token_id`).
     * Only the stored hash changes: the old plain token stops working the moment
     * this returns.
     *
     * @return string the new plain token (shown exactly once, never stored)
     *
     * @throws LogicException when the token is already revoked
     */
    public function regenerate(ApiToken $token): string
    {
        if ($token->isRevoked()) {
            throw new LogicException('Cannot regenerate a revoked token.');
        }

        $plain = config('api.token.prefix', 'rkc_').Str::random(40);

        $token->token = $this->hashToken($plain);
        $token->save();

        $this->logEvent($token, 'regenerated');

        return $plain;
    }

    public function hashToken(string $plain): string
    {
        return hash(config('api.token.hash_algo', 'sha256'), $plain);
    }

    /**
     * Catat aksi terhadap token ke activity log (PRD log audit).
     *
     * 🔴 Sengaja memakai model activity langsung, BUKAN trait `LogsActivity`.
     *
     * Trait itu menulis `attribute_changes` — dan di sini nilai yang berubah
     * adalah hash token. Nilai hash memang tidak sama dengan token asli, tapi
     * `wara_clients.api_token_id` ternormalisasi ke id token, jadi dif kolom
     * yang memuat hash tidak menambah nilai audit dan malah bikin bising.
     *
     * Yang ditulis: subjek (token), pelaku, event, dan properti ringan (nama,
     * id token, pelaku-user/system, tenant, IP, user-agent). Tidak pernah hash.
     *
     * Event yang dipakai: created, updated, regenerated, revoked.
     */
    public function logEvent(ApiToken $token, string $event): void
    {
        if (config('activitylog.enabled', true) === false) {
            return;
        }

        $activityModel = config('activitylog.activity_model', Activity::class);

        if (! Schema::hasTable((new $activityModel)->getTable())) {
            return;
        }

        $causer = auth()->user();
        $inConsole = app()->runningInConsole();
        $tenantName = $inConsole ? 'system' : (session('bale_active_slug') ?? 'landlord');
        $actor = $causer ? 'by user#'.$causer->getKey() : 'by system';
        $prefix = $tenantName === 'landlord' ? '' : $tenantName.' ';

        $activityModel::query()->create([
            'log_name' => 'api.token',
            'description' => sprintf('%s%s was %s (%s)', $prefix, class_basename(ApiToken::class), ucfirst($event), $actor),
            'event' => $event,
            'subject_type' => $token->getMorphClass(),
            'subject_id' => $token->getKey(),
            'causer_type' => $causer ? $causer->getMorphClass() : null,
            'causer_id' => $causer ? $causer->getKey() : null,
            'properties' => [
                'token_id' => $token->getKey(),
                'name' => $token->name,
                'logged_by' => $causer ? 'user' : 'system',
                'tenant_name' => $tenantName,
                'ip_address' => $inConsole ? null : request()->ip(),
                'user_agent' => $inConsole ? null : request()->userAgent(),
            ],
        ]);
    }
}
