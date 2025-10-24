<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Vérifier si la réponse indique une limite de taux atteinte
        if ($response->getStatusCode() === 429) {
            $user = $request->user();
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $route = $request->route() ? $request->route()->getName() : 'unknown';

            // Log l'événement de limite de taux atteinte
            Log::warning('Rate limit exceeded', [
                'user_id' => $user ? $user->id : null,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'route' => $route,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ]);

            // Ici, vous pourriez également :
            // - Envoyer une notification à l'administrateur
            // - Stocker dans une table dédiée pour analyse
            // - Bloquer temporairement l'IP
            // - etc.
        }

        return $response;
    }
}
