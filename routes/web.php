<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\PlayerController;
use App\Http\Controllers\SimplePlayerController;

Route::get('/player/{polarisId}', [PlayerController::class, 'show']);

Route::get('/sets/{polarisId}', [SimplePlayerController::class, 'getPlayerSets']);