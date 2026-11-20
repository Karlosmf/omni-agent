<?php

namespace App\Models;

use App\Enums\ChatMenuAction;
use App\Enums\ChatMenuSource;
use App\Support\KeywordMatcher;
use Database\Factories\ChatMenuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A WhatsApp list message the bot sends when the customer writes one of its keywords (or another menu opens it).
 */
class ChatMenu extends Model
{
    /** @use HasFactory<ChatMenuFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'keywords',
        'source',
        'options',
        'header',
        'body',
        'button_text',
        'on_select',
        'reply_text',
        'next_menu_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'source' => ChatMenuSource::class,
            'on_select' => ChatMenuAction::class,
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function nextMenu(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_menu_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return list<string>
     */
    public function keywordList(): array
    {
        return KeywordMatcher::parse($this->keywords);
    }

    /**
     * The options typed by the team, without empty ones.
     *
     * @return list<array{title: string, description: ?string}>
     */
    public function typedOptions(): array
    {
        return collect($this->options ?? [])
            ->filter(fn ($option) => is_array($option) && filled($option['title'] ?? null))
            ->map(fn (array $option) => ['title' => trim((string) $option['title']), 'description' => filled($option['description'] ?? null) ? trim((string) $option['description']) : null])
            ->values()
            ->all();
    }
}
