<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelPackage>
 */
class TravelPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement([
            'Playas del Caribe',
            'Aventura en Patagonia',
            'Roma y Toscana',
            'Maldivas All Inclusive',
            'Disney Orlando Familiar',
            'Safari en Kenia',
            'Crucero por el Mediterráneo',
            'Tokio y Kioto Express',
        ]).' '.fake()->numberBetween(2026, 2027);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'destination' => fake()->randomElement(['Caribe', 'Patagonia', 'Italia', 'Maldivas', 'Orlando', 'Kenia', 'Mediterráneo', 'Japón']),
            'nights' => fake()->numberBetween(3, 14),
            'tags' => fake()->randomElements(['playa', 'aventura', 'familiar', 'all-inclusive', 'cultural', 'crucero', 'luna-de-miel', 'exótico'], fake()->numberBetween(1, 3)),
            'price_from' => fake()->randomFloat(2, 800, 8000),
            'currency' => fake()->randomElement(['USD', 'ARS']),
            'cover_image' => null,
            'gallery' => null,
            'summary' => fake()->sentence(15),
            'description' => fake()->paragraphs(3, true),
            'itinerary' => [
                ['day' => 'Día 1', 'title' => 'Llegada y traslado', 'description' => fake()->sentence(10)],
                ['day' => 'Día 2', 'title' => 'Excursión principal', 'description' => fake()->sentence(10)],
                ['day' => 'Día 3', 'title' => 'Día libre o actividades opcionales', 'description' => fake()->sentence(10)],
            ],
            'included' => "✅ Aéreos ida y vuelta\n✅ Alojamiento\n✅ Traslados\n✅ Asistencia al viajero",
            'excluded' => "❌ Excursiones opcionales\n❌ Comidas no mencionadas\n❌ Gastos personales",
            'is_active' => fake()->boolean(80),
        ];
    }
}
