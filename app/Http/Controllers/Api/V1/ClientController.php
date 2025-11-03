<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            // Vérifier les autorisations : seuls les admins peuvent voir tous les clients
            if ($request->user() && !$request->user()->hasRole('admin')) {
                return $this->errorResponse('Accès non autorisé', 403);
            }

            $filters = $request->only(['statut', 'search', 'sort', 'order']);
            $limit = min($request->get('limit', 10), 100);

            $clients = Client::with('comptes')
                ->filterAndSort($filters)
                ->paginate($limit);

            return $this->paginatedResponse(
                ClientResource::collection($clients),
                $clients
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des clients', 500);
        }
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $client = Client::create($validated);

            $responseData = new ClientResource($client);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'delete' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'DELETE',
                    'rel' => 'delete'
                ],
                'collection' => [
                    'href' => url('/api/v1/clients'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            return $this->successResponse($responseData, 'Client créé avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la création du client', 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $client = Client::with('comptes')->findOrFail($id);

            // Vérifier les autorisations : les clients ne peuvent voir que leur propre profil
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $userClient = $request->user()->client;
                if (!$userClient || $userClient->id !== $client->id) {
                    return $this->errorResponse('Accès non autorisé à ce profil client', 403);
                }
            }

            $responseData = new ClientResource($client);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'delete' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'DELETE',
                    'rel' => 'delete'
                ],
                'collection' => [
                    'href' => url('/api/v1/clients'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            return $this->successResponse($responseData, 'Détails du client récupérés avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Client non trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du client', 500);
        }
    }

    public function update(StoreClientRequest $request, string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);

            // Vérifier les autorisations : les clients ne peuvent modifier que leur propre profil
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $userClient = $request->user()->client;
                if (!$userClient || $userClient->id !== $client->id) {
                    return $this->errorResponse('Accès non autorisé à la modification de ce profil client', 403);
                }
            }

            $validated = $request->validated();

            $client->update($validated);

            $responseData = new ClientResource($client);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'delete' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'DELETE',
                    'rel' => 'delete'
                ],
                'collection' => [
                    'href' => url('/api/v1/clients'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            return $this->successResponse($responseData, 'Client mis à jour avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Client non trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la mise à jour du client', 500);
        }
    }

    /**
     * Récupérer un client par numéro de téléphone
     */
    public function getByTelephone(Request $request, string $telephone): JsonResponse
    {
        try {
            // Validation du format de téléphone passé en paramètre d'URL
            if (!preg_match('/^\+221\d{9}$/', $telephone)) {
                return $this->errorResponse('Format de numéro de téléphone invalide', 400);
            }

            $client = Client::with('comptes')->telephone($telephone)->first();

            if (!$client) {
                return $this->errorResponse('Client non trouvé avec ce numéro de téléphone', 404);
            }

            $responseData = new ClientResource($client);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'update' => [
                    'href' => url('/api/v1/clients/' . $client->id),
                    'method' => 'PUT',
                    'rel' => 'update'
                ],
                'collection' => [
                    'href' => url('/api/v1/clients'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ]
            ];

            return $this->successResponse($responseData, 'Client récupéré avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Format de numéro de téléphone invalide', 400, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du client', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);

            // Vérifier les autorisations : seuls les admins peuvent supprimer des clients
            if (request()->user() && !request()->user()->hasRole('admin')) {
                return $this->errorResponse('Accès non autorisé à la suppression de clients', 403);
            }

            $client->delete(); // Soft delete

            $responseData = [
                'message' => 'Client supprimé avec succès',
                '_links' => [
                    'collection' => [
                        'href' => url('/api/v1/clients'),
                        'method' => 'GET',
                        'rel' => 'collection'
                    ]
                ]
            ];

            return $this->successResponse($responseData, 'Client supprimé avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Client non trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la suppression du client', 500);
        }
    }
}
