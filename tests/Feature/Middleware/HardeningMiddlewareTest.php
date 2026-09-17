<?php

use Bale\Api\Services\TokenManager;

test('token without hardening rules allows any ip and host', function () {
    $issued = app(TokenManager::class)->issue('Client');

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('single ip rule matches request ip', function () {
    $issued = app(TokenManager::class)->issue('Client', [], ['127.0.0.1']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('cidr rule allows in-range ip', function () {
    $issued = app(TokenManager::class)->issue('Client', [], ['127.0.0.0/8']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('ip outside allowed range is rejected', function () {
    $issued = app(TokenManager::class)->issue('Client', [], ['10.0.0.0/8']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertForbidden();
});

test('single ip outside allowed rules is rejected', function () {
    $issued = app(TokenManager::class)->issue('Client', [], ['192.168.1.10']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertForbidden();
});

test('exact host rule matches request host', function () {
    $issued = app(TokenManager::class)->issue('Client', [], [], ['localhost']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('host outside allowed rules is rejected', function () {
    $issued = app(TokenManager::class)->issue('Client', [], [], ['allowed.example.com']);

    $this->getJson('/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertForbidden();
});

test('wildcard host rule matches subdomain', function () {
    $issued = app(TokenManager::class)->issue('Client', [], [], ['*.example.com']);

    $this->getJson('http://sub.example.com/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('wildcard host rule matches bare domain', function () {
    $issued = app(TokenManager::class)->issue('Client', [], [], ['*.example.com']);

    $this->getJson('http://example.com/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertOk();
});

test('wildcard host rule rejects unrelated host', function () {
    $issued = app(TokenManager::class)->issue('Client', [], [], ['*.example.com']);

    $this->getJson('http://evil.test/api/ping', ['Authorization' => 'Bearer '.$issued['plain']])
        ->assertForbidden();
});
