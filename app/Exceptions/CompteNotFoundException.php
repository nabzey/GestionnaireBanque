<?php

namespace App\Exceptions;

use Exception;

class CompteNotFoundException extends Exception
{
    protected $errorCode = 'COMPTE_NOT_FOUND';
    protected $message = "Le compte avec l'ID spécifié n'existe pas";

    public function __construct(string $message = null, int $code = 404)
    {
        parent::__construct($message ?? $this->message, $code);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage()
            ]
        ], $this->getCode());
    }
}