<?php

use App\Http\Controllers\BienWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/biens', [BienWebController::class, 'index']);