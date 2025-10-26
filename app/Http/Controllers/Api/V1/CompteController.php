<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompteController extends Controller
{
    use ApiResponseTrait;
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'statut', 'search', 'sort', 'order']);

            // Pagination
            $limit = min($request->get('limit', 10), 100);

            $query = Compte::with('client')
                ->filterAndSort($filters);

            // Si c'est un client (pas admin), filtrer uniquement ses comptes
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $client = $request->user()->client;
                if ($client) {
                    $query->where('client_id', $client->id);
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
            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'type' => 'required|in:cheque,courant,epargne',
                'devise' => 'required|in:FCFA,EUR,USD',
                'solde_initial' => 'numeric|min:0',
                'statut' => 'in:actif,bloque,ferme'
            ]);

            $compte = Compte::create($validated);

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

            return $this->successResponse($responseData, 'Compte créé avec succès', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Données invalides', 400, $e->errors());
        } catch (\Exception $e) {
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

            // Vérifier que le client ne peut voir que ses propres comptes
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $client = $request->user()->client;
                if (!$client || $compte->client_id !== $client->id) {
                    return $this->errorResponse('Accès non autorisé à ce compte', 403);
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
