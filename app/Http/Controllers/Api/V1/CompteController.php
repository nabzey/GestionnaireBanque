<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
