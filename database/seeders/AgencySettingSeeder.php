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
        AgencySetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Omni-Agent',
                'logotipo_path' => null,
                'isotipo_path' => null,

                // Frontend Colors
                'fe_primary_color' => '#1a56db',
                'fe_secondary_color' => '#7e22ce',
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
                'be_primary_color' => '#f59e0b', // Amber por defecto de Omni-Agent
                'be_success_color' => '#22c55e',
                'be_warning_color' => '#f59e0b',
                'be_danger_color' => '#ef4444',
                'be_info_color' => '#3b82f6',
                'be_gray_color' => '#71717a',

                'contact_email' => 'info@omniagent.com',
                'contact_phone' => '+34 900 123 456',
                'address' => 'Calle Principal 123, Madrid, España',
                'social_links' => [
                    ['platform' => 'Instagram', 'url' => 'https://instagram.com/omniagent', 'icon' => 'ph-instagram-logo'],
                    ['platform' => 'Facebook', 'url' => 'https://facebook.com/omniagent', 'icon' => 'ph-facebook-logo'],
                    ['platform' => 'WhatsApp', 'url' => 'https://wa.me/34900123456', 'icon' => 'ph-whatsapp-logo'],
                    ['platform' => 'Email', 'url' => 'mailto:info@omniagent.com', 'icon' => 'ph-envelope'],
                ],
            ]
        );
    }
}
