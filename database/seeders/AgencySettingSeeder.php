<?php

namespace Database\Seeders;

use App\Models\AgencySetting;
use Illuminate\Database\Seeder;

class AgencySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // firstOrCreate: correr el seeder otra vez nunca pisa lo que la agencia cambió en el panel.
        AgencySetting::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => config('brand.name'),
                'logotipo_path' => null,
                'isotipo_path' => null,

                // IA: sin key por defecto → el chatbot usa el modo fallback (sin IA)
                // El administrador configura su propia key desde el panel → Configuración → Marca Blanca
                'ai_provider' => 'gemini',
                'ai_api_key' => null,
                'gemini_api_key' => null,

                // Frontend Colors
                'fe_primary_color' => config('brand.colors.frontend_primary'),
                'fe_secondary_color' => config('brand.colors.frontend_secondary'),
                'fe_accent_color' => '#f59e0b',
                'fe_neutral_color' => '#3d4451',
                'fe_base_100_color' => '#ffffff',
                'fe_base_200_color' => '#f2f2f2',
                'fe_info_color' => '#3abff8',
                'fe_success_color' => '#36d399',
                'fe_warning_color' => '#fbbd23',
                'fe_error_color' => '#f87272',
                'fe_base_content_color' => '#1f2937',

                // Backend Colors (Filament)
                'be_primary_color' => config('brand.colors.backend_primary'),
                'be_success_color' => '#22c55e',
                'be_warning_color' => '#f59e0b',
                'be_danger_color' => '#ef4444',
                'be_info_color' => '#3b82f6',
                'be_gray_color' => '#71717a',

                'contact_email' => config('brand.email'),
                'contact_phone' => config('brand.phone'),
                'address' => config('brand.address'),
                'social_links' => $this->socialLinks(),
            ]
        );
    }

    /**
     * Solo las redes que la instalación configuró.
     *
     * @return list<array{platform: string, url: string, icon: string}>
     */
    private function socialLinks(): array
    {
        $icons = ['instagram' => 'ph-instagram-logo', 'facebook' => 'ph-facebook-logo', 'whatsapp' => 'ph-whatsapp-logo'];

        return collect($icons)
            ->filter(fn (string $icon, string $network) => filled(config("brand.social.{$network}")))
            ->map(fn (string $icon, string $network) => ['platform' => ucfirst($network), 'url' => config("brand.social.{$network}"), 'icon' => $icon])
            ->values()
            ->all();
    }
}
