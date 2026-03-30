<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencySetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'contact_email',
        'contact_phone',
        'address',
        'social_links',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }
}
