<?php

namespace App\Providers;

use App\Models\Compte;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Route model binding personnalisé pour les comptes
        Route::bind('compte', function ($value) {
            // D'abord chercher en local
            $compte = Compte::find($value);

            if ($compte) {
                return $compte;
            }

            // Si pas trouvé en local, chercher dans Neon
            $neonCompte = DB::connection('neon')->table('comptes')->where('id', $value)->first();

            if ($neonCompte) {
                // Créer une instance temporaire du modèle pour Neon
                return new Compte((array) $neonCompte);
            }

            return null;
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
