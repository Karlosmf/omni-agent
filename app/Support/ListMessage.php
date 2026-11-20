<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A WhatsApp list message: a text with a button that opens up to ten options. Meta's limits are applied here so a
 * long package name or a long body never gets the whole message rejected.
 */
class ListMessage
{
    public const MAX_ROWS = 10;

    public const MAX_ROW_TITLE = 24;

    public const MAX_ROW_DESCRIPTION = 72;

    public const MAX_BUTTON = 20;

    public const MAX_HEADER = 60;

    public const MAX_BODY = 1024;

    public const MAX_ROW_ID = 200;

    /**
     * The `interactive` object of Meta's Cloud API, which Zernio also takes as is.
     *
     * @param  list<array{id: string, title: string, description?: string}>  $rows
     * @return array<string, mixed>
     */
    public static function interactive(string $body, string $button, array $rows, ?string $header = null, ?string $sectionTitle = null): array
    {
        $section = array_filter([
            'title' => filled($sectionTitle) ? self::cut($sectionTitle, self::MAX_ROW_TITLE) : null,
            'rows' => array_map(fn (array $row) => array_filter([
                'id' => Str::limit($row['id'], self::MAX_ROW_ID, ''),
                'title' => self::cut($row['title'], self::MAX_ROW_TITLE),
                'description' => filled($row['description'] ?? null) ? self::cut((string) $row['description'], self::MAX_ROW_DESCRIPTION) : null,
            ], fn ($value) => $value !== null), array_slice($rows, 0, self::MAX_ROWS)),
        ], fn ($value) => $value !== null);

        return array_filter([
            'type' => 'list',
            'header' => filled($header) ? ['type' => 'text', 'text' => self::cut($header, self::MAX_HEADER)] : null,
            'body' => ['text' => self::cut($body, self::MAX_BODY)],
            'action' => [
                'button' => self::cut($button, self::MAX_BUTTON),
                'sections' => [$section],
            ],
        ], fn ($value) => $value !== null);
    }

    /**
     * Trimmed and cut to a limit, with an ellipsis when something was left out.
     */
    public static function cut(string $text, int $limit): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return mb_strlen($text) <= $limit ? $text : rtrim(mb_substr($text, 0, $limit - 1)).'…';
    }
}
