<?php

namespace App\Events;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendClientNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public Compte $compte;
    public ?string $temporaryPassword;
    public ?string $verificationCode;

    /**
     * Créer une nouvelle instance d'événement
     */
    public function __construct(
        Client $client,
        Compte $compte,
        ?string $temporaryPassword = null,
        ?string $verificationCode = null
    ) {
        $this->client = $client;
        $this->compte = $compte;
        $this->temporaryPassword = $temporaryPassword;
        $this->verificationCode = $verificationCode;
    }
}