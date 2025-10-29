<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CompteController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests;
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'statut', 'search', 'sort', 'order']);

            // Pagination
            $limit = min($request->get('limit', 10), 100);

            $query = Compte::with('client')
                ->filterAndSort($filters);

            // Vérification d'authentification et filtrage par utilisateur
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $client = $request->user()->client;
                if ($client) {
                    $query->byClientId($client->id);
                } else {
                    return $this->errorResponse('Client non trouvé', 404);
                }
            }

            $comptes = $query->paginate($limit);

            return $this->paginatedResponse(
                CompteResource::collection($comptes),
                $comptes,
                'Liste des comptes récupérée avec succès'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des comptes', 500);
        }
    }


    /**
     * Créer un nouveau compte
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte",
     *     description="Créer un nouveau compte bancaire",
     *     operationId="createCompte",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_id", "type", "devise"},
     *             @OA\Property(property="admin_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne"}, example="epargne"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
     *             @OA\Property(property="solde_initial", type="number", format="float", example=100000),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="_links", type="object",
     *                 @OA\Property(property="self", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
     *                     @OA\Property(property="method", type="string", example="GET"),
     *                     @OA\Property(property="rel", type="string", example="self")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     security={}
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Vérifier les autorisations
            $this->authorize('create', Compte::class);

            $validated = $request->validate([
                'telephone' => ['required', new \App\Rules\TelephoneRule()],
                'type' => 'required|in:cheque,epargne',
                'soldeInitial' => 'required|numeric|min:10000',
                'devise' => 'required|in:FCFA,EUR,USD',
                'client' => 'required|array',
                'client.titulaire' => 'required|string|min:2|max:255',
                'client.nci' => 'required|string|size:13|regex:/^\d{13}$/',
                'client.email' => 'required|email|unique:users,email',
                'client.telephone' => ['required', new \App\Rules\TelephoneRule()],
                'client.adresse' => 'nullable|string|max:500',
                'client_id' => 'nullable|exists:clients,id'
            ]);

            DB::beginTransaction();

            $clientData = $validated['client'];

            // Vérifier si le client existe ou le créer
            if (empty($validated['client_id'])) {
                // Créer un nouveau client
                $temporaryPassword = Compte::generateCode();
                $verificationCode = Compte::generateCode();

                $client = Client::create([
                    'nom' => $clientData['titulaire'],
                    'prenom' => '', // On peut extraire le prénom du titulaire si nécessaire
                    'email' => $clientData['email'],
                    'telephone' => $validated['telephone'],
                    'nci' => $clientData['nci'],
                    'adresse' => $clientData['adresse'] ?? null,
                    'statut' => 'actif',
                ]);

                // Créer l'utilisateur associé
                $user = User::create([
                    'name' => $clientData['titulaire'],
                    'email' => $clientData['email'],
                    'password' => Hash::make($temporaryPassword),
                    'email_verified_at' => now(),
                    'userable_type' => 'client',
                    'userable_id' => $client->id,
                ]);

                $clientId = $client->id;
                $userCreated = true;
            } else {
                // Utiliser le client existant
                $client = Client::findOrFail($validated['client_id']);
                $clientId = $client->id;
                $temporaryPassword = null;
                $verificationCode = null;
                $userCreated = false;
            }

            // Créer le compte
            $compte = Compte::create([
                'client_id' => $clientId,
                'numero' => Compte::generateNumero(),
                'telephone' => $validated['telephone'],
                'type' => $validated['type'],
                'devise' => $validated['devise'],
                'solde_initial' => $validated['soldeInitial'],
                'statut' => 'actif',
            ]);

            DB::commit();

            // Envoyer les notifications après la création réussie
            if ($userCreated) {
                try {
                    // Déclencher l'événement pour les notifications
                    \App\Events\SendClientNotification::dispatch($client, $compte, $temporaryPassword, $verificationCode);
                } catch (\Exception $e) {
                    // Log l'erreur d'email mais ne pas échouer la création du compte
                    Log::warning('Erreur lors de l\'envoi des notifications: ' . $e->getMessage());
                    // Le compte est créé avec succès même si l'email échoue
                }
            }

            $responseData = new CompteResource($compte);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'delete' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'DELETE',
                    'rel' => 'delete'
                ],
                'collection' => [
                    'href' => url('/api/v1/zeynab-ba/comptes'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            $response = [
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => $responseData,
            ];

            // Ajouter les informations supplémentaires si un nouveau client a été créé
            if ($userCreated) {
                $response['credentials'] = [
                    'email' => $clientData['email'],
                    'temporary_password' => $temporaryPassword,
                    'verification_code' => $verificationCode,
                ];
            }

            return response()->json($response, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Données invalides', 400, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de la création du compte', 500);
        }
    }

    /**
     * Afficher un compte spécifique
     *
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Afficher un compte spécifique",
     *     description="Récupérer les détails d'un compte bancaire spécifique",
     *     operationId="getCompte",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="_links", type="object",
     *                 @OA\Property(property="self", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
     *                     @OA\Property(property="method", type="string", example="GET"),
     *                     @OA\Property(property="rel", type="string", example="self")
     *                 ),
     *                 @OA\Property(property="update", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
     *                     @OA\Property(property="method", type="string", example="PUT"),
     *                     @OA\Property(property="rel", type="string", example="update")
     *                 ),
     *                 @OA\Property(property="delete", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
     *                     @OA\Property(property="method", type="string", example="DELETE"),
     *                     @OA\Property(property="rel", type="string", example="delete")
     *                 ),
     *                 @OA\Property(property="collection", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes"),
     *                     @OA\Property(property="method", type="string", example="GET"),
     *                     @OA\Property(property="rel", type="string", example="collection")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     security={}
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $compte = Compte::with('client')->findOrFail($id);

            // Vérifier les autorisations d'accès
            if ($request->user()) {
                $this->authorize('view', $compte);
            }

            $responseData = new CompteResource($compte);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'delete' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'DELETE',
                    'rel' => 'delete'
                ],
                'collection' => [
                    'href' => url('/api/v1/zeynab-ba/comptes'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            return $this->successResponse($responseData, 'Détails du compte récupérés avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du compte', 500);
        }
    }

    /**
     * Mettre à jour un compte spécifique
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Mettre à jour un compte",
     *     description="Mettre à jour les informations d'un compte bancaire",
     *     operationId="updateCompte",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne"}),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *             @OA\Property(property="motif_blocage", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="_links", type="object",
     *                 @OA\Property(property="self", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes/{id}"),
     *                     @OA\Property(property="method", type="string", example="GET"),
     *                     @OA\Property(property="rel", type="string", example="self")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     security={}
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $compte = Compte::findOrFail($id);

            $validated = $request->validate([
                'type' => 'sometimes|in:cheque,courant,epargne',
                'statut' => 'sometimes|in:actif,bloque,ferme',
                'motif_blocage' => 'nullable|string'
            ]);

            $compte->update($validated);

            $responseData = new CompteResource($compte);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'delete' => [
                    'href' => url('/api/v1/zeynab-ba/comptes/' . $compte->id),
                    'method' => 'DELETE',
                    'rel' => 'delete'
                ],
                'collection' => [
                    'href' => url('/api/v1/zeynab-ba/comptes'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            return $this->successResponse($responseData, 'Compte mis à jour avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Données invalides', 400, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la mise à jour du compte', 500);
        }
    }
    /**
     * Supprimer un compte spécifique
     *
     * @param string $id
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Supprimer un compte",
     *     description="Supprimer un compte bancaire (soft delete)",
     *     operationId="deleteCompte",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(property="_links", type="object",
     *                 @OA\Property(property="collection", type="object",
     *                     @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes"),
     *                     @OA\Property(property="method", type="string", example="GET"),
     *                     @OA\Property(property="rel", type="string", example="collection")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     security={}
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $compte = Compte::findOrFail($id);
            $compte->delete(); // Soft delete

            $responseData = [
                'message' => 'Compte supprimé avec succès',
                '_links' => [
                    'collection' => [
                        'href' => url('/api/v1/comptes'),
                        'method' => 'GET',
                        'rel' => 'collection'
                    ]
                ]
            ];

            return $this->successResponse($responseData, 'Compte supprimé avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la suppression du compte', 500);
        }
    }

    /**
     * Lister les comptes archivés (soft deleted) - Accessible seulement aux admins
     * Les comptes archivés sont consultés depuis le cloud
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function archives(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'statut', 'search', 'sort', 'order']);
            $limit = min($request->get('limit', 10), 100);

            $comptes = Compte::with('client')
                ->onlyTrashed() // Récupère seulement les soft deleted
                ->filterAndSort($filters)
                ->paginate($limit);

            // Note: Dans un vrai scénario cloud, cette méthode ferait appel à un service externe
            // Ici nous simulons en utilisant les données locales soft deleted

            return $this->paginatedResponse(
                CompteResource::collection($comptes),
                $comptes,
                'Liste des comptes archivés récupérée depuis le cloud'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des comptes archivés', 500);
        }
    }
}
