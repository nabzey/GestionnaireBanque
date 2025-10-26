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

            return $this->successResponse($responseData, 'Client créé avec succès', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la création du client', 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $client = Client::with('comptes')->findOrFail($id);

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

    public function destroy(string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);
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
