<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'agent_id',
        'title',
        'description',
        'due_date',
        'due_time',
        'is_completed',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function related()
    {
        return $this->morphTo();
    }
}
