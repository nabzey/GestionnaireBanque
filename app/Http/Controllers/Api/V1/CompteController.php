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

    /**
     * Lister tous les comptes avec filtres et pagination
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @queryParam page int Numéro de page (default: 1)
     * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100)
     * @queryParam type string Filtrer par type (epargne, cheque)
     * @queryParam statut string Filtrer par statut (actif, bloque, ferme)
     * @queryParam search string Recherche par titulaire ou numéro
     * @queryParam sort string Tri (dateCreation, solde, titulaire)
     * @queryParam order string Ordre (asc, desc)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['type', 'statut', 'search', 'sort', 'order']);

            // Pagination
            $limit = min($request->get('limit', 10), 100);

            $comptes = Compte::with('admin')
                ->filterAndSort($filters)
                ->paginate($limit);

            return $this->paginatedResponse(
                CompteResource::collection($comptes),
                $comptes
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des comptes', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
