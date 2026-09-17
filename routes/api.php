<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoint infrastructure bale/api
|--------------------------------------------------------------------------
|
| Hanya berisi endpoint milik bale/api sendiri. Endpoint domain didaftarkan
| oleh masing-masing package lewat routes/api.php milik package tersebut
| dengan memakai middleware group `bale.api` dan `scope:<nama>`.
|
*/

Route::middleware('bale.api')->prefix('api')->name('api.')->group(function () {
    Route::get('ping', function () {
        return response()->json(['data' => 'pong']);
    })->name('ping');

    Route::get('ping/ability', function () {
        return response()->json(['data' => 'pong']);
    })->middleware('scope:api.read')->name('ping.ability');
});
