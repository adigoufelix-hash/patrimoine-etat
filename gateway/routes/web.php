<?php

use App\Http\Controllers\BienWebController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.bientot', ['titre' => 'Tableau de bord']);
Route::get('/biens', [BienWebController::class, 'index']);
Route::view('/affectations', 'pages.bientot', ['titre' => 'Affectations']);
Route::view('/incidents', 'pages.bientot', ['titre' => 'Incidents']);
Route::view('/utilisateurs', 'pages.bientot', ['titre' => 'Utilisateurs']);
