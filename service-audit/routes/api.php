<?php

use App\Http\Controllers\EvenementController;
use App\Http\Middleware\CleService;
use Illuminate\Support\Facades\Route;

// Journal en ajout seul (RG13) : il n'y a volontairement ni PUT, ni PATCH, ni DELETE.
Route::middleware(CleService::class)->group(function () {
    Route::get('/evenements', [EvenementController::class, 'index']);
    Route::post('/evenements', [EvenementController::class, 'store']);
});
