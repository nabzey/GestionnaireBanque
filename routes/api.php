<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\V1\CompteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes API version 1
Route::prefix('v1')->group(function () {
    /**
     * Lister tous les comptes
     *
     * GET /api/v1/comptes
     *
     * Query Parameters:
     * - page: Numéro de page (default: 1)
     * - limit: Nombre d'éléments par page (default: 10, max: 100)
     * - type: Filtrer par type (epargne, cheque)
     * - statut: Filtrer par statut (actif, bloque, ferme)
     * - search: Recherche par titulaire ou numéro
     * - sort: Tri (dateCreation, solde, titulaire)
     * - order: Ordre (asc, desc)
     */
    Route::get('/comptes', [CompteController::class, 'index'])
        ->middleware(['throttle:60,1'])
        ->name('api.v1.comptes.index');
});

Route::middleware('auth:api')->get('/user', [UserController::class, 'getUser']);
