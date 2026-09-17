<?php

namespace Bale\Api\Livewire\Pages\Token\Section;

use Bale\Api\Models\ApiToken;
use Livewire\Attributes\On;
use Livewire\Component;

class TokenTable extends Component
{
    public function render()
    {
        return view('api::livewire.pages.token.section.token-table');
    }

    #[On('revokeToken')]
    public function revokeToken(string $id): void
    {
        abort_unless(auth()->user()->can('api-token.revoke'), 403);

        $token = ApiToken::query()->find($id);

        if (! $token || $token->isRevoked()) {
            $this->dispatch('toast', message: 'Token not found or already revoked.', type: 'error');

            return;
        }

        $token->revoke();

        $this->dispatch('toast', message: 'Token revoked successfully.', type: 'success');
        $this->dispatch('refresh');
    }
}
