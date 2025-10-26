<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Format de réponse API standard
     *
     * @param bool $success
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function apiResponse(bool $success, $data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => $success,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Réponse de succès
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        return $this->apiResponse(true, $data, $message, $statusCode);
    }

    /**
     * Réponse d'erreur
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, $errors = null): JsonResponse
    {
        $data = $errors ? ['errors' => $errors] : null;
        return $this->apiResponse(false, $data, $message, $statusCode);
    }

    /**
     * Réponse avec pagination et HATEOAS
     *
     * @param mixed $data
     * @param mixed $paginator
     * @param string|null $message
     * @return JsonResponse
     */
    protected function paginatedResponse($data, $paginator, string $message = null): JsonResponse
    {
        $pagination = [
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'totalItems' => $paginator->total(),
            'itemsPerPage' => $paginator->perPage(),
            'hasNext' => $paginator->hasMorePages(),
            'hasPrevious' => $paginator->currentPage() > 1,
        ];

        $links = [
            'self' => [
                'href' => $paginator->url($paginator->currentPage()),
                'method' => 'GET',
                'rel' => 'self'
            ],
            'first' => [
                'href' => $paginator->url(1),
                'method' => 'GET',
                'rel' => 'first'
            ],
            'last' => [
                'href' => $paginator->url($paginator->lastPage()),
                'method' => 'GET',
                'rel' => 'last'
            ],
        ];

        if ($paginator->hasMorePages()) {
            $links['next'] = [
                'href' => $paginator->nextPageUrl(),
                'method' => 'GET',
                'rel' => 'next'
            ];
        }

        if ($paginator->currentPage() > 1) {
            $links['previous'] = [
                'href' => $paginator->previousPageUrl(),
                'method' => 'GET',
                'rel' => 'previous'
            ];
        }

        // Ajouter des liens HATEOAS pour les actions sur les comptes individuels
        $embedded = [];
        foreach ($data as $compte) {
            $embedded[] = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numero,
                'titulaire' => $compte->client ? $compte->client->nom_complet : null,
                'type' => $compte->type,
                'solde' => $compte->solde_initial,
                'devise' => $compte->devise,
                'dateCreation' => $compte->created_at->toISOString(),
                'statut' => $compte->statut,
                'motifBlocage' => $compte->motif_blocage,
                'metadata' => $compte->metadata,
                '_links' => [
                    'self' => [
                        'href' => url('/api/v1/comptes/' . $compte->id),
                        'method' => 'GET',
                        'rel' => 'self'
                    ],
                    'update' => [
                        'href' => url('/api/v1/comptes/' . $compte->id),
                        'method' => 'PUT',
                        'rel' => 'update'
                    ],
                    'delete' => [
                        'href' => url('/api/v1/comptes/' . $compte->id),
                        'method' => 'DELETE',
                        'rel' => 'delete'
                    ]
                ]
            ];
        }

        $responseData = [
            '_links' => $links,
            '_embedded' => [
                'comptes' => $embedded
            ],
            'pagination' => $pagination,
        ];

        return $this->successResponse($responseData, $message);
    }
}