<?php

namespace App\Models;

use App\Enums\FollowUpAction;
use App\Enums\FollowUpTrigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FollowUpRule extends Model
{
    protected $fillable = ['name', 'trigger', 'delay_hours', 'action', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => FollowUpTrigger::class,
            'action' => FollowUpAction::class,
            'is_active' => 'boolean',
            'delay_hours' => 'integer',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(FollowUpRun::class);
    }
}
