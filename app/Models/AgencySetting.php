<?php

namespace App\Models;

use App\Enums\AiProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AgencySetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'gemini_api_key',
        'ai_provider',
        'ai_api_key',
        'ai_assistant_name',
        'logotipo_path',
        'isotipo_path',
        'fe_primary_color',
        'fe_secondary_color',
        'fe_accent_color',
        'fe_neutral_color',
        'fe_base_100_color',
        'fe_base_200_color',
        'fe_info_color',
        'fe_success_color',
        'fe_warning_color',
        'fe_error_color',
        'fe_base_content_color',
        'be_primary_color',
        'be_success_color',
        'be_warning_color',
        'be_danger_color',
        'be_info_color',
        'be_gray_color',
        'contact_email',
        'contact_phone',
        'address',
        'hero_cta_url',
        'google_maps_url',
        'meta_description',
        'footer_text',
        'header_scripts',
        'footer_scripts',
        'social_links',
        'is_maintenance_mode',
        'maintenance_bypass_key',
        'contract_template',
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
            'is_maintenance_mode' => 'boolean',
            'ai_provider' => AiProvider::class,
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('agency_settings');
        });

        static::deleted(function () {
            Cache::forget('agency_settings');
        });
    }
}
