<?php

namespace App\Listeners;

use App\Events\SendClientNotification;
use App\Services\TwilioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClientNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected TwilioService $twilioService;

    /**
     * Créer une nouvelle instance du listener
     */
    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Gérer l'événement
     */
    public function handle(SendClientNotification $event): void
    {
        try {
            $client = $event->client;
            $compte = $event->compte;

            Log::info("Envoi des notifications pour le nouveau compte {$compte->numero}");

            // 1. Envoyer l'email avec les informations de connexion
            if ($event->temporaryPassword && $event->verificationCode) {
                $this->sendWelcomeEmail($client, $compte, $event->temporaryPassword, $event->verificationCode);
            }

            // 2. Envoyer le SMS de confirmation
            $this->sendConfirmationSms($client, $compte);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications pour le compte {$compte->numero}: " . $e->getMessage());
            throw $e; // Re-throw pour permettre la gestion d'erreur
        }
    }

    /**
     * Envoyer l'email de bienvenue
     */
    private function sendWelcomeEmail($client, $compte, string $temporaryPassword, string $verificationCode): void
    {
        try {
            $data = [
                'client' => $client,
                'compte' => $compte,
                'temporaryPassword' => $temporaryPassword,
                'verificationCode' => $verificationCode,
            ];

            Mail::send('emails.welcome-client', $data, function ($message) use ($client) {
                $message->to($client->email)
                        ->subject('Bienvenue - Informations de connexion');
            });

            Log::info("Email de bienvenue envoyé à {$client->email}");

        } catch (\Exception $e) {
            Log::warning("Erreur envoi email à {$client->email}: " . $e->getMessage());
            // Ne pas échouer complètement si l'email échoue
        }
    }

    /**
     * Envoyer le SMS de confirmation
     */
    private function sendConfirmationSms($client, $compte): void
    {
        try {
            $message = "Bienvenue {$client->nom_complet} ! Votre compte {$compte->numero} a été créé avec succès. Type: {$compte->type}.";

            $this->twilioService->sendSms($client->telephone, $message);

            Log::info("SMS de confirmation envoyé au {$client->telephone}");

        } catch (\Exception $e) {
            Log::warning("Erreur envoi SMS au {$client->telephone}: " . $e->getMessage());
            // Ne pas échouer complètement si le SMS échoue
        }
    }
}