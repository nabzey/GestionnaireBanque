<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CompteController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\UserController;

Route::prefix('v1/zeynab-ba')->group(function () {

    // ✅ AUTHENTIFICATION
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->middleware(['throttle:60,1'])
        ->group(function () {

            Route::post('login', 'login')->name('api.v1.auth.login');           // Connexion
            Route::post('register', 'register')->name('api.v1.auth.register'); // Inscription client
            Route::post('logout', 'logout')->middleware('auth:api')->name('api.v1.auth.logout'); // Déconnexion
            Route::get('user', 'user')->middleware('auth:api')->name('api.v1.auth.user');       // Utilisateur connecté
        });

    // Routes protégées (nécessitent authentification)

    // ✅ ROUTES COMPTES (temporairement sans authentification)
    Route::controller(CompteController::class)
        ->prefix('comptes')
        ->middleware(['throttle:60,1', \App\Http\Middleware\LoggingMiddleware::class])
        ->group(function () {

            Route::get('/', 'index')->name('api.v1.comptes.index');           // Liste des comptes
            Route::post('/', 'store')->name('api.v1.comptes.store');  // Création
            Route::get('/{id}', 'show')->name('api.v1.comptes.show');         // Afficher un compte
            Route::put('/{id}', 'update')->name('api.v1.comptes.update'); // Modifier/Bloquer/Débloquer
            Route::delete('/{id}', 'destroy')->name('api.v1.comptes.destroy'); // Supprimer
        });

    // ✅ ROUTES COMPTES AVEC AUTHENTIFICATION (pour OAuth2/Passport)
    // Route::middleware('auth:api')->group(function () {
    //     Route::controller(CompteController::class)
    //         ->prefix('comptes-secure')
    //         ->middleware(['throttle:60,1', \App\Http\Middleware\LoggingMiddleware::class])
    //         ->group(function () {
    //
    //             Route::get('/', 'index')->name('api.v1.comptes.secure.index');
    //             Route::get('/{id}', 'show')->name('api.v1.comptes.secure.show');
    //         });
    // });

    // ✅ ARCHIVES (temporairement sans authentification)
    Route::get('comptes-archives', [CompteController::class, 'archives'])
        ->middleware(['throttle:60,1'])
        ->name('api.v1.comptes.archives');

    // ✅ COMPTES NEON (bloqués/archivés dans base serverless)
    Route::get('comptes-neon', [CompteController::class, 'neon'])
        ->middleware(['throttle:60,1'])
        ->name('api.v1.comptes.neon');

    // ✅ TRANSACTIONS (protégées)
    Route::get('transactions', [TransactionController::class, 'index'])
        ->middleware(['auth:api', 'throttle:60,1'])
        ->name('api.v1.transactions.index');
});

// ✅ UTILISATEUR CONNECTÉ
// Route::middleware('auth:api')->get('/user', [UserController::class, 'getUser']);
