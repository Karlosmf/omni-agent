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
                'company_name' => 'Luopan Viajes',
                'logo_path' => null,
                'favicon_path' => null,
                'primary_color' => '#1a56db',
                'secondary_color' => '#7e22ce',
                'accent_color' => '#f59e0b',
                'neutral_color' => '#3d4451',
                'base_100_color' => '#ffffff',
                'base_200_color' => '#f2f2f2',
                'info_color' => '#3abff8',
                'success_color' => '#36d399',
                'warning_color' => '#fbbd23',
                'error_color' => '#f87272',
                'base_content_color' => '#1f2937',
                'contact_email' => 'info@luopanviajes.com',
                'contact_phone' => '+34 900 123 456',
                'address' => 'Calle Principal 123, Madrid, España',
                'social_links' => [
                    ['platform' => 'Instagram', 'url' => 'https://instagram.com/luopanviajes', 'icon' => 'ph-instagram-logo'],
                    ['platform' => 'Facebook', 'url' => 'https://facebook.com/luopanviajes', 'icon' => 'ph-facebook-logo'],
                    ['platform' => 'WhatsApp', 'url' => 'https://wa.me/34900123456', 'icon' => 'ph-whatsapp-logo'],
                    ['platform' => 'Email', 'url' => 'mailto:info@luopanviajes.com', 'icon' => 'ph-envelope'],
                ],
            ]
        );
    }
}
