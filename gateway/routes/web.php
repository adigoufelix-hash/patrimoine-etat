<?php

use App\Http\Controllers\AffectationWebController;
use App\Http\Controllers\AuditWebController;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\BienWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentWebController;
use App\Http\Controllers\InventaireController;
use App\Http\Controllers\NotificationWebController;
use App\Http\Controllers\ReferentielController;
use App\Http\Controllers\UtilisateurWebController;
use App\Http\Middleware\EnsureConnecte;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\JournalAudit;
use Illuminate\Support\Facades\Route;

Route::middleware(JournalAudit::class)->group(function () {

    Route::get('/connexion', [AuthWebController::class, 'form']);
    Route::post('/connexion', [AuthWebController::class, 'login']);
    Route::get('/inscription', [AuthWebController::class, 'formInscription']);
    Route::post('/inscription', [AuthWebController::class, 'inscription'])->middleware('throttle:5,1');

    Route::middleware(EnsureConnecte::class)->group(function () {
        Route::post('/deconnexion', [AuthWebController::class, 'logout']);

        // Tout utilisateur connecté (le responsable ne voit que son service : RG6)
        Route::get('/biens', [BienWebController::class, 'index']);
        Route::get('/biens/{id}', [InventaireController::class, 'show'])->whereNumber('id');
        Route::get('/affectations', [AffectationWebController::class, 'index']);
        Route::get('/incidents', [IncidentWebController::class, 'index']);
        Route::get('/incidents/nouveau', [IncidentWebController::class, 'create']);
        Route::post('/incidents', [IncidentWebController::class, 'store']);

        // Notifications : chacun ne voit que les siennes (RG15)
        Route::get('/notifications', [NotificationWebController::class, 'index']);
        Route::post('/notifications/lues', [NotificationWebController::class, 'toutes']);
        Route::post('/notifications/{id}/lue', [NotificationWebController::class, 'lire'])->whereNumber('id');

        // Administrateur et gestionnaire (RG5, RG9, RG10)
        Route::middleware(EnsureRole::class . ':admin,gestionnaire')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);

            Route::get('/biens/creer', [BienWebController::class, 'create']);
            Route::post('/biens', [BienWebController::class, 'store']);
            Route::get('/biens/{id}/modifier', [BienWebController::class, 'edit'])->whereNumber('id');
            Route::put('/biens/{id}', [BienWebController::class, 'update'])->whereNumber('id');

            Route::get('/inventaire/export', [InventaireController::class, 'export']);
            Route::get('/inventaire/imprimer', [InventaireController::class, 'imprimer']);

            Route::get('/affectations/nouvelle', [AffectationWebController::class, 'create']);
            Route::post('/affectations', [AffectationWebController::class, 'store']);

            Route::patch('/incidents/{id}/statut', [IncidentWebController::class, 'statut'])->whereNumber('id');
        });

        // Administrateur seul (RG7, RG8, RG11, RG12, RG13)
        Route::middleware(EnsureRole::class . ':admin')->group(function () {
            Route::get('/utilisateurs', [UtilisateurWebController::class, 'index']);
            Route::get('/utilisateurs/creer', [UtilisateurWebController::class, 'create']);
            Route::post('/utilisateurs', [UtilisateurWebController::class, 'store']);
            Route::get('/utilisateurs/{id}/modifier', [UtilisateurWebController::class, 'edit'])->whereNumber('id');
            Route::patch('/utilisateurs/{id}', [UtilisateurWebController::class, 'update'])->whereNumber('id');

            Route::get('/audit', [AuditWebController::class, 'index']);

            // Catégories et services : un seul contrôleur, le type vient de l'adresse
            Route::get('/{type}', [ReferentielController::class, 'index']);
            Route::get('/{type}/creer', [ReferentielController::class, 'create']);
            Route::post('/{type}', [ReferentielController::class, 'store']);
            Route::get('/{type}/{id}/modifier', [ReferentielController::class, 'edit'])->whereNumber('id');
            Route::put('/{type}/{id}', [ReferentielController::class, 'update'])->whereNumber('id');
        });
    });
});
