<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;

        if ($userId) {
            // Compter les requêtes de l'utilisateur pour aujourd'hui
            $cacheKey = "user_requests_{$userId}_" . now()->format('Y-m-d');
            $requestCount = Cache::increment($cacheKey);

            // Si c'est la première requête du jour, définir une expiration
            if ($requestCount === 1) {
                Cache::put($cacheKey, 1, now()->endOfDay());
            }

            // Logger si plus de 10 requêtes par jour
            if ($requestCount > 10) {
                Log::warning('Utilisateur avec trop de requêtes par jour', [
                    'user_id' => $userId,
                    'request_count' => $requestCount,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }
        }

        return $next($request);
    }
}
