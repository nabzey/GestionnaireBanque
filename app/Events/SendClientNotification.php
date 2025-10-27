<?php

namespace App\Events;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendClientNotification
{
    use Dispatchable, SerializesModels;

    public Client $client;
    public Compte $compte;
    public string $temporaryPassword;
    public string $verificationCode;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, Compte $compte, string $temporaryPassword, string $verificationCode)
    {
        $this->client = $client;
        $this->compte = $compte;
        $this->temporaryPassword = $temporaryPassword;
        $this->verificationCode = $verificationCode;
    }
}
