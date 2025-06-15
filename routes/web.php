<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\PlayerController;

Route::get('/player/{polarisId}', [PlayerController::class, 'show']);