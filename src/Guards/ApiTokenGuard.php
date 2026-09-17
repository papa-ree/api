<?php

namespace Bale\Api\Guards;

use Bale\Api\Models\ApiToken;
use Bale\Api\Services\TokenManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class ApiTokenGuard implements Guard
{
    protected ?ApiToken $user = null;

    public function __construct(
        protected Request $request,
        protected TokenManager $tokens,
    ) {}

    public function user(): ?ApiToken
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $plain = $this->getTokenFromRequest();

        if ($plain === null) {
            return null;
        }

        return $this->user = $this->tokens->resolve($plain);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function id(): mixed
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    protected function getTokenFromRequest(): ?string
    {
        $authorization = $this->request->header('Authorization');

        if (! is_string($authorization)) {
            return null;
        }

        if (preg_match('/Bearer\s+(\S+)/i', $authorization, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
