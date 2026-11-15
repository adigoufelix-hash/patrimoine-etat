<?php

use App\Http\Controllers\AffectationWebController;
use App\Http\Controllers\BienWebController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);

Route::get('/biens', [BienWebController::class, 'index']);
Route::get('/biens/creer', [BienWebController::class, 'create']);
Route::post('/biens', [BienWebController::class, 'store']);
Route::get('/biens/{id}/modifier', [BienWebController::class, 'edit'])->whereNumber('id');
Route::put('/biens/{id}', [BienWebController::class, 'update'])->whereNumber('id');

Route::get('/affectations', [AffectationWebController::class, 'index']);
Route::get('/affectations/nouvelle', [AffectationWebController::class, 'create']);
Route::post('/affectations', [AffectationWebController::class, 'store']);

Route::view('/incidents', 'pages.bientot', ['titre' => 'Incidents']);
Route::view('/utilisateurs', 'pages.bientot', ['titre' => 'Utilisateurs']);
