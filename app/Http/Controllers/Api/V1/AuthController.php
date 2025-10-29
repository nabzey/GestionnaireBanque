<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Gestion de l'authentification"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur (Admin ou Client)",
     *     description="Authentifie un utilisateur et retourne les tokens OAuth2. Le rôle (admin/client) est déterminé automatiquement selon les identifiants fournis.",
     *     operationId="login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Connexion Admin",
     *                     required={"email", "password"},
     *                     @OA\Property(property="email", type="string", format="email", description="Adresse email de l'admin", example="admin@banque.com"),
     *                     @OA\Property(property="password", type="string", description="Mot de passe de l'admin", example="password")
     *                 ),
     *                 @OA\Schema(
     *                     title="Connexion Client",
     *                     required={"email", "client_id"},
     *                     @OA\Property(property="email", type="string", format="email", description="Adresse email du client", example="client@example.com"),
     *                     @OA\Property(property="client_id", type="string", format="uuid", description="ID unique du client", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", example="1"),
     *                     @OA\Property(property="name", type="string", example="Admin Principal"),
     *                     @OA\Property(property="email", type="string", example="admin@banque.com"),
     *                     @OA\Property(property="role", type="string", enum={"admin", "client"}, example="admin"),
     *                     @OA\Property(property="profile", type="object", description="Informations du profil selon le rôle")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="refresh_token", type="string", example="refresh_token_here"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2023-12-01T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Informations d'identification incorrectes",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        // Validation différenciée selon le contexte
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'code_authentification' => 'sometimes|required_without:password|string',
        ]);

        $authData = User::authenticate($request->only(['email', 'password', 'code_authentification']));

        if (!$authData) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        // Créer un cookie sécurisé pour le token d'accès
        $cookie = Cookie::make(
            'access_token',
            $authData['token'],
            config('passport.tokens_expire_in') / 60, // Convertir en minutes
            null,
            null,
            config('app.env') === 'production', // Secure en production
            true, // HttpOnly
            false, // Raw
            'Lax' // SameSite
        );

        return $this->successResponse($authData, 'Connexion réussie')
            ->withCookie($cookie);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/auth/register",
     *     tags={"Authentification"},
     *     summary="Inscription client",
     *     description="Crée un nouveau compte client avec génération automatique d'un code d'authentification",
     *     operationId="register",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "email", "password", "password_confirmation"},
     *             @OA\Property(property="nom", type="string", description="Nom du client", example="Diallo"),
     *             @OA\Property(property="prenom", type="string", description="Prénom du client", example="Amadou"),
     *             @OA\Property(property="email", type="string", format="email", description="Adresse email", example="amadou.diallo@example.com"),
     *             @OA\Property(property="password", type="string", minLength=8, description="Mot de passe", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", description="Confirmation du mot de passe", example="password123"),
     *             @OA\Property(property="telephone", type="string", description="Numéro de téléphone", example="+221771234567"),
     *             @OA\Property(property="nci", type="string", description="Numéro NCI", example="1234567890123"),
     *             @OA\Property(property="adresse", type="string", description="Adresse", example="123 Rue de la Paix, Dakar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inscription réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="temporary_password", type="string", example="temp12345")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => ['required', new \App\Rules\TelephoneRule(), 'unique:clients,telephone'],
            'nci' => 'required|string|size:13|regex:/^\d{13}$/|unique:clients,nci',
            'adresse' => 'nullable|string|max:500',
        ]);

        $registrationData = User::registerClient($request->all());

        // Envoyer l'email de confirmation
        try {
            \Illuminate\Support\Facades\Mail::to($registrationData['user']['email'])->send(
                new \App\Mail\ClientRegistrationConfirmation($registrationData['user'], $registrationData['temporary_password'], $registrationData['code_authentification'])
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Erreur lors de l\'envoi de l\'email de confirmation: ' . $e->getMessage());
            // Ne pas échouer l'inscription si l'email échoue
        }

        return $this->successResponse($registrationData, 'Inscription réussie. Un email de confirmation a été envoyé.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/auth/refresh",
     *     tags={"Authentification"},
     *     summary="Rafraîchir le token d'accès",
     *     description="Utilise le refresh token pour générer un nouveau token d'accès",
     *     operationId="refresh",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", description="Le refresh token", example="refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="refresh_token", type="string", example="new_refresh_token_here"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2023-12-01T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        try {
            // Trouver le token de refresh
            $refreshToken = \Laravel\Passport\RefreshToken::where('id', $request->refresh_token)->first();

            if (!$refreshToken) {
                return $this->errorResponse('Refresh token invalide', 401);
            }

            // Vérifier si le token n'est pas expiré
            if ($refreshToken->expires_at && $refreshToken->expires_at->isPast()) {
                return $this->errorResponse('Refresh token expiré', 401);
            }

            // Récupérer l'utilisateur associé
            $accessToken = $refreshToken->accessToken;
            if (!$accessToken) {
                return $this->errorResponse('Token d\'accès associé introuvable', 401);
            }

            $user = $accessToken->user;
            if (!$user) {
                return $this->errorResponse('Utilisateur introuvable', 401);
            }

            // Révoquer l'ancien token d'accès
            $accessToken->revoke();

            // Créer de nouveaux tokens
            $tokenResult = User::createTokens($user);

            // Créer un cookie sécurisé pour le nouveau token d'accès
            $cookie = Cookie::make(
                'access_token',
                $tokenResult['access_token'],
                config('passport.tokens_expire_in') / 60,
                null,
                null,
                config('app.env') === 'production',
                true,
                false,
                'Lax'
            );

            return $this->successResponse([
                'token' => $tokenResult['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => $tokenResult['expires_in'],
                'refresh_token' => $tokenResult['refresh_token'],
                'expires_at' => $tokenResult['expires_at'],
            ], 'Token rafraîchi avec succès')->withCookie($cookie);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du rafraîchissement du token', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/auth/logout",
     *     tags={"Authentification"},
     *     summary="Déconnexion",
     *     description="Révoque le token d'accès actuel de l'utilisateur",
     *     operationId="logout",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->revokeCurrentToken();

        // Supprimer le cookie d'accès
        $cookie = Cookie::forget('access_token');

        return $this->successResponse(null, 'Déconnexion réussie')
            ->withCookie($cookie);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/auth/user",
     *     tags={"Authentification"},
     *     summary="Informations utilisateur connecté",
     *     description="Récupère les informations complètes de l'utilisateur actuellement connecté",
     *     operationId="getUser",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informations utilisateur récupérées"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse(
            $request->user()->getFullInfo(),
            'Informations utilisateur récupérées'
        );
    }
}
