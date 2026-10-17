<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks whether the hosting cron (php artisan schedule:run, every minute) is alive.
 */
class CronHeartbeat
{
    public const KEY = 'omni:cron-heartbeat';

    public const STALE_AFTER_MINUTES = 5;

    public static function beat(): void
    {
        Cache::forever(self::KEY, now()->toIso8601String());
    }

    public static function lastBeat(): ?Carbon
    {
        $value = Cache::get(self::KEY);

        return $value ? Carbon::parse($value) : null;
    }

    public static function isStale(): bool
    {
        $last = self::lastBeat();

        return $last === null || $last->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }
}
