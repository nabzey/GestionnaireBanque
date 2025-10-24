<?php

namespace App\Exceptions;

use Exception;

class CompteException extends Exception
{
    public static function compteNotFound(string $numero = null): self
    {
        $message = $numero ? "Compte avec numéro {$numero} non trouvé" : "Compte non trouvé";
        return new self($message, 404);
    }

    public static function compteBloque(string $numero, string $motif = null): self
    {
        $message = "Le compte {$numero} est bloqué";
        if ($motif) {
            $message .= " : {$motif}";
        }
        return new self($message, 403);
    }

    public static function compteFerme(string $numero): self
    {
        return new self("Le compte {$numero} est fermé", 403);
    }

    public static function clientNotFound(string $telephone): self
    {
        return new self("Client avec téléphone {$telephone} non trouvé", 404);
    }

    public static function unauthorized(): self
    {
        return new self("Accès non autorisé à cette ressource", 401);
    }

    public static function validationError(array $errors): self
    {
        $exception = new self("Erreur de validation", 422);
        $exception->errors = $errors;
        return $exception;
    }

    public $errors = [];
}