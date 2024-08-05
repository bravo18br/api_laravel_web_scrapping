<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['cors'])->group(function () {
    Route::get('/', function () {
        return response()->view('welcome', [], 200);
    });
});
