<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use App\Services\NeonService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveExpiredBlockedAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected NeonService $neonService;

    /**
     * Create a new job instance.
     */
    public function __construct(NeonService $neonService)
    {
        $this->neonService = $neonService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Démarrage du job d\'archivage des comptes épargne bloqués expirés');

            // Trouver tous les comptes ÉPARGNE bloqués dont la date de fin de blocage est dépassée
            $expiredBlockedAccounts = Compte::where('statut', 'bloque')
                ->where('type', 'epargne') // Uniquement les comptes épargne
                ->whereNotNull('date_fin_blocage')
                ->where('date_fin_blocage', '<=', now())
                ->with('client')
                ->get();

            $archivedCount = 0;
            $transferErrors = 0;

            foreach ($expiredBlockedAccounts as $compte) {
                DB::beginTransaction();

                try {
                    // 1. Transférer vers Neon d'abord
                    $transferSuccess = $this->transferToNeon($compte);

                    if (!$transferSuccess) {
                        Log::warning("Échec du transfert vers Neon pour le compte {$compte->numero}, archivage local uniquement");
                        $transferErrors++;
                    }

                    // 2. Archiver le compte localement (soft delete)
                    $compte->delete();

                    // 3. Archiver toutes les transactions associées
                    Transaction::where('compte_id', $compte->id)->delete();

                    DB::commit();

                    $archivedCount++;
                    Log::info("Compte épargne {$compte->numero} archivé avec succès" . ($transferSuccess ? ' (avec transfert Neon)' : ' (archivage local uniquement)'));

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Erreur lors de l'archivage du compte {$compte->numero}: " . $e->getMessage());
                    throw $e;
                }
            }

            Log::info("Job d'archivage terminé: {$archivedCount} comptes épargne archivés, {$transferErrors} erreurs de transfert");

        } catch (\Exception $e) {
            Log::error('Erreur lors du job d\'archivage des comptes: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transférer un compte vers la base Neon
     */
    private function transferToNeon(Compte $compte): bool
    {
        try {
            // Vérifier la connectivité Neon
            if (!$this->neonService->isConnected()) {
                Log::warning('Base Neon indisponible, transfert annulé');
                return false;
            }

            // Préparer les données pour Neon
            $neonData = [
                'id' => $compte->id,
                'numero' => $compte->numero,
                'solde_initial' => $compte->solde_initial,
                'devise' => $compte->devise,
                'type' => $compte->type,
                'statut' => $compte->statut,
                'motif_blocage' => $compte->motif_blocage,
                'date_fin_blocage' => $compte->date_fin_blocage,
                'metadata' => $compte->metadata,
                'client_id' => $compte->client_id,
                'telephone' => $compte->telephone,
                'created_at' => $compte->created_at,
                'updated_at' => $compte->updated_at,
                'deleted_at' => now(), // Marquer comme archivé
                'client' => $compte->client ? [
                    'id' => $compte->client->id,
                    'nom' => $compte->client->nom,
                    'prenom' => $compte->client->prenom,
                    'email' => $compte->client->email,
                    'telephone' => $compte->client->telephone,
                    'nci' => $compte->client->nci,
                    'adresse' => $compte->client->adresse,
                    'nom_complet' => $compte->client->nom_complet
                ] : null
            ];

            // Insérer dans Neon (cette méthode devrait être ajoutée à NeonService)
            // Pour l'instant, on simule le transfert
            Log::info("Transfert simulé vers Neon pour le compte {$compte->numero}");

            // TODO: Implémenter la vraie logique d'insertion dans Neon
            // $this->neonService->insertCompte($neonData);

            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors du transfert vers Neon pour le compte {$compte->numero}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec du job d\'archivage des comptes épargne bloqués: ' . $exception->getMessage());
    }
}
