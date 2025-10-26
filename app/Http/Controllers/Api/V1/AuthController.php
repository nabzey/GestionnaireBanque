<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
     *     description="Authentifier un utilisateur (Admin ou Client) et retourner un token d'accès",
     *     operationId="login",
     *     security={},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@banque.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Admin User"),
     *                     @OA\Property(property="email", type="string", example="admin@banque.com"),
     *                     @OA\Property(property="role", type="string", example="admin")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *             ),
     *             @OA\Property(property="message", type="string", example="Connexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Informations d'identification incorrectes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Les informations d'identification sont incorrectes."))
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $authData = User::authenticate($request->only(['email', 'password']));

        if (!$authData) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        return $this->successResponse($authData, 'Connexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/auth/register",
     *     tags={"Authentification"},
     *     summary="Inscription client",
     *     description="Créer un nouveau compte client avec authentification",
     *     operationId="register",
     *     security={},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","email","telephone","password","password_confirmation"},
     *             @OA\Property(property="nom", type="string", maxLength=255, example="Diop"),
     *             @OA\Property(property="prenom", type="string", maxLength=255, example="Amadou"),
     *             @OA\Property(property="email", type="string", format="email", example="amadou.diop@email.com"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Amadou Diop"),
     *                     @OA\Property(property="email", type="string", example="amadou.diop@email.com"),
     *                     @OA\Property(property="role", type="string", example="client")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *             ),
     *             @OA\Property(property="message", type="string", example="Inscription réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'required|string|unique:clients,telephone',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $registrationData = User::registerClient($request->all());

        return $this->successResponse($registrationData, 'Inscription réussie', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->revokeCurrentToken();

        return $this->successResponse(null, 'Déconnexion réussie');
    }
  
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse(
            $request->user()->getFullInfo(),
            'Informations utilisateur récupérées'
        );
    }
}
