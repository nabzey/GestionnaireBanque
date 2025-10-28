<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreExpiredBlockedAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Démarrage du job de restauration des comptes bloqués expirés');

            // Trouver tous les comptes archivés (soft deleted) qui étaient bloqués
            // et dont la date de fin de blocage est dépassée
            $expiredBlockedAccounts = Compte::onlyTrashed()
                ->where('statut', 'bloque')
                ->whereNotNull('date_fin_blocage')
                ->where('date_fin_blocage', '<=', now())
                ->get();

            $restoredCount = 0;

            foreach ($expiredBlockedAccounts as $compte) {
                DB::beginTransaction();

                try {
                    // Restaurer le compte
                    $compte->restore();

                    // Restaurer toutes les transactions associées
                    Transaction::where('compte_id', $compte->id)
                        ->onlyTrashed()
                        ->restore();

                    // Remettre le compte en statut actif
                    $compte->update([
                        'statut' => 'actif',
                        'date_debut_blocage' => null,
                        'date_fin_blocage' => null,
                        'motif_blocage' => null
                    ]);

                    DB::commit();

                    $restoredCount++;
                    Log::info("Compte {$compte->numero} restauré avec succès");

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Erreur lors de la restauration du compte {$compte->numero}: " . $e->getMessage());
                    throw $e;
                }
            }

            Log::info("Job de restauration terminé: {$restoredCount} comptes restaurés");

        } catch (\Exception $e) {
            Log::error('Erreur lors du job de restauration des comptes: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec du job de restauration des comptes bloqués: ' . $exception->getMessage());
    }
}
