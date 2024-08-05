<?php

use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->view('welcome', [], 200);
})->middleware(HandleCors::class);

// Route::get('/', function () {
//     return response()->view('welcome', [], 200);
// });
