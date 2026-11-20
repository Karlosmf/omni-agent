<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Finds words or phrases set up by the team (one per line or separated by commas) in what a customer wrote.
 * Accents and case do not matter, and a keyword only matches as a whole word: "persona" is not found in "personas".
 */
class KeywordMatcher
{
    /**
     * @return list<string>
     */
    public static function parse(?string $keywords): array
    {
        return collect(preg_split('/[\n,;]+/', (string) $keywords) ?: [])
            ->map(fn (string $keyword) => trim($keyword))
            ->filter(fn (string $keyword) => $keyword !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The longest keyword found in the text, as it was set up; null when none is there.
     *
     * @param  list<string>  $keywords
     */
    public static function longestMatch(string $text, array $keywords): ?string
    {
        $text = self::normalize($text);

        if ($text === '') {
            return null;
        }

        $found = null;
        $foundLength = 0;

        foreach ($keywords as $keyword) {
            $normalized = self::normalize($keyword);
            $length = mb_strlen($normalized);

            if ($normalized === '' || $length <= $foundLength) {
                continue;
            }

            if (preg_match('/(?<![\pL\pN])'.preg_quote($normalized, '/').'(?![\pL\pN])/u', $text) === 1) {
                $found = $keyword;
                $foundLength = $length;
            }
        }

        return $found;
    }

    /**
     * Lower case, without accents and with single spaces.
     */
    public static function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', Str::lower(Str::ascii($text))) ?? '');
    }
}
