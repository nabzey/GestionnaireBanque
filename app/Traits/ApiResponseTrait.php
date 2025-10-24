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
     * Réponse avec pagination
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
            'self' => $paginator->url($paginator->currentPage()),
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
        ];

        if ($paginator->hasMorePages()) {
            $links['next'] = $paginator->nextPageUrl();
        }

        if ($paginator->currentPage() > 1) {
            $links['previous'] = $paginator->previousPageUrl();
        }

        $responseData = [
            'data' => $data,
            'pagination' => $pagination,
            'links' => $links,
        ];

        return $this->successResponse($responseData, $message);
    }
}