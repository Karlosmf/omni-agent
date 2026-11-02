<?php

namespace Database\Seeders;

use App\Models\AgencySetting;
use App\Models\TravelPackage;
use App\Models\WhatsAppTemplate;
use App\Services\Images\ImageDownloader;
use App\Services\Images\ImageSearchService;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Brand data and trip ideas of Luopan Viajes & Turismo (Reconquista, Santa Fe), taken from its public
 * Instagram posts (@luopanviajes) and its website. Safe to run again: it updates by slug.
 */
class LuopanSeeder extends Seeder
{
    public function run(): void
    {
        $this->brand();
        $this->whatsAppTemplates();

        foreach ($this->packages() as $data) {
            $images = $data['images'];
            unset($data['images']);

            $package = TravelPackage::updateOrCreate(['slug' => $data['slug']], $data);
            $this->attachImages($package, $images);
        }
    }

    /**
     * Texts to submit to Meta (WhatsApp Manager > Message templates, category Utility, language es_AR) under these
     * same names. They are saved inactive: switch each one on once Meta approves it, otherwise sending it fails.
     */
    private function whatsAppTemplates(): void
    {
        $templates = [
            [
                'purpose' => 'quote_ready',
                'name' => 'luopan_presupuesto_listo',
                'body' => 'Hola {{1}}, ya tenemos lista tu propuesta para {{2}}. Te la mandamos por acá y, si querés, la repasamos juntos. Luopan Viajes & Turismo.',
                'variables_count' => 2,
            ],
            [
                'purpose' => 'follow_up',
                'name' => 'luopan_seguimiento_cotizacion',
                'body' => 'Hola {{1}}, te escribimos de Luopan Viajes & Turismo para saber si pudiste ver la propuesta de {{2}}. Si querés cambiar fechas u hoteles, respondé este mensaje y la ajustamos.',
                'variables_count' => 2,
            ],
            [
                'purpose' => 'installment_reminder',
                'name' => 'luopan_recordatorio_cuota',
                'body' => 'Hola {{1}}, te recordamos que la {{2}} de tu viaje, por USD {{3}}, vence el {{4}}. Cualquier duda, escribinos. Luopan Viajes & Turismo.',
                'variables_count' => 4,
            ],
        ];

        foreach ($templates as $template) {
            $record = WhatsAppTemplate::firstOrNew(['name' => $template['name']]);
            $record->fill($template + ['language' => 'es_AR']);
            $record->is_active ??= false;
            $record->save();
        }
    }

