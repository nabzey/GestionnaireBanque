<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Vérifier si c'est un numéro sénégalais valide
        if (!$this->isValidSenegalesePhone($value)) {
            $fail('Le numéro de téléphone doit être au format sénégalais valide (ex: +221771234567 ou 771234567).');
        }
    }

    /**
     * Vérifier si le numéro est un numéro sénégalais valide
     */
    private function isValidSenegalesePhone(string $phone): bool
    {
        // Supprimer tous les espaces et caractères non numériques sauf +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Formats acceptés :
        // +221771234567 (format international)
        // 771234567 (format local)
        // 221771234567 (avec indicatif sans +)

        // Vérifier format international avec +
        if (preg_match('/^\+221\d{9}$/', $phone)) {
            return $this->isValidSenegaleseOperator(substr($phone, 4));
        }

        // Vérifier format avec indicatif sans +
        if (preg_match('/^221\d{9}$/', $phone)) {
            return $this->isValidSenegaleseOperator(substr($phone, 3));
        }

        // Vérifier format local (9 chiffres)
        if (preg_match('/^\d{9}$/', $phone)) {
            return $this->isValidSenegaleseOperator($phone);
        }

        return false;
    }

    /**
     * Vérifier si le numéro correspond à un opérateur sénégalais valide
     */
    private function isValidSenegaleseOperator(string $number): bool
    {
        // Les numéros sénégalais commencent par :
        // 70, 75, 76, 77, 78 (Orange)
        // 33 (Expresso)
        // 95, 96, 97 (Free)

        $prefix = substr($number, 0, 2);

        $validPrefixes = ['70', '75', '76', '77', '78', '33', '95', '96', '97'];

        return in_array($prefix, $validPrefixes) && strlen($number) === 9;
    }
}