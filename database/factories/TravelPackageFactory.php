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
        ]) . ' ' . fake()->numberBetween(2026, 2027);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(100, 999),
            'destination' => fake()->randomElement(['Caribe', 'Patagonia', 'Italia', 'Maldivas', 'Orlando', 'Kenia', 'Mediterráneo', 'Japón']),
            'nights' => fake()->numberBetween(3, 14),
            'tags' => fake()->randomElements(['playa', 'aventura', 'familiar', 'all-inclusive', 'cultural', 'crucero', 'luna-de-miel', 'exótico'], fake()->numberBetween(1, 3)),
            'price_from' => fake()->randomFloat(2, 800, 8000),
            'currency' => fake()->randomElement(['USD', 'ARS']),
            'cover_image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?q=80&w=800&auto=format&fit=crop',
            'gallery' => [
                'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1501504905252-473c47e087f8?q=80&w=800&auto=format&fit=crop',
            ],
            'summary' => fake()->sentence(15),
            'description' => fake()->paragraphs(3, true),
            'itinerary' => [
                ['day' => '1', 'title' => 'Llegada y traslado', 'description' => 'Recepción en el aeropuerto y traslado al hotel seleccionado. Resto del día libre para acomodarse.'],
                ['day' => '2', 'title' => 'City Tour Histórico', 'description' => 'Recorrido guiado por los principales puntos históricos de la ciudad con guía en español.'],
                ['day' => '3', 'title' => 'Excursión de día completo', 'description' => 'Salida temprano hacia los atractivos naturales de la región. Incluye almuerzo tradicional.'],
                ['day' => '4', 'title' => 'Día libre', 'description' => 'Día a disposición para compras, paseos opcionales o simplemente descansar en las instalaciones.'],
                ['day' => '5', 'title' => 'Regreso', 'description' => 'Check-out al mediodía y traslado al aeropuerto para su vuelo de regreso.'],
            ],
            'included' => "✅ Aéreos ida y vuelta\n✅ Alojamiento\n✅ Traslados\n✅ Asistencia al viajero",
            'excluded' => "❌ Excursiones opcionales\n❌ Comidas no mencionadas\n❌ Gastos personales",
            'is_active' => fake()->boolean(80),
        ];
    }
}
