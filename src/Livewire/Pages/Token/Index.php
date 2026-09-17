<?php

namespace Bale\Api\Livewire\Pages\Token;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core::layouts.app')]
#[Title('API Tokens')]
class Index extends Component
{
    public function render()
    {
        return view('api::livewire.pages.token.index');
    }
}
