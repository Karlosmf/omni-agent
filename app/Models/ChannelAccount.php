<?php

namespace App\Models;

use App\Enums\Channel;
use Database\Factories\ChannelAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One connected WhatsApp number, Facebook page or Instagram account.
 */
class ChannelAccount extends Model
{
    /** @use HasFactory<ChannelAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'channel',
        'label',
        'external_id',
        'access_token',
        'app_secret',
        'verify_token',
        'is_active',
    ];

    protected $hidden = ['access_token', 'app_secret'];

    protected function casts(): array
    {
        return [
            'channel' => Channel::class,
            'access_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public static function findActive(Channel $channel, string $externalId): ?self
    {
        return static::where('channel', $channel->value)->where('external_id', $externalId)->where('is_active', true)->first();
    }
}
