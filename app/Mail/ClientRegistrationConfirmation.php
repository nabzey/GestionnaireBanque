<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $temporaryPassword;
    public $codeAuthentification;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $temporaryPassword, $codeAuthentification)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
        $this->codeAuthentification = $codeAuthentification;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation d\'inscription - Banque',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.client_registration_confirmation',
            with: [
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
                'codeAuthentification' => $this->codeAuthentification,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}