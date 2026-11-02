<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Database\Factories\ZernioConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The Zernio conversation of a WhatsApp number (E.164).
 */
class ZernioConversation extends Model
{
    /** @use HasFactory<ZernioConversationFactory> */
    use HasFactory;

    protected $fillable = ['phone', 'conversation_id'];

    public static function remember(?string $phone, ?string $conversationId): void
    {
        $phone = PhoneNumber::toE164($phone ? '+'.ltrim($phone, '+') : null);

        if ($phone === null || blank($conversationId)) {
            return;
        }

        static::updateOrCreate(['phone' => $phone], ['conversation_id' => $conversationId]);
    }

    public static function idFor(string $phone): ?string
    {
        $phone = PhoneNumber::toE164('+'.ltrim($phone, '+'));

        return $phone === null ? null : static::where('phone', $phone)->value('conversation_id');
    }
}
