<?php

use App\Http\Controllers\BienController;
use App\Models\Categorie;
use Illuminate\Support\Facades\Route;

Route::apiResource('biens', BienController::class);
Route::get('/categories', fn () => Categorie::all());