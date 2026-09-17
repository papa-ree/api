<?php

use Bale\Api\Services\ApiScopeRegistry;

if (! function_exists('registerApiScopes')) {
    /**
     * Daftarkan scope API dari ServiceProvider package.
     *
     * Contoh:
     *   registerApiScopes('rakaca', [
     *       'rakaca.form.read' => 'Membaca daftar & detail formulir.',
     *   ]);
     *
     * @param  array<int|string, string>  $scopes  [scope => deskripsi] atau [scope, ...]
     */
    function registerApiScopes(string $group, array $scopes): void
    {
        app(ApiScopeRegistry::class)->register($group, $scopes);
    }
}
