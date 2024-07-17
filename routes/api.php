<?php

use App\Http\Controllers\AlvoController;
use Illuminate\Support\Facades\Route;

Route::get('/atualizaConteudoOriginal', [AlvoController::class, 'atualizaConteudoOriginal']);

Route::apiResource('/alvo', AlvoController::class);
