<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NeonService
{
    /**
     * Rechercher un compte dans la base Neon (serverless)
     *
     * @param string $compteId
     * @return array|null
     */
    public function findCompte(string $compteId): ?array
    {
        try {
            // Configuration de la connexion Neon
            $neonConnection = DB::connection('neon'); // À configurer dans database.php

            // Recherche dans les comptes bloqués/fermés/archivés
            $compte = $neonConnection->table('comptes')
                ->leftJoin('clients', 'comptes.client_id', '=', 'clients.id')
                ->where('comptes.id', $compteId)
                ->where(function ($query) {
                    $query->whereIn('comptes.statut', ['bloque', 'ferme'])
                          ->orWhereNotNull('comptes.deleted_at');
                })
                ->select([
                    'comptes.*',
                    'clients.nom as client_nom',
                    'clients.prenom as client_prenom',
                    'clients.email as client_email'
                ])
                ->first();

            if ($compte) {
                return [
                    'id' => $compte->id,
                    'numero' => $compte->numero,
                    'solde_initial' => $compte->solde_initial,
                    'devise' => $compte->devise,
                    'type' => $compte->type,
                    'statut' => $compte->statut,
                    'motif_blocage' => $compte->motif_blocage,
                    'metadata' => json_decode($compte->metadata, true),
                    'client_id' => $compte->client_id,
                    'telephone' => $compte->telephone,
                    'created_at' => $compte->created_at,
                    'updated_at' => $compte->updated_at,
                    'deleted_at' => $compte->deleted_at,
                    'client' => [
                        'id' => $compte->client_id,
                        'nom' => $compte->client_nom,
                        'prenom' => $compte->client_prenom,
                        'email' => $compte->client_email,
                        'nom_complet' => trim($compte->client_nom . ' ' . $compte->client_prenom)
                    ],
                    'source' => 'neon'
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche dans Neon: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Liste les comptes de Neon avec filtres et pagination
     */
    public function listComptes(array $filters = [], int $limit = 10): array
    {
        try {
            if (!$this->isConnected()) {
                return [
                    'data' => [],
                    'total' => 0,
                    'pagination' => [
                        'currentPage' => 1,
                        'totalPages' => 0,
                        'totalItems' => 0,
                        'itemsPerPage' => $limit,
                        'hasNext' => false,
                        'hasPrevious' => false
                    ]
                ];
            }

            $neonConnection = DB::connection('neon');

            $query = $neonConnection->table('comptes')
                ->leftJoin('clients', 'comptes.client_id', '=', 'clients.id')
                ->where(function ($query) {
                    $query->whereIn('comptes.statut', ['bloque', 'ferme'])
                          ->orWhereNotNull('comptes.deleted_at');
                });

            // Appliquer les filtres
            if (isset($filters['type']) && $filters['type']) {
                $query->where('comptes.type', $filters['type']);
            }

            if (isset($filters['statut']) && $filters['statut']) {
                $query->where('comptes.statut', $filters['statut']);
            }

            if (isset($filters['search']) && $filters['search']) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('comptes.numero', 'like', "%{$search}%")
                      ->orWhere('clients.nom', 'like', "%{$search}%")
                      ->orWhere('clients.prenom', 'like', "%{$search}%")
                      ->orWhere('clients.email', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortField = $filters['sort'] ?? 'created_at';
            $sortOrder = $filters['order'] ?? 'desc';

            $sortMapping = [
                'numeroCompte' => 'comptes.numero',
                'dateCreation' => 'comptes.created_at',
                'solde' => 'comptes.solde_initial',
                'titulaire' => 'clients.nom',
            ];

            $actualSortField = $sortMapping[$sortField] ?? 'comptes.' . $sortField;

            if ($actualSortField === 'clients.nom') {
                $query->orderBy('clients.nom', $sortOrder);
            } else {
                $query->orderBy($actualSortField, $sortOrder);
            }

            // Pagination
            $total = $query->count();
            $comptes = $query->select([
                'comptes.*',
                'clients.nom as client_nom',
                'clients.prenom as client_prenom',
                'clients.email as client_email'
            ])->paginate($limit);

            // Transformer les données
            $data = [];
            foreach ($comptes as $compte) {
                $data[] = [
                    'id' => $compte->id,
                    'numero' => $compte->numero,
                    'solde_initial' => $compte->solde_initial,
                    'devise' => $compte->devise,
                    'type' => $compte->type,
                    'statut' => $compte->statut,
                    'motif_blocage' => $compte->motif_blocage,
                    'date_fin_blocage' => $compte->date_fin_blocage,
                    'metadata' => json_decode($compte->metadata, true),
                    'client_id' => $compte->client_id,
                    'telephone' => $compte->telephone,
                    'created_at' => $compte->created_at,
                    'updated_at' => $compte->updated_at,
                    'deleted_at' => $compte->deleted_at,
                    'client' => [
                        'id' => $compte->client_id,
                        'nom' => $compte->client_nom,
                        'prenom' => $compte->client_prenom,
                        'email' => $compte->client_email,
                        'nom_complet' => trim($compte->client_nom . ' ' . $compte->client_prenom)
                    ]
                ];
            }

            return [
                'data' => $data,
                'total' => $total,
                'pagination' => [
                    'currentPage' => $comptes->currentPage(),
                    'totalPages' => $comptes->lastPage(),
                    'totalItems' => $total,
                    'itemsPerPage' => $comptes->perPage(),
                    'hasNext' => $comptes->hasMorePages(),
                    'hasPrevious' => $comptes->currentPage() > 1
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors de la liste des comptes Neon: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 0,
                    'totalItems' => 0,
                    'itemsPerPage' => $limit,
                    'hasNext' => false,
                    'hasPrevious' => false
                ]
            ];
        }
    }

    /**
     * Archiver un compte dans Neon
     *
     * @param array $compteData
     * @return bool
     */
    public function archiveCompte(array $compteData): bool
    {
        try {
            if (!$this->isConnected()) {
                Log::error('Neon indisponible pour archivage');
                return false;
            }

            $neonConnection = DB::connection('neon');

            // Vérifier si le compte existe déjà dans Neon
            $existing = $neonConnection->table('comptes')->where('id', $compteData['id'])->first();

            if ($existing) {
                // Mettre à jour si existe
                $neonConnection->table('comptes')
                    ->where('id', $compteData['id'])
                    ->update($compteData);
            } else {
                // Insérer si n'existe pas
                $neonConnection->table('comptes')->insert($compteData);
            }

            Log::info("Compte {$compteData['numero']} archivé dans Neon");
            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage dans Neon: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restaurer un compte depuis Neon vers la base locale
     *
     * @param string $compteId
     * @return bool
     */
    public function restoreCompte(string $compteId): bool
    {
        try {
            if (!$this->isConnected()) {
                Log::error('Neon indisponible pour restauration');
                return false;
            }

            $neonConnection = DB::connection('neon');
            $localConnection = DB::connection('mysql');

            // Récupérer le compte depuis Neon
            $compteNeon = $neonConnection->table('comptes')
                ->where('id', $compteId)
                ->first();

            if (!$compteNeon) {
                Log::error("Compte {$compteId} non trouvé dans Neon");
                return false;
            }

            // Préparer les données pour la restauration (nettoyer les champs de blocage)
            $compteData = [
                'statut' => 'actif',
                'motif_blocage' => null,
                'date_debut_blocage' => null,
                'date_fin_blocage' => null,
                'updated_at' => now()
            ];

            // Restaurer dans la base locale
            $localConnection->table('comptes')
                ->where('id', $compteId)
                ->update($compteData);

            // Supprimer de Neon après restauration réussie
            $neonConnection->table('comptes')
                ->where('id', $compteId)
                ->delete();

            Log::info("Compte {$compteId} restauré depuis Neon");
            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la restauration depuis Neon: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier la connectivité à Neon
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        try {
            DB::connection('neon')->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning('Connexion Neon indisponible: ' . $e->getMessage());
            return false;
        }
    }
}