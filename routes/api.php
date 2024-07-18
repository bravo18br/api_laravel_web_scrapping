<?php

use App\Http\Controllers\AlvoController;
use App\Http\Controllers\WppController;
use Illuminate\Support\Facades\Route;

Route::get('/alvo/atualizaConteudoAlvo', [AlvoController::class, 'atualizaConteudoOriginal']);

Route::apiResource('/alvo', AlvoController::class);

Route::get('/wpp/getStatusWPP', [WppController::class, 'getStatusWPP']);