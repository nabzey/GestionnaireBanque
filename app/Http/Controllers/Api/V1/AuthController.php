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
