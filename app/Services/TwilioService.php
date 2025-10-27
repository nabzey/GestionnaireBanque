<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->fromNumber = config('services.twilio.from');
    }

    /**
     * Envoyer un SMS
     */
    public function sendSMS(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message
            ]);

            Log::info("SMS envoyé avec succès à {$to}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du SMS à {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification de création de compte
     */
    public function notifyAccountCreation(string $phoneNumber, string $accountNumber, string $accountType, string $clientName): bool
    {
        $message = "Bonjour {$clientName}, votre compte {$accountType} numéro {$accountNumber} a été créé avec succès. Bienvenue chez Zeynab-Ba Banque !";

        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Notification de transaction
     */
    public function notifyTransaction(string $phoneNumber, string $transactionType, float $amount, string $accountNumber, string $clientName): bool
    {
        $message = "Bonjour {$clientName}, une {$transactionType} de {$amount} FCFA a été effectuée sur votre compte {$accountNumber}.";

        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Notification de blocage de compte
     */
    public function notifyAccountBlocked(string $phoneNumber, string $accountNumber, string $reason, string $clientName): bool
    {
        $message = "Bonjour {$clientName}, votre compte {$accountNumber} a été bloqué. Motif: {$reason}. Contactez-nous pour plus d'informations.";

        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Notification de déblocage de compte
     */
    public function notifyAccountUnblocked(string $phoneNumber, string $accountNumber, string $clientName): bool
    {
        $message = "Bonjour {$clientName}, votre compte {$accountNumber} a été débloqué. Vous pouvez maintenant effectuer des opérations.";

        return $this->sendSMS($phoneNumber, $message);
    }
}