    private function brand(): void
    {
        AgencySetting::updateOrCreate(['id' => 1], [
            'company_name' => 'Luopan Viajes & Turismo',
            'contact_email' => 'belenzorzon@luopanviajes.tur.ar',
            'contact_phone' => '+54 9 3482 600801',
            'address' => 'Belgrano 843, local A, Reconquista, Santa Fe (CP 3560)',
            'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=Belgrano+843+Reconquista+Santa+Fe',
            'meta_description' => 'Luopan Viajes & Turismo, más que una agencia, tu compañera de viajes. Salidas grupales acompañadas, cruceros, Caribe, Brasil y viajes a medida desde Reconquista, con financiación.',
            'footer_text' => 'Luopan, más que una agencia, tu compañera de viajes. Belgrano 843, Reconquista · 3482-300052 / 3482-600801',
            'hero_cta_url' => 'https://wa.me/5493482600801',
            'file_number_prefix' => 'LUO',
            'ai_assistant_name' => 'Luo',
            'fe_primary_color' => '#1f8a4c',
            'be_primary_color' => '#1f8a4c',
            'social_links' => [
                ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/luopanviajes/', 'icon' => 'ph-instagram-logo'],
                ['platform' => 'WhatsApp', 'url' => 'https://wa.me/5493482600801', 'icon' => 'ph-whatsapp-logo'],
                ['platform' => 'Web', 'url' => 'https://www.luopanviajes.tur.ar', 'icon' => 'ph-globe'],
                ['platform' => 'Email', 'url' => 'mailto:belenzorzon@luopanviajes.tur.ar', 'icon' => 'ph-envelope'],
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function packages(): array
    {
        $contact = "\n\nConsultanos en Belgrano 843, Reconquista, o al 3482-600801 / 3482-300052.";

        return [
            [
                'slug' => 'gran-caribe-2027-punta-cana-y-crucero-por-las-antillas',
                'title' => 'Gran Caribe 2027: Punta Cana y crucero por las Antillas',
                'destination' => 'Punta Cana y Antillas',
                'nights' => 10,
                'tags' => ['Caribe', 'Crucero', 'Salida grupal', 'All inclusive'],
                'price_from' => 2990,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => 'Playa, all inclusive y crucero por el Caribe en una salida grupal acompañada desde Reconquista y zona.',
                'description' => "Un viaje que combina playa, all inclusive, crucero, paisajes soñados y la tranquilidad de viajar en grupo. 3 noches en Punta Cana y 7 noches a bordo del MSC Opera recorriendo Virgin Gorda, St. Maarten, St. Kitts & Nevis, Tortola e Isla Catalina.\n\nSalida: 12 de marzo de 2027, desde Asunción con LATAM. Reservá tu lugar con USD 500.".$contact,
                'included' => "Aéreo desde Asunción con LATAM (incluye equipaje de bodega)\n3 noches en Punta Cana, all inclusive\n7 noches a bordo del MSC Opera con bebidas incluidas\nTraslados en destino\nAsistencia al viajero\nSalida grupal acompañada desde Reconquista y zona",
                'seasons' => [['name' => 'Salida grupal 12 de marzo', 'from' => '2027-03-12', 'to' => '2027-03-12', 'price_from' => 2990]],
                'is_active' => true,
                'images' => ['Punta Cana beach', 'MSC cruise ship Caribbean', 'St. Maarten beach'],
            ],
            [
                'slug' => 'grand-voyage-msc-splendida-de-barcelona-a-buenos-aires',
                'title' => 'Grand Voyage MSC Splendida: de Barcelona a Buenos Aires',
                'destination' => 'Barcelona a Buenos Aires (crucero)',
                'nights' => 18,
                'tags' => ['Crucero', 'Transatlántico', 'Europa', 'Brasil'],
                'price_from' => 2619,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => 'Cruzá el Atlántico a bordo del MSC Splendida: 18 noches entre Europa, África y Sudamérica.',
                'description' => "Una travesía única para vivir la magia de cruzar el Atlántico a bordo del MSC Splendida. Itinerario: Barcelona, Gibraltar, Casablanca, Las Palmas de Gran Canaria, Maceió, Salvador de Bahía, Río de Janeiro, Montevideo y Buenos Aires.\n\nSalida: 14 de noviembre de 2026, con aéreo de Buenos Aires a Barcelona por Air Europa.".$contact,
                'included' => "Aéreo con Air Europa de Buenos Aires a Barcelona\n18 noches a bordo del MSC Splendida",
                'seasons' => [['name' => 'Salida 14 de noviembre', 'from' => '2026-11-14', 'to' => '2026-11-14', 'price_from' => 2619]],
                'is_active' => true,
                'images' => ['MSC Splendida cruise ship', 'Barcelona Sagrada Familia', 'Rio de Janeiro Sugarloaf'],
            ],
            [
                'slug' => 'colombia-bogota-pereira-y-medellin',
                'title' => 'Colombia: Bogotá, Pereira y Medellín',
                'destination' => 'Colombia',
                'nights' => 9,
                'tags' => ['Colombia', 'Salida grupal', 'Cultura', 'Café'],
                'price_from' => 2380,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => 'Salida grupal acompañada por paisajes, cultura, café y la energía colombiana.',
                'description' => "Bogotá, Pereira y Medellín en una salida grupal acompañada para descubrir paisajes increíbles, cultura, café y toda la energía de Colombia.\n\nSalida: 10 de mayo de 2027, con aéreo desde Asunción por LATAM.".$contact,
                'included' => "Aéreo desde Asunción con LATAM\n9 noches de alojamiento con desayuno\nTraslados\nAsistencia al viajero",
                'seasons' => [['name' => 'Salida grupal 10 de mayo', 'from' => '2027-05-10', 'to' => '2027-05-10', 'price_from' => 2380]],
                'is_active' => true,
                'images' => ['Medellin Colombia skyline', 'Bogota Monserrate', 'Salento Colombia coffee region'],
            ],
            [
                'slug' => 'cartagena-de-indias-hotel-capilla-del-mar',
                'title' => 'Cartagena de Indias: escapada al Caribe colombiano',
                'destination' => 'Cartagena, Colombia',
                'nights' => 7,
                'tags' => ['Caribe', 'Colombia', 'Playa'],
                'price_from' => 990,
                'price_basis' => 'base_triple',
                'currency' => 'USD',
                'summary' => '7 noches frente al mar en el Hotel Capilla del Mar 5★, con historia, playas e islas.',
                'description' => "Una escapada al Caribe colombiano que combina historia, playas soñadas y paisajes únicos: el centro histórico de Cartagena con sus callecitas coloridas, el Parque Nacional Tayrona, Isla Palma, las Islas del Rosario y Barú.\n\nTarifa desde USD 990 por pasajero en base triple.".$contact,
                'included' => "Aéreos desde Asunción con Copa Airlines (carry on + bolso personal)\nTraslados en destino\n7 noches en Hotel Capilla del Mar 5★, frente al mar\nDesayuno y servicio de playa",
                'is_active' => true,
                'images' => ['Cartagena de Indias walled city', 'Islas del Rosario Colombia', 'Tayrona National Park beach'],
            ],
            [
                'slug' => 'jamaica-riu-negril-all-inclusive',
                'title' => 'Jamaica: Riu Negril all inclusive',
                'destination' => 'Jamaica',
                'nights' => 7,
                'tags' => ['Caribe', 'Salida grupal', 'All inclusive'],
                'price_from' => 2350,
                'price_basis' => 'base_doble',
                'currency' => 'USD',
                'summary' => 'Salida grupal acompañada a Negril: playas de agua turquesa y todo el relax.',
                'description' => "Salida grupal acompañada para vivir una experiencia única en el Caribe: playas de agua turquesa, paisajes increíbles y 7 noches en el exclusivo Riu Negril, all inclusive. Tarifa desde USD 2.350 por persona en base doble.\n\nLa última salida publicada fue el 11 de octubre de 2026: confirmá las próximas fechas antes de publicar.".$contact,
                'included' => "Aéreo con LATAM desde Asunción\n7 noches en Riu Negril, all inclusive\nTraslados\nAsistencia al viajero",
                'is_active' => false,
                'images' => ['Negril Jamaica beach', 'Seven Mile Beach Negril'],
            ],
            [
                'slug' => 'miches-republica-dominicana-viva-miches',
                'title' => 'Miches: el paraíso escondido de República Dominicana',
                'destination' => 'Miches, República Dominicana',
                'nights' => 8,
                'tags' => ['Caribe', 'Salida grupal', 'All inclusive', 'República Dominicana'],
                'price_from' => 2095,
                'price_basis' => 'base_triple',
                'currency' => 'USD',
                'summary' => 'Playas vírgenes y un all inclusive de primer nivel en el Caribe que todavía no está explotado.',
                'description' => "Playas vírgenes, naturaleza intacta y un all inclusive de primer nivel: el Caribe que todavía no está explotado, ideal para vivir algo distinto. Salida grupal acompañada el 14 de noviembre, con todo organizado para que solo te relajes.\n\nTarifas por pasajero: base doble USD 2.180, base triple USD 2.095. Reservá con una mínima seña.".$contact,
                'included' => "Aéreo con LATAM desde Asunción (carry on + bolso personal)\nTraslados in/out\n8 noches en Viva Miches by Wyndham, all inclusive\nAsistencia al viajero",
                'seasons' => [['name' => 'Salida grupal 14 de noviembre (base triple)', 'from' => '2026-11-14', 'to' => '2026-11-14', 'price_from' => 2095]],
                'is_active' => true,
                'images' => ['Miches Dominican Republic beach', 'Playa Esmeralda Miches'],
            ],
            [
                'slug' => 'pre-venta-brasil-2027-bus-desde-reconquista',
                'title' => 'Pre-venta Brasil 2027: Camboriú, Canasvieiras, Bombinhas y Torres',
                'destination' => 'Brasil (Santa Catarina y Río Grande do Sul)',
                'nights' => 7,
                'tags' => ['Brasil', 'Bus', 'Verano', 'Cuotas'],
                'price_from' => 0,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => 'Vacaciones de verano en bus desde Reconquista, en cuotas, con salidas todos los jueves de enero a marzo.',
                'description' => "Empezá a planear tus vacaciones de verano con tiempo y pagalas en cuotas. Destinos disponibles: Camboriú, Canasvieiras, Bombinhas y Torres. Salidas todos los jueves de enero, febrero y marzo de 2027.\n\nConsultanos por disponibilidad, hoteles y formas de pago. El precio no estaba publicado: completalo antes de publicar la idea.".$contact,
                'included' => "Bus ida y vuelta desde Reconquista y zona\n7 noches de alojamiento en el destino elegido\nDesayuno o media pensión\nAsistencia al viajero\nCoordinación",
                'is_active' => false,
                'images' => ['Balneario Camboriu', 'Bombinhas beach', 'Torres Rio Grande do Sul'],
            ],
            [
                'slug' => 'punta-cana-playa-y-relax',
                'title' => 'Punta Cana: playa y relax',
                'destination' => 'Punta Cana, República Dominicana',
                'nights' => 0,
                'tags' => ['Caribe', 'Playa', 'República Dominicana'],
                'price_from' => 0,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => 'Arena blanca, mar turquesa y palmeras en uno de los destinos más soñados del Caribe.',
                'description' => "Playas de arena blanca, mar turquesa, palmeras y días de relax. Reservá tu lugar con una mínima seña y empezá a vivir la previa de unas vacaciones increíbles.\n\nNoches y precio no estaban publicados: completalos antes de publicar la idea.".$contact,
                'is_active' => false,
                'images' => ['Punta Cana Bavaro beach', 'Saona Island Dominican Republic'],
            ],
            [
                'slug' => 'rio-de-janeiro-y-buzios-salida-15-de-noviembre',
                'title' => 'Río de Janeiro y Búzios: salida 15 de noviembre',
                'destination' => 'Río de Janeiro y Búzios, Brasil',
                'nights' => 7,
                'tags' => ['Brasil', 'Playa', 'Cupos confirmados'],
                'price_from' => 813,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => '7 noches con aéreo desde Buenos Aires: hoteles en Búzios (desde USD 813) o en Río de Janeiro (desde USD 1.118).',
                'description' => "Cupos confirmados. Salida el domingo 15 de noviembre de 2026 (EZE / RIO) con 7 noches. Opción Búzios desde USD 813 por persona; opción Río de Janeiro desde USD 1.118 por persona.\n\nPrecios publicados en la web de la agencia (mayorista).".$contact,
                'included' => "Aéreo ida y vuelta\n7 noches de hotel\n2 traslados\nSeguro de viaje",
                'seasons' => [['name' => 'Salida 15 de noviembre (Búzios)', 'from' => '2026-11-15', 'to' => '2026-11-15', 'price_from' => 813]],
                'is_active' => true,
                'images' => ['Buzios Brazil', 'Rio de Janeiro Copacabana'],
            ],
            [
                'slug' => 'ecuador-fascinante-7-dias-6-noches',
                'title' => 'Ecuador Fascinante: 7 días / 6 noches',
                'destination' => 'Ecuador',
                'nights' => 6,
                'tags' => ['Ecuador', 'Circuito', 'Cultura'],
                'price_from' => 2443,
                'price_basis' => 'por_persona',
                'currency' => 'USD',
                'summary' => 'Circuito por 2 destinos de Ecuador en 7 días y 6 noches.',
                'description' => "Circuito de 7 días y 6 noches por 2 destinos de Ecuador, desde USD 2.443 por persona. También disponible Ecuador Express de 8 días y 7 noches (4 destinos), desde USD 2.280.\n\nPrecios publicados en la web de la agencia (mayorista). Confirmá fechas y servicios incluidos.".$contact,
                'is_active' => true,
                'images' => ['Quito Ecuador historic center', 'Cotopaxi volcano Ecuador'],
            ],
        ];
    }

    /**
     * Real, licensed photos of the destination (Wikimedia Commons, Unsplash or Pexels when keys exist), with credit.
     *
     * @param  list<string>  $queries
     */
    private function attachImages(TravelPackage $package, array $queries): void
    {
        if ($package->cover_image) {
            return;
        }

        try {
            $candidates = collect(app(ImageSearchService::class)->search($queries, 2))->take(4);
            $stored = [];

            foreach ($candidates as $candidate) {
                try {
                    $stored[] = app(ImageDownloader::class)->store($candidate);
                } catch (Throwable) {
                    // Skip photos that cannot be downloaded.
                }
            }

            if ($stored !== []) {
                $package->update([
                    'cover_image' => $stored[0]['path'],
                    'gallery' => collect($stored)->skip(1)->pluck('path')->values()->all(),
                    'image_credits' => collect($stored)->pluck('credit')->all(),
                ]);
            }
        } catch (Throwable $e) {
            $this->command?->warn("Sin fotos para «{$package->title}»: ".$e->getMessage());
        }
    }
}
