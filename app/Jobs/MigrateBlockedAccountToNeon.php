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

class MigrateBlockedAccountToNeon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $compteId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $compteId)
    {
        $this->compteId = $compteId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Récupérer le compte avec ses transactions
            $compte = Compte::with('transactions')->find($this->compteId);

            if (!$compte) {
                Log::error('Compte non trouvé pour migration vers Neon', ['compte_id' => $this->compteId]);
                return;
            }

            // Migrer vers Neon
            DB::connection('neon')->transaction(function () use ($compte) {
                // Insérer le compte dans Neon
                DB::connection('neon')->table('comptes')->insert([
                    'id' => $compte->id,
                    'numero' => $compte->numero,
                    'solde_initial' => $compte->solde_initial,
                    'devise' => $compte->devise,
                    'type' => $compte->type,
                    'statut' => $compte->statut,
                    'motif_blocage' => $compte->motif_blocage,
                    'metadata' => json_encode($compte->metadata),
                    'client_id' => $compte->client_id,
                    'created_at' => $compte->created_at,
                    'updated_at' => $compte->updated_at,
                    'deleted_at' => $compte->deleted_at,
                ]);

                // Migrer les transactions associées
                foreach ($compte->transactions as $transaction) {
                    DB::connection('neon')->table('transactions')->insert([
                        'id' => $transaction->id,
                        'compte_id' => $transaction->compte_id,
                        'montant' => $transaction->montant,
                        'type' => $transaction->type,
                        'description' => $transaction->description,
                        'date_transaction' => $transaction->date_transaction,
                        'metadata' => json_encode($transaction->metadata),
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);
                }
            });

            Log::info('Migration du compte vers Neon réussie', [
                'compte_id' => $this->compteId,
                'transactions_count' => $compte->transactions->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la migration vers Neon', [
                'compte_id' => $this->compteId,
                'error' => $e->getMessage()
            ]);

            // Relancer le job en cas d'échec
            $this->fail($e);
        }
    }
}
