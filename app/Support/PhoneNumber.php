<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Placeholders that older code stored instead of a real phone number.
     */
    private const PLACEHOLDERS = ['sin telefono', 'sin teléfono', 'web-form', 'web form', 'n/a', 'na', '-'];

    /**
     * Normalise a phone number to digits with an optional leading "+".
     * Returns null for empty values, placeholders and anything that is not a plausible number.
     */
    public static function normalize(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '' || in_array(mb_strtolower($phone), self::PLACEHOLDERS, true)) {
            return null;
        }

        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        return ($hasPlus ? '+' : '').$digits;
    }
}
