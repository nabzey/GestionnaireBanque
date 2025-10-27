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

class RestoreAccountFromNeon implements ShouldQueue
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
            // Vérifier si le compte existe dans Neon
            $neonCompte = DB::connection('neon')->table('comptes')->where('id', $this->compteId)->first();

            if (!$neonCompte) {
                Log::error('Compte non trouvé dans Neon pour restauration', ['compte_id' => $this->compteId]);
                return;
            }

            // Restaurer vers la base locale
            DB::transaction(function () use ($neonCompte) {
                // Restaurer le compte (mettre à jour le statut)
                Compte::withTrashed()->where('id', $this->compteId)->update([
                    'statut' => 'actif',
                    'motif_blocage' => null,
                    'deleted_at' => null, // Restaurer si soft deleted
                    'updated_at' => now(),
                ]);

                // Restaurer les transactions depuis Neon
                $neonTransactions = DB::connection('neon')->table('transactions')
                    ->where('compte_id', $this->compteId)
                    ->get();

                foreach ($neonTransactions as $transaction) {
                    Transaction::updateOrCreate(
                        ['id' => $transaction->id],
                        [
                            'compte_id' => $transaction->compte_id,
                            'montant' => $transaction->montant,
                            'type' => $transaction->type,
                            'description' => $transaction->description,
                            'date_transaction' => $transaction->date_transaction,
                            'metadata' => json_decode($transaction->metadata, true),
                            'created_at' => $transaction->created_at,
                            'updated_at' => $transaction->updated_at,
                        ]
                    );
                }

                // Supprimer de Neon après restauration
                DB::connection('neon')->table('comptes')->where('id', $this->compteId)->delete();
                DB::connection('neon')->table('transactions')->where('compte_id', $this->compteId)->delete();
            });

            Log::info('Restauration du compte depuis Neon réussie', [
                'compte_id' => $this->compteId,
                'transactions_restored' => DB::connection('neon')->table('transactions')
                    ->where('compte_id', $this->compteId)->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la restauration depuis Neon', [
                'compte_id' => $this->compteId,
                'error' => $e->getMessage()
            ]);

            $this->fail($e);
        }
    }
}
