<?php

use Bale\Api\Models\ApiToken;
use Bale\Api\Services\TokenManager;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('api:install menambahkan 4 permission dan sync ke role root', function () {
    $root = Role::firstOrCreate(['name' => 'root', 'guard_name' => 'web']);

    Artisan::call('api:install');

    foreach (['api-token.read', 'api-token.create', 'api-token.update', 'api-token.revoke'] as $perm) {
        expect(Permission::findByName($perm, 'web'))->not->toBeNull();
    }

    expect($root->fresh()->hasPermissionTo('api-token.read'))->toBeTrue()
        ->and($root->fresh()->hasPermissionTo('api-token.revoke'))->toBeTrue();
});

it('api:prune-expired hanya menghapus token revoked/expired lebih lama dari prune window', function () {
    $pruneDays = (int) config('api.token.prune_days', 30);

    $oldRevoked = app(TokenManager::class)->issue('Lama Di-revoke', ['rakaca.form.read'])['model'];
    $oldRevoked->revoked_at = now()->subDays($pruneDays + 10);
    $oldRevoked->save();

    $oldExpired = app(TokenManager::class)->issue('Lama Kedaluwarsa', ['rakaca.form.read'])['model'];
    $oldExpired->expires_at = now()->subDays($pruneDays + 5);
    $oldExpired->save();

    $recentRevoked = app(TokenManager::class)->issue('Baru Di-revoke', ['rakaca.form.read'])['model'];
    $recentRevoked->revoked_at = now()->subDay();
    $recentRevoked->save();

    $active = app(TokenManager::class)->issue('Aktif', ['rakaca.form.read'])['model'];

    Artisan::call('api:prune-expired');

    expect(ApiToken::query()->find($oldRevoked->id))->toBeNull()
        ->and(ApiToken::query()->find($oldExpired->id))->toBeNull()
        ->and(ApiToken::query()->find($recentRevoked->id))->not->toBeNull()
        ->and(ApiToken::query()->find($active->id))->not->toBeNull();
});
