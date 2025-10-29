<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Exceptions\CompteNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use App\Services\NeonService;
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

    protected NeonService $neonService;

    public function __construct(NeonService $neonService)
    {
        $this->neonService = $neonService;
    }

    /**
     * Envoyer une notification SMS
     */
    private function sendSmsNotification(string $telephone, string $message): void
    {
        try {
            // Utiliser le service Twilio existant
            $twilioService = app(\App\Services\TwilioService::class);
            $twilioService->sendSms($telephone, $message);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas échouer l'opération
            Log::warning("Erreur envoi SMS: " . $e->getMessage());
            throw $e; // Re-throw pour permettre la gestion au niveau supérieur si nécessaire
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/comptes",
     *     summary="Lister tous les comptes actifs",
     *     description="Récupère la liste paginée des comptes bancaires actifs. Les comptes bloqués sont stockés dans Neon et ne sont pas visibles ici.",
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
     *         @OA\Schema(type="string", enum={"cheque", "epargne"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut (toujours 'actif' pour cet endpoint)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif"})
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
     *         @OA\Schema(type="string", enum={"numeroCompte", "solde", "dateCreation", "statut", "titulaire"})
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
     *         description="Liste des comptes actifs récupérée avec succès",
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
                ->where('statut', 'actif')
                ->filterAndSort($filters);

            // Filtrage par utilisateur désactivé pour faciliter les tests
            // Les comptes sont maintenant visibles par tous sans authentification
            // if ($request->user() && !$request->user()->hasRole('admin')) {
            //     $client = $request->user()->client;
            //     if ($client) {
            //         $query->byClientId($client->id);
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
     *             @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, description="Type de compte", example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", format="float", minimum=10000, description="Solde initial du compte", example=25000),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, description="Devise du compte", example="FCFA"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte (seuls les comptes épargne peuvent être bloqués)", example="actif", default="actif"),
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

                // Vérifier si le téléphone est déjà utilisé
                $existingClient = Client::where('telephone', $validated['telephone'])->first();
                if ($existingClient) {
                    throw new \Exception("Un client avec ce numéro de téléphone existe déjà: {$existingClient->nom_complet}");
                }

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

            $responseData = new CompteResource($compte);
            $response = $this->successResponse($responseData, 'Compte créé avec succès', null, 201);

            // Ajouter les informations supplémentaires si un nouveau client a été créé
            if ($userCreated) {
                $response = $response->getData(true);
                $response['credentials'] = [
                    'email' => $clientData['email'],
                    'temporary_password' => $temporaryPassword,
                    'verification_code' => $code,
                ];
                return response()->json($response, 201);
            }

            return $response;

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error: ' . json_encode($e->errors()));
            return $this->errorResponse('Données invalides', 400, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du compte: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ' . json_encode($request->all()));

            // Gestion spécifique des erreurs de contrainte unique
            if (str_contains($e->getMessage(), 'duplicate key value violates unique constraint')) {
                if (str_contains($e->getMessage(), 'clients_email_unique')) {
                    return $this->errorResponse('Un client avec cet email existe déjà', 422);
                }
                if (str_contains($e->getMessage(), 'clients_telephone_unique')) {
                    return $this->errorResponse('Un client avec ce numéro de téléphone existe déjà', 422);
                }
            }

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
     *         response=403,
     *         description="Accès non autorisé (temporairement désactivé)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas")
     *             )
     *         )
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
            // Validation de l'ID
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id)) {
                throw new CompteNotFoundException();
            }

            // 1. Recherche en base locale d'abord (comptes actifs uniquement)
            $compte = Compte::with('client')->find($id);

            if ($compte) {
                // Ajouter l'indicateur de source
                $compte->source = 'local';

                return $this->successResponse(
                    new CompteResource($compte),
                    'Détails du compte récupérés avec succès'
                );
            }

            // 2. Si pas trouvé en local, rechercher dans Neon (comptes bloqués/archivés)
            if ($this->neonService->isConnected()) {
                $compteNeon = $this->neonService->findCompte($id);

                if ($compteNeon) {
                    // Créer un objet Compte temporaire pour la resource
                    $compteObject = new Compte($compteNeon);
                    $compteObject->source = 'neon';

                    // Ajouter les relations manuellement
                    if (isset($compteNeon['client'])) {
                        $client = new Client($compteNeon['client']);
                        $compteObject->setRelation('client', $client);
                    }

                    return $this->successResponse(
                        new CompteResource($compteObject),
                        'Détails du compte récupérés depuis les archives'
                    );
                }
            }

            // 3. Compte non trouvé dans aucune source
            throw new CompteNotFoundException();

        } catch (CompteNotFoundException $e) {
            return $e->render();
        } catch (\Exception $e) {
            Log::error('Erreur dans show compte: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération du compte', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/zeynab-ba/comptes/{id}",
     *     summary="Mettre à jour un compte ou gérer blocage/déblocage",
     *     description="Met à jour les informations d'un compte bancaire. Permet également de bloquer (archiver dans Neon) ou débloquer (restaurer depuis Neon) un compte épargne.",
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
     *             @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, description="Type de compte", example="cheque"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte. 'bloque' archive dans Neon, 'actif' restaure depuis Neon", example="bloque"),
     *             @OA\Property(property="motif_blocage", type="string", nullable=true, description="Motif du blocage (requis si statut=bloque)", example="Inactivité prolongée"),
     *             @OA\Property(property="duree_blocage", type="integer", nullable=true, description="Durée du blocage en jours (requis si statut=bloque, 1-365 jours)", example=30)
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
     *         description="Données invalides ou opération non autorisée",
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
                'type' => 'sometimes|in:cheque,epargne',
                'statut' => 'sometimes|in:actif,bloque,ferme',
                'motif_blocage' => 'nullable|string',
                'duree_blocage' => 'nullable|integer|min:1|max:365'
            ]);

            $compte = Compte::findOrFail($id);

            // Gestion du changement de statut
            if (isset($validated['statut'])) {
                if ($validated['statut'] === 'bloque') {
                    // BLOQUER un compte - uniquement pour comptes épargne
                    if ($compte->type !== 'epargne') {
                        return $this->errorResponse('Impossible de bloquer ce compte : seuls les comptes épargne peuvent être bloqués', 400);
                    }

                    if ($compte->statut !== 'actif') {
                        return $this->errorResponse('Impossible de bloquer ce compte : le compte doit être actif', 400);
                    }

                    if (!isset($validated['motif_blocage']) || empty($validated['motif_blocage'])) {
                        return $this->errorResponse('Le motif de blocage est requis', 400);
                    }

                    if (!isset($validated['duree_blocage'])) {
                        return $this->errorResponse('La durée de blocage est requise', 400);
                    }

                    // Calculer les dates de blocage
                    $dateDebut = now();
                    $dateFin = $dateDebut->copy()->addDays($validated['duree_blocage']);

                    $validated['date_debut_blocage'] = $dateDebut;
                    $validated['date_fin_blocage'] = $dateFin;

                    // Pour le moment, on ne touche pas à Neon - garder en local
                    Log::info("Compte {$compte->numero} bloqué localement (Neon désactivé pour tests)");

                    // Envoyer notification SMS de blocage
                    try {
                        $message = "Votre compte {$compte->numero} a été bloqué pour motif: {$validated['motif_blocage']}. Durée: {$validated['duree_blocage']} jours.";
                        $this->sendSmsNotification($compte->telephone, $message);
                        Log::info("SMS de blocage envoyé au {$compte->telephone}");
                    } catch (\Exception $e) {
                        Log::warning("Erreur envoi SMS blocage: " . $e->getMessage());
                    }

                } elseif ($validated['statut'] === 'actif' && $compte->statut === 'bloque') {
                    // DÉBLOQUER un compte - pour le moment depuis local uniquement
                    // Vérifier que le déblocage est possible (dans les 2h)
                    if ($compte->date_debut_blocage && $compte->date_debut_blocage->diffInHours(now()) > 2) {
                        return $this->errorResponse('Le délai de 2 heures pour le déblocage est dépassé', 400);
                    }

                    // Nettoyer les champs de blocage
                    $validated['motif_blocage'] = null;
                    $validated['date_debut_blocage'] = null;
                    $validated['date_fin_blocage'] = null;

                    Log::info("Compte {$compte->numero} débloqué localement");

                    // Envoyer notification SMS de déblocage
                    try {
                        $message = "Votre compte {$compte->numero} a été débloqué avec succès. Vous pouvez maintenant utiliser votre compte normalement.";
                        $this->sendSmsNotification($compte->telephone, $message);
                        Log::info("SMS de déblocage envoyé au {$compte->telephone}");
                    } catch (\Exception $e) {
                        Log::warning("Erreur envoi SMS déblocage: " . $e->getMessage());
                    }

                } elseif ($validated['statut'] === 'ferme') {
                    // FERMER un compte - changement de statut simple
                    if ($compte->statut === 'bloque') {
                        return $this->errorResponse('Impossible de fermer un compte bloqué', 400);
                    }

                    Log::info("Compte {$compte->numero} fermé");

                } elseif ($validated['statut'] === 'actif') {
                    // RÉACTIVER un compte fermé
                    if ($compte->statut !== 'ferme') {
                        return $this->errorResponse('Impossible de réactiver ce compte : seul un compte fermé peut être réactivé', 400);
                    }

                    Log::info("Compte {$compte->numero} réactivé");

                } else {
                    return $this->errorResponse('Changement de statut non autorisé', 400);
                }
            } else {
                // Modification normale (sans changement de statut)
                if ($compte->statut === 'bloque') {
                    return $this->errorResponse('Impossible de modifier un compte bloqué', 400);
                }
                if ($compte->statut === 'ferme') {
                    return $this->errorResponse('Impossible de modifier un compte fermé', 400);
                }
            }

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
     *     description="Supprime un compte bancaire (soft delete). Les comptes bloqués ne peuvent pas être supprimés.",
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
     *                         @OA\Property(property="rel", type="string", example="collection")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Impossible de supprimer un compte bloqué",
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
    public function destroy(string $id): JsonResponse
    {
        try {
            $compte = Compte::findOrFail($id);

            // Vérifier que le compte n'est pas bloqué
            if ($compte->isBlocked()) {
                return $this->errorResponse('Impossible de supprimer un compte bloqué', 400);
            }

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
     *     summary="Lister les comptes supprimés (soft deleted)",
     *     description="Récupère la liste paginée des comptes supprimés (soft deleted) stockés en base locale.",
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
     *         @OA\Schema(type="string", enum={"cheque", "epargne"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut au moment de la suppression",
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
     *         @OA\Schema(type="string", enum={"numeroCompte", "solde", "dateCreation", "statut", "titulaire"})
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
     *         description="Liste des comptes supprimés récupérée avec succès",
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

            // Utiliser withoutGlobalScopes() pour contourner le CompteScope global
            $comptes = Compte::with('client')
                ->withoutGlobalScopes() // Désactive le scope global qui filtre deleted_at
                ->onlyTrashed() // Récupère seulement les soft deleted
                ->filterAndSort($filters)
                ->paginate($limit);

            return $this->paginatedResponse(
                CompteResource::collection($comptes),
                $comptes,
                'Liste des comptes supprimés récupérée avec succès'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des comptes archivés', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/zeynab-ba/comptes-neon",
     *     summary="Lister les comptes bloqués dans Neon",
     *     description="Récupère la liste paginée des comptes bloqués stockés dans la base Neon. Ces comptes peuvent être débloqués dans les 2h suivant leur blocage.",
     *     operationId="getNeonComptes",
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
     *         @OA\Schema(type="string", enum={"cheque", "epargne"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut (toujours 'bloque' pour cet endpoint)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"bloque"})
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
     *         @OA\Schema(type="string", enum={"numeroCompte", "solde", "dateCreation", "statut", "titulaire"})
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
     *         description="Liste des comptes bloqués récupérée avec succès depuis Neon",
     *         @OA\JsonContent(ref="#/components/schemas/ComptesResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur ou base Neon indisponible",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function neon(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'statut', 'search', 'sort', 'order']);
            $limit = min($request->get('limit', 10), 100);

            // Forcer le filtre statut à 'bloque' pour cet endpoint
            $filters['statut'] = 'bloque';

            $neonService = app(NeonService::class);
            $result = $neonService->listComptes($filters, $limit);

            // Transformer les données pour le format uniforme
            $comptesData = collect($result['data'])->map(function ($compte) {
                return new CompteResource(collect($compte)->merge(['source' => 'neon']));
            });

            // Créer un objet paginator factice pour la compatibilité
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $comptesData,
                $result['total'],
                $result['pagination']['itemsPerPage'],
                $result['pagination']['currentPage'],
                ['path' => $request->url(), 'pageName' => 'page']
            );

            return $this->paginatedResponse(
                $comptesData,
                $paginator,
                'Liste des comptes bloqués récupérée depuis Neon'
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des comptes Neon: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des comptes bloqués depuis Neon', 500);
        }
    }
}
