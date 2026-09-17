<?php

use Bale\Api\Models\ApiToken;
use Bale\Api\Services\TokenManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('issue returns the plain token once and stores only its hash', function () {
    $manager = app(TokenManager::class);

    $result = $manager->issue('My Client', ['rakaca.form.read']);

    expect($result)->toHaveKeys(['plain', 'model'])
        ->and($result['plain'])->toStartWith('rkc_')
        ->and(Str::length($result['plain']))->toBe(44)
        ->and($result['model'])->toBeInstanceOf(ApiToken::class)
        ->and($result['model']->token)->not->toBe($result['plain'])
        ->and($result['model']->name)->toBe('My Client')
        ->and($result['model']->abilities)->toBe(['rakaca.form.read']);

    expect(DB::table('api_tokens')->where('token', $result['plain'])->exists())->toBeFalse();

    $resolved = $manager->resolve($result['plain']);
    expect($resolved)->not->toBeNull()
        ->and($resolved->getKey())->toBe($result['model']->getKey());
});

test('resolve returns null for unknown token', function () {
    $manager = app(TokenManager::class);

    expect($manager->resolve('rkc_invalid_token_value_here'))->toBeNull();
});

test('resolve rejects revoked tokens', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client');

    $manager->revoke($issued['model']);

    expect($manager->resolve($issued['plain']))->toBeNull();
});

test('resolve accepts tokens without expiry', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client');

    expect($manager->resolve($issued['plain']))->not->toBeNull();
});

test('resolve rejects expired tokens', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client', [], [], [], now()->subMinute()->toDateTimeString());

    expect($manager->resolve($issued['plain']))->toBeNull();
});

test('resolve accepts tokens that expire in the future', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client', [], [], [], now()->addDay()->toDateTimeString());

    expect($manager->resolve($issued['plain']))->not->toBeNull();
});

test('revoke sets revoked_at on the model', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client');

    expect($issued['model']->revoked_at)->toBeNull();

    expect($manager->revoke($issued['model']))->toBeTrue();

    expect($issued['model']->fresh()->revoked_at)->not->toBeNull()
        ->and($manager->revoke($issued['model']))->toBeFalse();
});

test('revoke accepts a token id string', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client');

    expect($manager->revoke($issued['model']->getKey()))->toBeTrue();
    expect(ApiToken::find($issued['model']->getKey())->revoked_at)->not->toBeNull();
});

test('revoke returns false for unknown id', function () {
    $manager = app(TokenManager::class);

    expect($manager->revoke('00000000-0000-0000-0000-000000000000'))->toBeFalse();
});

test('touchUsage updates last_used_at', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client');

    $this->travel(10)->minutes();

    $manager->touchUsage($issued['model']);

    expect($issued['model']->fresh()->last_used_at)->not->toBeNull();
});

test('hashToken is deterministic and uses the configured algorithm', function () {
    $manager = app(TokenManager::class);

    expect($manager->hashToken('abc'))->toBe(hash('sha256', 'abc'))
        ->and($manager->hashToken('abc'))->toBe($manager->hashToken('abc'));
});
