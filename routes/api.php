<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WppProxyController;

Route::any('/wpp/{any}', [WppProxyController::class, 'proxy'])->where('any', '.*');