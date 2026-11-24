<?php

use App\Http\Controllers\AffectationController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\CleService;
use Illuminate\Support\Facades\Route;

Route::middleware(CleService::class)->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);

    Route::get('/affectations', [AffectationController::class, 'index']);
    Route::post('/affectations', [AffectationController::class, 'store']);
    Route::get('/biens/{bienId}/affectations', [AffectationController::class, 'historique']);

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::patch('/incidents/{incident}', [IncidentController::class, 'update']);
});
