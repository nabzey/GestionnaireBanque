<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NCIRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Format NCI sénégalais: 13 chiffres commençant par 1 ou 2
        $pattern = '/^[12][0-9]{12}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de CNI doit contenir exactement 13 chiffres et commencer par 1 ou 2.');
        }

        // Vérification de l'algorithme de Luhn (simplifié pour la CNI)
        if (!$this->isValidNCI($value)) {
            $fail('Le numéro de CNI n\'est pas valide.');
        }
    }

    /**
     * Vérification basique de la validité du NCI
     */
    private function isValidNCI(string $nci): bool
    {
        // Pour cet exemple, on vérifie juste la longueur et le premier chiffre
        // En production, implémenter l'algorithme complet de validation CNI
        return strlen($nci) === 13 && in_array($nci[0], ['1', '2']);
    }
}
