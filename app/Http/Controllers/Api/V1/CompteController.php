<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/comptes",
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste paginée des comptes bancaires.",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"cheque", "courant", "epargne"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par numéro de compte ou titulaire",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"numeroCompte", "solde", "dateCreation", "statut"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/ComptesResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Test de connexion DB avant la requête
            try {
                \Illuminate\Support\Facades\DB::connection()->getPdo();
                Log::info('Connexion DB OK dans CompteController');
            } catch (\Exception $dbException) {
                Log::error('Erreur de connexion DB: ' . $dbException->getMessage());
                return $this->errorResponse('Erreur de connexion à la base de données', 500);
            }

            $filters = $request->only(['type', 'statut', 'search', 'sort', 'order']);

            // Pagination
            $limit = min($request->get('limit', 10), 100);

            $query = Compte::with('client')
                ->filterAndSort($filters);

            // Temporairement désactivé : vérification d'authentification
            // // Si c'est un client (pas admin), filtrer uniquement ses comptes
            // if ($request->user() && !$request->user()->hasRole('admin')) {
            //     $client = $request->user()->client;
            //     if ($client) {
            //         $query->where('client_id', $client->id);
            //     } else {
            //         return $this->errorResponse('Client non trouvé', 404);
            //     }
            // }

            $comptes = $query->paginate($limit);

            return $this->paginatedResponse(
                CompteResource::collection($comptes),
                $comptes,
                'Liste des comptes récupérée avec succès'
            );

        } catch (\Exception $e) {
            Log::error('Erreur dans index comptes: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return $this->errorResponse('Erreur lors de la récupération des comptes: ' . $e->getMessage(), 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/v1/zeynab-ba/comptes",
     *     summary="Créer un nouveau compte",
     *     description="Crée un nouveau compte bancaire.",
     *     operationId="createCompte",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "type", "soldeInitial", "devise"},
     *             @OA\Property(property="client_id", type="string", format="uuid", description="ID du client existant (optionnel)", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="telephone", type="string", description="Numéro de téléphone pour les notifications SMS", example="+221771234567"),
     *             @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne"}, description="Type de compte", example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", format="float", minimum=10000, description="Solde initial du compte", example=25000),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, description="Devise du compte", example="FCFA"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte", example="actif", default="actif"),
     *             @OA\Property(property="client", type="object", description="Informations du nouveau client (requis si client_id non fourni)",
     *                 @OA\Property(property="titulaire", type="string", description="Nom complet du titulaire", example="Amadou Diop"),
     *                 @OA\Property(property="nci", type="string", description="Numéro NCI", example="1234567890123"),
     *                 @OA\Property(property="email", type="string", format="email", description="Email du client", example="amadou.diop@example.com"),
     *                 @OA\Property(property="telephone", type="string", description="Téléphone du client", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", description="Adresse du client", example="123 Rue de la Paix, Dakar")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteWithLinks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $clientData = $validated['client'];

            // Vérifier si le client existe ou le créer
            if (empty($clientData['id'])) {
                // Créer un nouveau client
                $temporaryPassword = User::generateTemporaryPassword();
                $code = Compte::generateCode();

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
                $client = Client::findOrFail($clientData['id']);
                $clientId = $client->id;
                $temporaryPassword = null;
                $code = null;
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
                'statut' => $validated['statut'] ?? 'actif',
            ]);

            DB::commit();

            // Envoyer les notifications après la création réussie
            if ($userCreated) {
                try {
                    // Déclencher l'événement pour les notifications
                    \App\Events\SendClientNotification::dispatch($client, $compte, $temporaryPassword, $code);
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
                    'verification_code' => $code,
                ];
            }

            return response()->json($response, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error: ' . json_encode($e->errors()));
            return $this->errorResponse('Données invalides', 400, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du compte: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));
            return $this->errorResponse('Erreur lors de la création du compte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     summary="Afficher un compte spécifique",
     *     description="Récupère les détails d'un compte bancaire spécifique.",
     *     operationId="getCompte",
     *     tags={"Comptes"},
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
     *             @OA\Property(property="message", type="string", example="Détails du compte récupérés avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteWithLinks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $compte = Compte::with('client')->findOrFail($id);

            // Temporairement désactivé : vérification d'authentification
            // // Vérifier que le client ne peut voir que ses propres comptes
            // if ($request->user() && !$request->user()->hasRole('admin')) {
            //     $client = $request->user()->client;
            //     if (!$client || $compte->client_id !== $client->id) {
            //         return $this->errorResponse('Accès non autorisé à ce compte', 403);
            //     }
            // }

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
     * @OA\Put(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     summary="Mettre à jour un compte",
     *     description="Met à jour les informations d'un compte bancaire.",
     *     operationId="updateCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte à mettre à jour",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne"}, description="Type de compte"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte"),
     *             @OA\Property(property="motif_blocage", type="string", nullable=true, description="Motif du blocage si le compte est bloqué")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/CompteWithLinks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'sometimes|in:cheque,courant,epargne',
                'statut' => 'sometimes|in:actif,bloque,ferme',
                'motif_blocage' => 'nullable|string'
            ]);

            $compte = Compte::findOrFail($id);
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
     * @OA\Delete(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     summary="Supprimer un compte",
     *     description="Supprime un compte bancaire (soft delete).",
     *     operationId="deleteCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte à supprimer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *                 @OA\Property(property="_links", type="object",
     *                     @OA\Property(property="collection", type="object",
     *                         @OA\Property(property="href", type="string", example="/api/v1/zeynab-ba/comptes"),
     *                         @OA\Property(property="method", type="string", example="GET"),
     *                         @OA\Property(property="rel", type="string", example="collection")
     *                     )
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
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
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
                        'href' => url('/api/v1/zeynab-ba/comptes'),
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
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/comptes-archives",
     *     summary="Lister les comptes archivés",
     *     description="Récupère la liste paginée des comptes archivés (soft deleted).",
     *     operationId="getArchivedComptes",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"cheque", "courant", "epargne"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par numéro de compte ou titulaire",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"numeroCompte", "solde", "dateCreation", "statut"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés récupérée avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/ComptesResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
