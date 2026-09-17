<div>
    <x-core::page-header
        :title="$tokenId ? __('Edit API Token') : __('Create API Token')"
        :subtitle="__('Manage token name, permissions, and hardening rules')"
    />

    {{-- Plain token shown only once, right after creation --}}
    @if($plainToken)
        <div class="max-w-3xl mx-auto mt-6">
            <div class="rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-900/20 p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 size-10 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center">
                        <x-lucide-key-round class="size-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Copy this token now') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('This is the only time the token will be shown. Store it somewhere safe before leaving this page.') }}
                        </p>
                        <div x-data="{ copied: false }"
                            class="mt-3 flex items-center gap-2 p-3 rounded-xl cursor-pointer group bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-800/60"
                            @click="navigator.clipboard.writeText(@js($plainToken)); copied = true; setTimeout(() => copied = false, 2000)">
                            <span class="flex-1 min-w-0 font-mono text-sm text-gray-800 dark:text-gray-200 break-all">{{ $plainToken }}</span>
                            <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium text-amber-700 dark:text-amber-400">
                                <x-lucide-clipboard x-show="!copied" class="size-4" />
                                <x-lucide-check x-show="copied" class="size-4 text-emerald-500" />
                                <span x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy') }}'"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="max-w-3xl mx-auto mt-6 space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/60 p-6 space-y-6">
            {{-- Name --}}
            <div>
                <x-core::input label="{{ __('Token Name') }}" wire:model="name" name="name"
                    placeholder="e.g. TI Helpdesk Integration" required autofocus />
                <x-core::input-error for="name" />
            </div>

            {{-- Permissions --}}
            <div>
                <x-core::label :value="__('Permissions')" />
                <div class="mt-2 space-y-4">
                    @foreach($abilityGroups as $group => $items)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">
                                {{ strtoupper($group) }}
                            </div>
                            <div class="space-y-1">
                                @foreach($items as $ability)
                                    <label class="flex items-center gap-3 py-1 cursor-pointer">
                                        <x-core::checkbox value="{{ $ability }}" wire:model="abilities" />
                                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $ability }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-core::input-error for="abilities" />
            </div>

            {{-- Allowed IPs --}}
            <div>
                <x-core::textarea label="{{ __('Allowed IPs') }}" wire:model="allowedIps" rows="3"
                    placeholder="192.168.1.10&#10;10.0.0.0/8" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('One IP or CIDR per line. Empty = allow all.') }}</p>
                <x-core::input-error for="allowedIps" />
            </div>

            {{-- Allowed Hosts --}}
            <div>
                <x-core::textarea label="{{ __('Allowed Hosts') }}" wire:model="allowedHosts" rows="3"
                    placeholder="*.ponorogo.go.id&#10;api.example.com" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('One host or wildcard (*.) per line. Empty = allow all.') }}</p>
                <x-core::input-error for="allowedHosts" />
            </div>

            {{-- Expiration --}}
            <div>
                <x-core::input type="date" label="{{ __('Expires At') }}" wire:model="expiresAt" name="expires_at" />
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Empty = never expires.') }}</p>
                <x-core::input-error for="expiresAt" />
            </div>

            {{-- Revoke toggle (edit only) --}}
            @if($tokenId)
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700/60">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <x-core::checkbox wire:model="revoked" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Revoke this token') }}</span>
                    </label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('Revoked tokens immediately stop working.') }}</p>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <x-core::button type="submit" variant="primary" spinner="save"
                    :label="$tokenId ? __('Update Token') : __('Create Token')" />
                <x-core::button link href="{{ route('api.tokens.index') }}" variant="secondary" :label="__('Cancel')" />
            </div>
        </div>
    </form>
</div>