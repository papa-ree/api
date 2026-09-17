<tr wire:key="token-row-{{ $record->getKey() }}"
    class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors duration-150">

    {{-- Name --}}
    <td class="px-4 py-3.5 w-full max-w-0 sm:max-w-none sm:w-auto">
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ $record->name }}
        </div>
    </td>

    {{-- Permissions --}}
    <td class="px-4 py-3.5 hidden xl:table-cell">
        <div class="flex flex-wrap gap-1">
            @forelse($record->abilities ?? [] as $ability)
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/40">
                    {{ $ability }}
                </span>
            @empty
                <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
            @endforelse
        </div>
    </td>

    {{-- Last Used --}}
    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $record->last_used_at ?? '—' }}
        </div>
    </td>

    {{-- Expires --}}
    <td class="px-4 py-3.5 hidden lg:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $record->expires_at ?? '—' }}
        </div>
    </td>

    {{-- Status --}}
    <td class="px-4 py-3.5 hidden sm:table-cell">
        @if($record->isRevoked())
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400 border border-red-100 dark:border-red-800/40">
                {{ __('Revoked') }}
            </span>
        @elseif($record->isExpired())
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-800/40">
                {{ __('Expired') }}
            </span>
        @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40">
                {{ __('Active') }}
            </span>
        @endif
    </td>

    {{-- Created --}}
    <td class="px-4 py-3.5 hidden md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $record->created_at }}
        </div>
    </td>

    {{-- Actions --}}
    <td class="px-4 py-3.5 whitespace-nowrap w-px">
        <livewire:core.shared-components.item-actions
            :editUrl="route('api.tokens.edit', $record->id)"
            :deleteId="$record->isRevoked() ? null : $record->id"
            deleteEvent="revokeToken"
            confirmMessage="{{ __('Yakin ingin me-revoke token ini? Setelah di-revoke, token tidak bisa digunakan lagi.') }}"
            :navigate="false"
            wire:key="item-actions-{{ $record->id }}" />
    </td>

</tr>