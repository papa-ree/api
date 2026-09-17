<?php

use Bale\Api\Services\TokenManager;

test('token with required ability is allowed', function () {
    $issued = app(TokenManager::class)->issue('Client', ['api.read']);

    $this->getJson('/api/ping/ability', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('token with multiple abilities passes when one matches', function () {
    $issued = app(TokenManager::class)->issue('Client', ['other.ability', 'api.read']);

    $this->getJson('/api/ping/ability', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('token without required ability is forbidden', function () {
    $issued = app(TokenManager::class)->issue('Client', ['other.ability']);

    $this->getJson('/api/ping/ability', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertForbidden();
});

test('token with no abilities is forbidden', function () {
    $issued = app(TokenManager::class)->issue('Client', []);

    $this->getJson('/api/ping/ability', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertForbidden();
});

test('missing token is unauthorized', function () {
    $this->getJson('/api/ping/ability')->assertUnauthorized();
});
