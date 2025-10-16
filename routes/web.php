<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/login', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/', function () {
    return view('welcome');
});

// ...existing code...
