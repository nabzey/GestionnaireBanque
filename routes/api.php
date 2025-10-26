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

    // ✅ ROUTES COMPTES (protégées)
    Route::controller(CompteController::class)
        ->prefix('comptes')
        ->middleware(['auth:api', 'throttle:60,1'])
        ->group(function () {

            Route::get('/', 'index')->name('api.v1.comptes.index');           // Liste des comptes
            Route::post('/', 'store')->middleware('role:admin')->name('api.v1.comptes.store');  // Création (Admin)
            Route::get('/{id}', 'show')->name('api.v1.comptes.show');         // Afficher un compte
            Route::put('/{id}', 'update')->middleware('role:admin')->name('api.v1.comptes.update'); // Modifier (Admin)
            Route::delete('/{id}', 'destroy')->middleware('role:admin')->name('api.v1.comptes.destroy'); // Supprimer (Admin)
        });

    // ✅ ARCHIVES (Admin seulement)
    Route::get('comptes-archives', [CompteController::class, 'archives'])
        ->middleware(['auth:api', 'role:admin', 'throttle:60,1'])
        ->name('api.v1.comptes.archives');

    // ✅ TRANSACTIONS (protégées)
    Route::get('transactions', [TransactionController::class, 'index'])
        ->middleware(['auth:api', 'throttle:60,1'])
        ->name('api.v1.transactions.index');
});

// ✅ UTILISATEUR CONNECTÉ
// Route::middleware('auth:api')->get('/user', [UserController::class, 'getUser']);
