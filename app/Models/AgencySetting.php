<?php

namespace App\Models;

use App\Enums\AiProvider;
use App\Enums\RateSource;
use Carbon\CarbonInterface;
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
        'ai_model',
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
        'default_rate_source',
        'file_number_prefix',
        'auto_assign_leads',
        'sla_first_response_minutes',
        'ai_monthly_message_limit',
        'unsplash_access_key',
        'pexels_api_key',
        'installments_last_due_days_before_trip',
        'payment_instructions',
        'reminder_before_text',
        'reminder_due_text',
        'reminder_overdue_text',
        'bot_enabled',
        'bot_instructions',
        'bot_handoff_rules',
        'bot_handoff_keywords',
        'bot_promotions',
        'bot_business_days',
        'bot_hours_from',
        'bot_hours_to',
        'bot_pause_hours',
        'bot_daily_reply_limit',
        'ai_base_url',
        'ai_monthly_spend_limit_usd',
        'ai_input_price_per_million',
        'ai_output_price_per_million',
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
            'auto_assign_leads' => 'boolean',
            'gemini_api_key' => 'encrypted',
            'ai_api_key' => 'encrypted',
            'unsplash_access_key' => 'encrypted',
            'pexels_api_key' => 'encrypted',
            'ai_provider' => AiProvider::class,
            'default_rate_source' => RateSource::class,
            'bot_enabled' => 'boolean',
            'bot_promotions' => 'array',
            'bot_business_days' => 'array',
            'bot_pause_hours' => 'integer',
            'bot_daily_reply_limit' => 'integer',
            'ai_monthly_spend_limit_usd' => 'float',
            'ai_input_price_per_million' => 'float',
            'ai_output_price_per_million' => 'float',
        ];
    }

    /**
     * The team's working hours, which decide when notices show. Without configured hours they always show.
     */
    public function isWithinBusinessHours(?CarbonInterface $moment = null): bool
    {
        $moment ??= now();
        $days = array_map('intval', $this->bot_business_days ?? []);

        if ($days !== [] && ! in_array($moment->dayOfWeekIso, $days, true)) {
            return false;
        }

        if (blank($this->bot_hours_from) || blank($this->bot_hours_to)) {
            return true;
        }

        $time = $moment->format('H:i');

        return $time >= substr($this->bot_hours_from, 0, 5) && $time < substr($this->bot_hours_to, 0, 5);
    }

    /**
     * Promotions in force today, for the bot to explain.
     *
     * @return list<array{title: string, text: string, channel: ?string, until: ?string}>
     */
    public function currentPromotions(): array
    {
        $today = now()->toDateString();

        return collect($this->bot_promotions ?? [])
            ->filter(fn (array $promotion) => filled($promotion['title'] ?? null)
                && (blank($promotion['starts_on'] ?? null) || $promotion['starts_on'] <= $today)
                && (blank($promotion['ends_on'] ?? null) || $promotion['ends_on'] >= $today))
            ->map(fn (array $promotion) => [
                'title' => (string) $promotion['title'],
                'text' => (string) ($promotion['text'] ?? ''),
                'channel' => $promotion['channel'] ?? null,
                'until' => $promotion['ends_on'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * The saved AI key is never sent to the browser: this is all the panel shows of it.
     */
    public function maskedAiApiKey(): ?string
    {
        return filled($this->ai_api_key) ? '••••••••'.substr((string) $this->ai_api_key, -4) : null;
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
