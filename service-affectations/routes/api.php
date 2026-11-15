<?php

use App\Http\Controllers\AffectationController;
use App\Http\Controllers\IncidentController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

Route::get('/services', fn () => Service::all());

Route::get('/affectations', [AffectationController::class, 'index']);
Route::post('/affectations', [AffectationController::class, 'store']);
Route::get('/biens/{bienId}/affectations', [AffectationController::class, 'historique']);

Route::get('/incidents', [IncidentController::class, 'index']);
Route::post('/incidents', [IncidentController::class, 'store']);
Route::patch('/incidents/{incident}', [IncidentController::class, 'update']);
