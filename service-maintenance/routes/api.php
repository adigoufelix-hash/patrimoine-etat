<?php

use App\Http\Controllers\MaintenanceController;
use App\Http\Middleware\CleService;
use Illuminate\Support\Facades\Route;

Route::middleware(CleService::class)->group(function () {
    Route::get('/maintenances', [MaintenanceController::class, 'index']);
    Route::post('/maintenances', [MaintenanceController::class, 'store']);
    Route::post('/maintenances/alertes', [MaintenanceController::class, 'alertes']);
    Route::patch('/maintenances/{maintenance}', [MaintenanceController::class, 'update']);
});
