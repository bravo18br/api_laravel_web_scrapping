<?php

use App\Http\Controllers\WppController;
use Illuminate\Support\Facades\Route;

Route::get('/wpp/api-docs', [WppController::class, 'api_docs']);
Route::get('/wpp/gerar-token', [WppController::class, 'gerar_token']);