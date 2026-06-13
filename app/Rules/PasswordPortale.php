<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordPortale implements ValidationRule
{
    /**
     * Elenco leggibile dei caratteri speciali ammessi (per messaggi e form).
     */
    public const SPECIALS_DISPLAY = '! @ # $ % ^ & * ( ) _ + - = [ ] { } . , : ; < > / ? | ~';

    private const SPECIALS_RAW = '!@#$%^&*()_+-=[]{}.,:;<>/?|~';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('La password non è valida.');

            return;
        }

        if (strlen($value) < 8) {
            $fail('La password deve contenere almeno 8 caratteri.');

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('La password deve contenere almeno una lettera maiuscola.');

            return;
        }

        if (! preg_match('/\d/', $value)) {
            $fail('La password deve contenere almeno un numero.');

            return;
        }

        if (! self::hasAllowedSpecial($value)) {
            $fail('La password deve contenere almeno un carattere speciale tra quelli ammessi ('.self::SPECIALS_DISPLAY.').');

            return;
        }
    }

    public static function hasAllowedSpecial(string $value): bool
    {
        $len = strlen(self::SPECIALS_RAW);
        for ($i = 0; $i < $len; $i++) {
            if (str_contains($value, substr(self::SPECIALS_RAW, $i, 1))) {
                return true;
            }
        }

        return false;
    }
}
