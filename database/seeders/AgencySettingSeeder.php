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
                'logo_path' => null, // Placeholder or existing path
                'favicon_path' => null,
                'primary_color' => '#1a56db',
                'secondary_color' => '#7e22ce',
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
