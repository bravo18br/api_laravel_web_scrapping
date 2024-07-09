<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/wpp', function () {
    return redirect('http://wpp:21465/api-docs');
});