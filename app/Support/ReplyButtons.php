<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Reply buttons under a WhatsApp message: what Meta allows (three, twenty characters each) and how the
 * same options read on a channel that has no buttons.
 */
class ReplyButtons
{
    public const MAX_BUTTONS = 3;

    public const MAX_TITLE_LENGTH = 20;

    /**
     * Keeps only what can be sent: trimmed, non-empty, distinct titles, cut to Meta's limits.
     *
     * @param  array<mixed>  $titles
     * @return list<string>
     */
    public static function normalize(array $titles): array
    {
        return collect($titles)
            ->filter(fn ($title) => is_string($title) || is_numeric($title))
            ->map(fn ($title) => Str::limit(trim((string) $title), self::MAX_TITLE_LENGTH, ''))
            ->filter()
            ->unique()
            ->take(self::MAX_BUTTONS)
            ->values()
            ->all();
    }

    /**
     * The id a tap sends back. The title is what the conversation needs, so the id only has to be unique within the message.
     */
    public static function payloadFor(string $title, int $position): string
    {
        return 'opt_'.($position + 1).'_'.(Str::slug($title) ?: 'x');
    }

    /**
     * The options written into the text, for channels without buttons.
     *
     * @param  list<string>  $buttons
     */
    public static function asText(string $text, array $buttons): string
    {
        if ($buttons === []) {
            return $text;
        }

        return rtrim($text)."\n\n".collect($buttons)->map(fn (string $title) => "• {$title}")->implode("\n");
    }
}
