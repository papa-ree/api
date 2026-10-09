<?php

use App\Models\User;
use Bale\Api\Livewire\Pages\Token\Form;
use Bale\Api\Livewire\Pages\Token\Index;
use Bale\Api\Livewire\Pages\Token\Section\TokenTable;
use Bale\Api\Models\ApiToken;
use Bale\Api\Services\TokenManager;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['api-token.read', 'api-token.create', 'api-token.update', 'api-token.revoke'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function apiTokenUser(array $perms = ['api-token.read']): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($perms);

    return $user;
}

it('menampilkan halaman index token dengan permission', function () {
    $user = apiTokenUser(['api-token.read']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('API Tokens');
});

it('create token menampilkan plain token sekali dan menyimpannya (hanya hash)', function () {
    $user = apiTokenUser(['api-token.read', 'api-token.create']);

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('name', 'Integrasi Topologi')
        ->set('abilities', ['api.read'])
        ->set('allowedIps', "10.0.0.0/8\n192.168.1.5")
        ->set('allowedHosts', '*.ponorogo.go.id')
        ->set('expiresAt', now()->addMonth()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('plainToken', fn ($value) => is_string($value) && str_starts_with($value, 'rkc_'))
        ->assertSet('tokenId', fn ($value) => $value !== null);

    $token = ApiToken::query()->firstOrFail();

    expect($token->name)->toBe('Integrasi Topologi')
        ->and($token->abilities)->toContain('api.read')
        ->and($token->allowed_ips)->toBe(['10.0.0.0/8', '192.168.1.5'])
        ->and($token->allowed_hosts)->toBe(['*.ponorogo.go.id'])
        ->and($token->token)->not->toBe(app(TokenManager::class)->resolve($token->token))
        ->and($token->expires_at)->not->toBeNull();
});

it('edit token memperbarui abilities dan hardening tanpa regenerate', function () {
    $user = apiTokenUser(['api-token.read', 'api-token.update']);

    $issued = app(TokenManager::class)->issue('Lama', ['api.read']);
    $plain = $issued['plain'];

    Livewire::actingAs($user)
        ->test(Form::class, ['id' => $issued['model']->id])
        ->assertSet('name', 'Lama')
        ->assertSet('abilities', ['api.read'])
        ->set('name', 'Baru')
        ->set('abilities', ['api.read'])
        ->set('allowedIps', '203.0.113.10')
        ->call('save')
        ->assertHasNoErrors();

    $token = $issued['model']->fresh();

    expect($token->name)->toBe('Baru')
        ->and($token->abilities)->toBe(['api.read'])
        ->and($token->allowed_ips)->toBe(['203.0.113.10'])
        ->and($token->token)->toBe(app(TokenManager::class)->hashToken($plain));
});

it('revoke token via tabel langsung memblokir request', function () {
    $user = apiTokenUser(['api-token.read', 'api-token.revoke']);

    $issued = app(TokenManager::class)->issue('Sementara', ['rakaca.form.read']);

    Livewire::actingAs($user)
        ->test(TokenTable::class)
        ->call('revokeToken', $issued['model']->id);

    expect($issued['model']->fresh()->isRevoked())->toBeTrue()
        ->and(app(TokenManager::class)->resolve($issued['plain']))->toBeNull();
});

it('revoke tanpa permission api-token.revoke ditolak 403', function () {
    $user = apiTokenUser(['api-token.read']);

    $issued = app(TokenManager::class)->issue('Tanpa Revoke', ['rakaca.form.read']);

    Livewire::actingAs($user)
        ->test(TokenTable::class)
        ->call('revokeToken', $issued['model']->id)
        ->assertForbidden();

    expect($issued['model']->fresh()->isRevoked())->toBeFalse();
});

it('regenerate token via form mengganti secret dan mencatat log', function () {
    $user = apiTokenUser(['api-token.read', 'api-token.update']);

    $issued = app(TokenManager::class)->issue('Rotasi', ['api.read']);
    $oldPlain = $issued['plain'];

    Livewire::actingAs($user)
        ->test(Form::class, ['id' => $issued['model']->id])
        ->assertSet('plainToken', null)
        ->call('regenerateToken')
        ->assertHasNoErrors()
        ->assertSet('plainToken', fn ($value) => is_string($value) && str_starts_with($value, 'rkc_'))
        ->assertSet('revoked', false);

    $model = $issued['model']->fresh();

    expect($model->isValid())->toBeTrue()
        ->and($model->token)->not->toBe(app(TokenManager::class)->hashToken($oldPlain))
        ->and(app(TokenManager::class)->resolve($oldPlain))->toBeNull();

    $activityModel = config('activitylog.activity_model');
    $log = $activityModel::query()
        ->where('subject_id', $issued['model']->id)
        ->where('subject_type', ApiToken::class)
        ->where('event', 'regenerated')
        ->firstOrFail();

    expect($log->causer_type)->toBe(User::class)
        ->and($log->causer_id)->toBe($user->id)
        ->and($log->log_name)->toBe('api.token')
        ->and($log->properties->get('logged_by'))->toBe('user');
});

it('edit token via form mencatat event updated pada activity log', function () {
    $user = apiTokenUser(['api-token.read', 'api-token.update']);

    $issued = app(TokenManager::class)->issue('Nama Awal', ['api.read']);

    Livewire::actingAs($user)
        ->test(Form::class, ['id' => $issued['model']->id])
        ->set('name', 'Nama Baru')
        ->call('save')
        ->assertHasNoErrors();

    $activityModel = config('activitylog.activity_model');
    $log = $activityModel::query()
        ->where('subject_id', $issued['model']->id)
        ->where('subject_type', ApiToken::class)
        ->where('event', 'updated')
        ->firstOrFail();

    expect($log->causer_id)->toBe($user->id)
        ->and($log->properties->get('name'))->toBe('Nama Baru');
});
