<x-core::page-header
    gradient
    :title="__('API Tokens')"
    :subtitle="__('Manage API tokens for external integrations')"
>
    <x-slot name="action">
        @can('api-token.create')
            <x-core::button link href="{{ route('api.tokens.create') }}" label="{{ __('New Token') }}">
                <x-slot name="icon">
                    <x-lucide-key-round class="w-5 h-5" />
                </x-slot>
            </x-core::button>
        @endcan
    </x-slot>
</x-core::page-header>