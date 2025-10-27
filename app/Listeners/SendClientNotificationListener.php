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
     * Create the event listener.
     */
    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Handle the event.
     */
    public function handle(SendClientNotification $event): void
    {
        try {
            // Envoyer l'email avec les identifiants
            $this->sendEmailNotification($event);

            // Envoyer le SMS avec le code de vérification
            $this->sendSMSNotification($event);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications client: ' . $e->getMessage());
            throw $e; // Re-throw pour que le job soit marqué comme échoué
        }
    }

    /**
     * Envoyer la notification par email
     */
    private function sendEmailNotification(SendClientNotification $event): void
    {
        try {
            Mail::raw(
                "Bienvenue chez Zeynab-Ba Banque !\n\n" .
                "Vos identifiants de connexion :\n" .
                "Email : {$event->client->email}\n" .
                "Mot de passe temporaire : {$event->temporaryPassword}\n\n" .
                "Informations de votre compte :\n" .
                "Numéro de compte : {$event->compte->numero}\n" .
                "Type : {$event->compte->type}\n" .
                "Solde initial : {$event->compte->solde_initial} {$event->compte->devise}\n\n" .
                "Veuillez changer votre mot de passe lors de votre première connexion.",
                function ($message) use ($event) {
                    $message->to($event->client->email)
                            ->subject('Bienvenue chez Zeynab-Ba Banque - Vos identifiants');
                }
            );

            Log::info('Email de bienvenue envoyé à ' . $event->client->email);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email à ' . $event->client->email . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoyer la notification par SMS
     */
    private function sendSMSNotification(SendClientNotification $event): void
    {
        try {
            $message = "Zeynab-Ba Banque: Bienvenue! Votre code de vérification est: {$event->verificationCode}. Utilisez-le pour votre première connexion.";

            $this->twilioService->sendSMS($event->compte->telephone, $message);

            Log::info('SMS envoyé au numéro ' . $event->compte->telephone);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS au ' . $event->compte->telephone . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(SendClientNotification $event, \Throwable $exception): void
    {
        Log::error('Échec de l\'envoi des notifications pour le client ' . $event->client->email . ': ' . $exception->getMessage());
    }
}
