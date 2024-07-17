<?php

use App\Http\Controllers\AlvoController;
use App\Http\Controllers\WppController;
use App\Http\Controllers\WppProxyController;
use Illuminate\Support\Facades\Route;

// Route::any('/wpp/{any}', [WppProxyController::class, 'proxy'])->where('any', '.*');

// Route::get('/wpp/api-docs', [WppController::class, 'api_docs']);
// Route::get('/wpp/gerar-token', [WppController::class, 'gerar_token']);

Route::get('/atualizaConteudoOriginal', [AlvoController::class, 'atualizaConteudoOriginal']);