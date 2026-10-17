<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpRun extends Model
{
    protected $fillable = ['follow_up_rule_id', 'subject_type', 'subject_id'];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FollowUpRule::class, 'follow_up_rule_id');
    }
}
