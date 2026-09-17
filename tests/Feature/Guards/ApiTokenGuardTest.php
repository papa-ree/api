<?php

use Bale\Api\Services\TokenManager;

test('valid token is authenticated', function () {
    $issued = app(TokenManager::class)->issue('Client');

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk()
        ->assertJson(['data' => 'pong']);
});

test('missing token returns 401', function () {
    $this->getJson('/api/ping')->assertUnauthorized();
});

test('invalid token returns 401', function () {
    $this->getJson('/api/ping', ['Authorization' => 'Bearer rkc_invalid_token'])
        ->assertUnauthorized();
});

test('malformed bearer header returns 401', function () {
    $issued = app(TokenManager::class)->issue('Client');

    $this->getJson('/api/ping', ['Authorization' => 'Basic '.$issued['plain']])
        ->assertUnauthorized();
});

test('revoked token returns 401', function () {
    $manager = app(TokenManager::class);
    $issued = $manager->issue('Client');
    $manager->revoke($issued['model']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertUnauthorized();
});

test('expired token returns 401', function () {
    $issued = app(TokenManager::class)->issue(
        'Client',
        [],
        [],
        [],
        now()->subMinute()->toDateTimeString()
    );

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertUnauthorized();
});

test('successful request updates last_used_at', function () {
    $issued = app(TokenManager::class)->issue('Client');

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])->assertOk();

    expect($issued['model']->fresh()->last_used_at)->not->toBeNull();
});
