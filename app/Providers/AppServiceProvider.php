<?php

namespace App\Providers;

use App\Models\Compte;
use App\Observers\CompteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer l'observer pour les comptes
        Compte::observe(CompteObserver::class);
    }
}
