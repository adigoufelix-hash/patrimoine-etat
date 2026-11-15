<?php

use App\Http\Controllers\AffectationWebController;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\BienWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\EnsureConnecte;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/connexion', [AuthWebController::class, 'form']);
Route::post('/connexion', [AuthWebController::class, 'login']);
Route::get('/inscription', [AuthWebController::class, 'formInscription']);
Route::post('/inscription', [AuthWebController::class, 'inscription'])->middleware('throttle:5,1');

Route::middleware(EnsureConnecte::class)->group(function () {
    Route::post('/deconnexion', [AuthWebController::class, 'logout']);

    // Tout utilisateur connecté (le responsable ne voit que son service : RG6)
    Route::get('/biens', [BienWebController::class, 'index']);
    Route::get('/affectations', [AffectationWebController::class, 'index']);
    Route::view('/incidents', 'pages.bientot', ['titre' => 'Incidents']);

    // Administrateur et gestionnaire (RG5)
    Route::middleware(EnsureRole::class . ':admin,gestionnaire')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);

        Route::get('/biens/creer', [BienWebController::class, 'create']);
        Route::post('/biens', [BienWebController::class, 'store']);
        Route::get('/biens/{id}/modifier', [BienWebController::class, 'edit'])->whereNumber('id');
        Route::put('/biens/{id}', [BienWebController::class, 'update'])->whereNumber('id');

        Route::get('/affectations/nouvelle', [AffectationWebController::class, 'create']);
        Route::post('/affectations', [AffectationWebController::class, 'store']);
    });

    // Administrateur seul
    Route::view('/utilisateurs', 'pages.bientot', ['titre' => 'Utilisateurs'])
        ->middleware(EnsureRole::class . ':admin');
});
