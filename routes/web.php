<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-log', function () {
    Log::channel('integrado')->info('Testando log no canal integrado');
    return 'Log testado';
});