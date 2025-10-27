<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Formats acceptés: +221XXXXXXXXX, 221XXXXXXXXX, 77XXXXXXX, 78XXXXXXX, 76XXXXXXX, 70XXXXXXX
        $pattern = '/^(\+221|221)?[67][0-9]{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de téléphone doit être au format sénégalais valide (ex: +221771234567 ou 771234567).');
        }
    }
}
