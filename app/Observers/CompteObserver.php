<?php

namespace App\Observers;

use App\Events\SendClientNotification;
use App\Models\Compte;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;

class CompteObserver
{
    protected TwilioService $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Gérer après création d'un compte
     */
    public function created(Compte $compte): void
    {
        try {
            Log::info("Nouveau compte créé: {$compte->numero}");

            // Générer un mot de passe temporaire et un code de vérification
            $temporaryPassword = Compte::generateCode();
            $verificationCode = Compte::generateCode();

            // Envoyer les notifications
            SendClientNotification::dispatch(
                $compte->client,
                $compte,
                $temporaryPassword,
                $verificationCode
            );

            Log::info("Notifications envoyées pour le compte {$compte->numero}");

        } catch (\Exception $e) {
            Log::error("Erreur lors de la création du compte {$compte->numero}: " . $e->getMessage());
        }
    }

    /**
     * Gérer après mise à jour d'un compte
     */
    public function updated(Compte $compte): void
    {
        try {
            // Vérifier si le statut a changé
            if ($compte->wasChanged('statut')) {
                $oldStatus = $compte->getOriginal('statut');
                $newStatus = $compte->statut;

                Log::info("Changement de statut du compte {$compte->numero}: {$oldStatus} -> {$newStatus}");

                // Notifications selon le changement de statut
                $this->handleStatusChange($compte, $oldStatus, $newStatus);
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du compte {$compte->numero}: " . $e->getMessage());
        }
    }

    /**
     * Gérer après suppression d'un compte
     */
    public function deleted(Compte $compte): void
    {
        try {
            Log::info("Compte supprimé (soft delete): {$compte->numero}");

            // Ici on pourrait envoyer une notification de suppression
            // Mais comme c'est soft delete, on garde le compte accessible

        } catch (\Exception $e) {
            Log::error("Erreur lors de la suppression du compte {$compte->numero}: " . $e->getMessage());
        }
    }

    /**
     * Gérer les changements de statut
     */
    private function handleStatusChange(Compte $compte, string $oldStatus, string $newStatus): void
    {
        $message = '';

        switch ($newStatus) {
            case 'bloque':
                $message = "Votre compte {$compte->numero} a été bloqué.";
                if ($compte->motif_blocage) {
                    $message .= " Motif: {$compte->motif_blocage}";
                }
                break;

            case 'actif':
                if ($oldStatus === 'bloque') {
                    $message = "Votre compte {$compte->numero} a été débloqué avec succès.";
                } elseif ($oldStatus === 'ferme') {
                    $message = "Votre compte {$compte->numero} a été réactivé.";
                }
                break;

            case 'ferme':
                $message = "Votre compte {$compte->numero} a été fermé.";
                break;
        }

        if (!empty($message)) {
            try {
                $this->twilioService->sendSms($compte->telephone, $message);
                Log::info("SMS envoyé pour changement de statut: {$compte->numero}");
            } catch (\Exception $e) {
                Log::warning("Erreur envoi SMS pour compte {$compte->numero}: " . $e->getMessage());
            }
        }
    }
}