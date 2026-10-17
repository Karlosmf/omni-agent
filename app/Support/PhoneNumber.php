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

    /**
     * E.164 form of a phone number. Argentine numbers are unified to the mobile format
     * +549<area><number>, whatever way they were typed (with/without 0, 15, 9 or country code).
     * Anything else is returned as normalize() leaves it.
     */
    public static function toE164(?string $phone): ?string
    {
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            return null;
        }

        $hasPlus = str_starts_with($normalized, '+');
        $digits = ltrim($normalized, '+');

        if (str_starts_with($digits, '54')) {
            $national = substr($digits, 2);
        } elseif (! $hasPlus && str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
        } elseif (! $hasPlus && strlen($digits) === 10) {
            $national = $digits;
        } else {
            return $normalized;
        }

        if (strlen($national) === 11 && str_starts_with($national, '9')) {
            return '+54'.$national;
        }

        if (strlen($national) === 12) {
            foreach ([2, 3, 4] as $areaLength) {
                if (substr($national, $areaLength, 2) === '15') {
                    $national = substr($national, 0, $areaLength).substr($national, $areaLength + 2);
                    break;
                }
            }
        }

        return strlen($national) === 10 ? '+549'.$national : $normalized;
    }

    /**
     * Every way the same number may already be stored, to find a returning customer.
     *
     * @return list<string>
     */
    public static function lookupVariants(?string $phone): array
    {
        $normalized = self::normalize($phone);
        $e164 = self::toE164($phone);

        if ($normalized === null || $e164 === null) {
            return [];
        }

        $variants = [$normalized, $e164, ltrim($e164, '+')];

        if (str_starts_with($e164, '+549')) {
            $national = substr($e164, 4);
            array_push($variants, '+54'.$national, '54'.$national, $national, '0'.$national);
        }

        return array_values(array_unique($variants));
    }
}
