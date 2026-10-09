<?php

namespace Bale\Api\Livewire\Pages\Token;

use Bale\Api\Models\ApiToken;
use Bale\Api\Services\ApiScopeRegistry;
use Bale\Api\Services\TokenManager;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core::layouts.app')]
#[Title('API Token')]
class Form extends Component
{
    public ?string $tokenId = null;

    public string $name = '';

    public array $abilities = [];

    public array $abilityGroups = [];

    public string $allowedIps = '';

    public string $allowedHosts = '';

    public ?string $expiresAt = null;

    public bool $revoked = false;

    public ?string $plainToken = null;

    public function mount(?string $token = null, ?string $id = null): void
    {
        $resolvedId = $token ?? $id;

        $this->abilityGroups = app(ApiScopeRegistry::class)->grouped();

        if ($resolvedId) {
            $this->tokenId = $resolvedId;
            $tokenModel = ApiToken::query()->findOrFail($resolvedId);

            $this->name = $tokenModel->name;
            $this->abilities = $tokenModel->abilities ?? [];
            $this->allowedIps = $this->formatLines($tokenModel->allowed_ips ?? []);
            $this->allowedHosts = $this->formatLines($tokenModel->allowed_hosts ?? []);
            $this->expiresAt = $tokenModel->expires_at?->toDateString();
            $this->revoked = $tokenModel->isRevoked();
        }
    }

    public function rules(): array
    {
        $flat = app(ApiScopeRegistry::class)->flat();

        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in($flat)],
            'allowedIps' => ['nullable', 'string', 'max:2000'],
            'allowedHosts' => ['nullable', 'string', 'max:2000'],
            'expiresAt' => ['nullable', 'date'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $allowedIps = $this->parseLines($this->allowedIps);
        $allowedHosts = $this->parseLines($this->allowedHosts);

        if ($this->tokenId) {
            $token = ApiToken::query()->findOrFail($this->tokenId);

            $token->update([
                'name' => $this->name,
                'abilities' => array_values($this->abilities),
                'allowed_ips' => $allowedIps,
                'allowed_hosts' => $allowedHosts,
                'expires_at' => $this->expiresAt ?? null,
                'revoked_at' => $this->revoked ? now() : null,
            ]);

            app(TokenManager::class)->logEvent($token, 'updated');

            $this->dispatch('toast', message: 'Token updated successfully.', type: 'success');
            $this->redirectRoute('api.tokens.index', navigate: true);

            return;
        }

        $result = app(TokenManager::class)->issue(
            name: $this->name,
            abilities: array_values($this->abilities),
            allowedIps: $allowedIps,
            allowedHosts: $allowedHosts,
            expiresAt: $this->expiresAt ?? null,
        );

        $this->plainToken = $result['plain'];
        $this->tokenId = $result['model']->id;
        $this->revoked = false;

        $this->reset('name', 'abilities', 'allowedIps', 'allowedHosts', 'expiresAt');
        $this->dispatch('toast', message: 'Token created. Copy the plain token now — it will not be shown again.', type: 'success');
    }

    public function regenerateToken(): void
    {
        if (! $this->tokenId) {
            return;
        }

        $token = ApiToken::query()->findOrFail($this->tokenId);
        $plain = app(TokenManager::class)->regenerate($token);

        $this->plainToken = $plain;
        $this->revoked = false;

        $this->dispatch('toast', message: 'Token regenerated. Copy the new plain token now — the old one is no longer valid.', type: 'success');
    }

    public function render()
    {
        return view('api::livewire.pages.token.form');
    }

    protected function parseLines(string $value): array
    {
        $lines = preg_split('/[\r\n,]+/', trim($value)) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $lines))));
    }

    protected function formatLines(array $values): string
    {
        return implode(PHP_EOL, $values);
    }
}
