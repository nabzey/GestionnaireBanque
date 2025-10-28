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

            Log::info('Toutes les notifications envoyées avec succès pour le client ' . $event->client->email);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications client: ' . $e->getMessage());
            // Ne pas re-throw pour éviter que le job échoue et bloque la création du compte
            // Le compte est créé même si les notifications échouent
        }
    }

    /**
     * Envoyer la notification par email
     */
    private function sendEmailNotification(SendClientNotification $event): void
    {
        try {
            // Vérifier si l'email est configuré
            if (empty(config('mail.mailers.smtp.host')) || config('mail.mailers.smtp.host') === 'mailpit') {
                Log::info('Email non configuré - simulation de l\'envoi à ' . $event->client->email);
                return; // Simuler l'envoi en développement
            }

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
            // Ne pas throw pour éviter l'échec du job
        }
    }

    /**
     * Envoyer la notification par SMS
     */
    private function sendSMSNotification(SendClientNotification $event): void
    {
        try {
            // Formater le numéro de téléphone pour Twilio (avec indicatif)
            $phoneNumber = $this->formatPhoneNumber($event->compte->telephone);

            $message = "Zeynab-Ba Banque: Bienvenue! Votre code de vérification est: {$event->verificationCode}. Utilisez-le pour votre première connexion.";

            $result = $this->twilioService->sendSMS($phoneNumber, $message);

            if ($result) {
                Log::info('SMS envoyé avec succès au numéro ' . $phoneNumber);
            } else {
                Log::warning('Échec de l\'envoi du SMS au numéro ' . $phoneNumber);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS au ' . $event->compte->telephone . ': ' . $e->getMessage());
            // Ne pas throw pour éviter l'échec du job
        }
    }

    /**
     * Formater le numéro de téléphone pour Twilio
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Si le numéro commence déjà par +, le retourner tel quel
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // Ajouter l'indicatif sénégalais si nécessaire
        if (str_starts_with($phone, '221')) {
            return '+' . $phone;
        }

        // Pour les numéros locaux, ajouter l'indicatif
        return '+221' . $phone;
    }

    /**
     * Handle a job failure.
     */
    public function failed(SendClientNotification $event, \Throwable $exception): void
    {
        Log::error('Échec de l\'envoi des notifications pour le client ' . $event->client->email . ': ' . $exception->getMessage());
    }
}
