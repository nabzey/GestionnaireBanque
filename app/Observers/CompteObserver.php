<?php

namespace App\Observers;

use App\Models\Compte;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;

class CompteObserver
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        try {
            // Charger la relation client si elle n'est pas chargée
            $compte->load('client');

            // Utiliser le téléphone du compte si disponible, sinon celui du client
            $telephone = $compte->telephone ?: ($compte->client->telephone ?? null);

            if ($telephone) {
                $clientName = $compte->client ? $compte->client->nom . ' ' . $compte->client->prenom : 'Client';
                $this->twilioService->notifyAccountCreation(
                    $telephone,
                    $compte->numero,
                    $compte->type,
                    $clientName
                );
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification de création de compte: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        try {
            // Charger la relation client si elle n'est pas chargée
            $compte->load('client');

            // Notification de blocage de compte
            if ($compte->wasChanged('statut') && $compte->statut === 'bloque') {
                if ($compte->client && $compte->client->telephone) {
                    $this->twilioService->notifyAccountBlocked(
                        $compte->client->telephone,
                        $compte->numero,
                        $compte->motif_blocage ?? 'Non spécifié',
                        $compte->client->nom . ' ' . $compte->client->prenom
                    );
                }
            }

            // Notification de déblocage de compte
            if ($compte->wasChanged('statut') && $compte->statut === 'actif' && $compte->getOriginal('statut') === 'bloque') {
                if ($compte->client && $compte->client->telephone) {
                    $this->twilioService->notifyAccountUnblocked(
                        $compte->client->telephone,
                        $compte->numero,
                        $compte->client->nom . ' ' . $compte->client->prenom
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification de mise à jour de compte: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        // Les comptes sont soft deleted, pas de notification nécessaire
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        // Pas de notification nécessaire pour la restauration
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        // Pas de notification nécessaire pour la suppression définitive
    }
}