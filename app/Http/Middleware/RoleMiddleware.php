<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
                'errors' => ['auth' => 'Token d\'authentification requis']
            ], 401);
        }

        // Vérifier le rôle basé sur userable_type (polymorphisme)
        if ($role === 'admin' && $user->userable_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux administrateurs',
                'errors' => ['role' => 'Vous n\'avez pas les permissions nécessaires']
            ], 403);
        }

        if ($role === 'client' && $user->userable_type !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux clients',
                'errors' => ['role' => 'Vous n\'avez pas les permissions nécessaires']
            ], 403);
        }

        return $next($request);
    }
}
