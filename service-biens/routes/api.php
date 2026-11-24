<?php

use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Middleware\CleService;
use Illuminate\Support\Facades\Route;

Route::middleware(CleService::class)->group(function () {
    Route::apiResource('biens', BienController::class);

    Route::get('/categories', [CategorieController::class, 'index']);
    Route::post('/categories', [CategorieController::class, 'store']);
    Route::put('/categories/{categorie}', [CategorieController::class, 'update']);
});
