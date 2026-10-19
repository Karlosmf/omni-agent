<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    //
    protected $fillable = [
        'lead_id',
        'role',
        'content',
        'channel',
        'direction',
        'external_id',
        'status',
        'sent_by_user_id',
        'media_path',
        'input_tokens',
        'output_tokens',
        'ai_model',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
