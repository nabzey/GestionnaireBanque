<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'statut', 'compte_id', 'search', 'sort', 'order']);
            $limit = min($request->get('limit', 10), 100);

            $query = Transaction::with('compte.client')
                ->filterAndSort($filters);

            // Si c'est un client (pas admin), filtrer uniquement ses transactions
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $client = $request->user()->client;
                if ($client) {
                    $query->whereHas('compte', function ($q) use ($client) {
                        $q->where('client_id', $client->id);
                    });
                } else {
                    return $this->errorResponse('Client non trouvé', 404);
                }
            }

            $transactions = $query->paginate($limit);

            return $this->paginatedResponse(
                TransactionResource::collection($transactions),
                $transactions,
                'Liste des transactions récupérée avec succès'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des transactions', 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $transaction = Transaction::with('compte.client')->findOrFail($id);

            // Vérifier que le client ne peut voir que ses propres transactions
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $client = $request->user()->client;
                if (!$client || $transaction->compte->client_id !== $client->id) {
                    return $this->errorResponse('Accès non autorisé à cette transaction', 403);
                }
            }

            $responseData = new TransactionResource($transaction);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/transactions/' . $transaction->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'collection' => [
                    'href' => url('/api/v1/transactions'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ],
                'compte' => [
                    'href' => url('/api/v1/comptes/' . $transaction->compte_id),
                    'method' => 'GET',
                    'rel' => 'compte'
                ]
            ];

            return $this->successResponse($responseData, 'Détails de la transaction récupérés avec succès');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Transaction non trouvée', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération de la transaction', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'compte_id' => 'required|exists:comptes,id',
                'type' => 'required|in:depot,retrait,virement,transfert',
                'montant' => 'required|numeric|min:0.01',
                'devise' => 'required|in:FCFA,EUR,USD',
                'description' => 'nullable|string|max:500',
            ]);

            // Vérifier que le compte appartient au client (si c'est un client)
            if ($request->user() && !$request->user()->hasRole('admin')) {
                $client = $request->user()->client;
                $compte = \App\Models\Compte::find($validated['compte_id']);
                if (!$client || !$compte || $compte->client_id !== $client->id) {
                    return $this->errorResponse('Accès non autorisé à ce compte', 403);
                }
            }

            $transaction = Transaction::create($validated);

            $responseData = new TransactionResource($transaction);
            $responseData['_links'] = [
                'self' => [
                    'href' => url('/api/v1/transactions/' . $transaction->id),
                    'method' => 'GET',
                    'rel' => 'self'
                ],
                'collection' => [
                    'href' => url('/api/v1/transactions'),
                    'method' => 'GET',
                    'rel' => 'collection'
                ],
                'compte' => [
                    'href' => url('/api/v1/comptes/' . $transaction->compte_id),
                    'method' => 'GET',
                    'rel' => 'compte'
                ]
            ];

            return $this->successResponse($responseData, 'Transaction créée avec succès', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Données invalides', 400, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la création de la transaction', 500);
        }
    }
}
