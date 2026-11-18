<?php

use App\Http\Controllers\NotificationController;
use App\Http\Middleware\CleService;
use Illuminate\Support\Facades\Route;

Route::middleware(CleService::class)->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/compteur', [NotificationController::class, 'compteur']);
    Route::post('/notifications/lues', [NotificationController::class, 'toutesLues']);
    Route::post('/notifications/{notification}/lue', [NotificationController::class, 'lire']);
});
