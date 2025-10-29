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
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur (admin ou client) et retourne les tokens OAuth2",
     *     operationId="login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", description="Adresse email de l'utilisateur", example="admin@banque.com"),
     *             @OA\Property(property="password", type="string", description="Mot de passe (requis pour les admins)", example="password"),
     *             @OA\Property(property="code_authentification", type="string", description="Code d'authentification (requis pour les clients)", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
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
            'password' => 'sometimes|required_without:code_authentification|string',
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
            'telephone' => ['nullable', new \App\Rules\TelephoneRule()],
            'nci' => 'nullable|string|size:13|regex:/^\d{13}$/',
            'adresse' => 'nullable|string|max:500',
        ]);

        $registrationData = User::registerClient($request->all());

        return $this->successResponse($registrationData, 'Inscription réussie');
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
