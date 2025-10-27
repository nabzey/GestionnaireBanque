<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log de la requête entrante
        $this->logRequest($request);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log de la réponse
        $this->logResponse($request, $response, $duration);

        return $response;
    }

    /**
     * Logger les informations de la requête
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'operation' => $this->getOperationName($request),
            'resource' => $this->getResourceName($request),
        ];

        Log::channel('operations')->info('API Request', $logData);
    }

    /**
     * Logger les informations de la réponse
     */
    private function logResponse(Request $request, Response $response, float $duration): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'operation' => $this->getOperationName($request),
            'resource' => $this->getResourceName($request),
        ];

        // Choisir le niveau de log selon le code de statut
        if ($response->getStatusCode() >= 500) {
            Log::channel('operations')->error('API Response - Error', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::channel('operations')->warning('API Response - Client Error', $logData);
        } else {
            Log::channel('operations')->info('API Response - Success', $logData);
        }
    }

    /**
     * Déterminer le nom de l'opération
     */
    private function getOperationName(Request $request): string
    {
        $method = $request->getMethod();
        $uri = $request->getRequestUri();

        // Extraire le nom de l'opération basé sur la route
        if (str_contains($uri, '/comptes')) {
            switch ($method) {
                case 'GET':
                    return str_contains($uri, '/comptes/') ? 'Afficher compte' : 'Lister comptes';
                case 'POST':
                    return 'Créer compte';
                case 'PUT':
                    return 'Modifier compte';
                case 'DELETE':
                    return 'Supprimer compte';
            }
        }

        return $method . ' ' . $uri;
    }

    /**
     * Déterminer le nom de la ressource
     */
    private function getResourceName(Request $request): string
    {
        $uri = $request->getRequestUri();

        if (str_contains($uri, '/comptes')) {
            return 'Comptes';
        }

        return 'API';
    }
}
