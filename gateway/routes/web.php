<?php

use App\Http\Controllers\BienWebController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.bientot', ['titre' => 'Tableau de bord']);

Route::get('/biens', [BienWebController::class, 'index']);
Route::get('/biens/creer', [BienWebController::class, 'create']);
Route::post('/biens', [BienWebController::class, 'store']);
Route::get('/biens/{id}/modifier', [BienWebController::class, 'edit'])->whereNumber('id');
Route::put('/biens/{id}', [BienWebController::class, 'update'])->whereNumber('id');

Route::view('/affectations', 'pages.bientot', ['titre' => 'Affectations']);
Route::view('/incidents', 'pages.bientot', ['titre' => 'Incidents']);
Route::view('/utilisateurs', 'pages.bientot', ['titre' => 'Utilisateurs']);
