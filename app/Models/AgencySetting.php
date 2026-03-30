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
        'accent_color',
        'neutral_color',
        'base_100_color',
        'base_200_color',
        'info_color',
        'success_color',
        'warning_color',
        'error_color',
        'base_content_color',
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
