<?php

namespace App\Models;

use App\Enums\BotNoticeType;
use Database\Factories\BotNoticeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notice from the receptionist bot to the team. It only lives inside the panel: nothing is sent out.
 */
class BotNotice extends Model
{
    /** @use HasFactory<BotNoticeFactory> */
    use HasFactory;

    protected $fillable = ['lead_id', 'type', 'detail', 'read_at'];

    protected function casts(): array
    {
        return [
            'type' => BotNoticeType::class,
            'read_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    /**
     * Raise a notice unless the same one is still waiting to be read.
     */
    public static function raise(Lead $lead, BotNoticeType $type, ?string $detail = null): self
    {
        return static::unread()->firstOrCreate(['lead_id' => $lead->id, 'type' => $type], ['detail' => $detail]);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * How many notices the team should see right now: none outside business hours.
     */
    public static function visibleCount(): int
    {
        return get_agency_settings()?->isWithinBusinessHours() === false ? 0 : static::unread()->count();
    }
}
