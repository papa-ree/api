<?php

use Bale\Api\Livewire\Pages\Token;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('api')->as('api.')->group(function () {
    // Token management UI (landlord).
    Route::middleware(['permission:api-token.read'])->group(function () {
        Route::get('/tokens', Token\Index::class)->name('tokens.index');
    });

    Route::middleware(['permission:api-token.create'])->group(function () {
        Route::get('/tokens/create', Token\Form::class)->name('tokens.create');
    });

    Route::middleware(['permission:api-token.update'])->group(function () {
        Route::get('/tokens/{token}/edit', Token\Form::class)->name('tokens.edit');
    });
});
