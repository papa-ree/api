<?php

namespace Bale\Api\Models;

use Bale\Api\Services\TokenManager;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\IpUtils;

class ApiToken extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasUuids;

    protected $table = 'api_tokens';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'allowed_ips',
        'allowed_hosts',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'allowed_ips' => 'array',
        'allowed_hosts' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function isIpAllowed(?string $ip): bool
    {
        if (config('api.harden.enabled') === false) {
            return true;
        }

        $rules = $this->allowed_ips ?? [];

        if (empty($rules)) {
            return true;
        }

        if ($ip === null) {
            return false;
        }

        foreach ($rules as $rule) {
            if (IpUtils::checkIp($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    public function isHostAllowed(?string $host): bool
    {
        $rules = $this->allowed_hosts ?? [];

        if (empty($rules)) {
            return true;
        }

        $host = strtolower(trim((string) $host));

        if ($host === '') {
            return false;
        }

        foreach ($rules as $rule) {
            $rule = strtolower(trim($rule));

            if (str_starts_with($rule, '*.')) {
                $suffix = substr($rule, 1);

                if ($host === ltrim($suffix, '.') || str_ends_with($host, $suffix)) {
                    return true;
                }
            } elseif ($host === $rule) {
                return true;
            }
        }

        return false;
    }

    public function revoke(): bool
    {
        return app(TokenManager::class)->revoke($this);
    }
}
