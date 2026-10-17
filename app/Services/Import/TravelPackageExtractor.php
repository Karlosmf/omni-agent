<?php

namespace App\Services\Import;

use App\Services\AiConciergeService;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

/**
 * Turns the raw text (or a PDF) of a trip offer into a structured travel package.
 */
class TravelPackageExtractor
{
    public function __construct(private AiConciergeService $ai) {}

    /**
     * @return array<string, mixed> A validated, normalized payload.
     *
     * @throws RuntimeException When the AI never returns usable JSON.
     */
    public function extract(?string $text, ?string $pdfPath = null): array
    {
        $prompt = $this->prompt($text);
        $lastError = 'respuesta vacía';

        foreach ([0, 1] as $attempt) {
            try {
                $raw = $this->ai->generate($attempt === 0 ? $prompt : $prompt."\n\nTu respuesta anterior no fue un JSON válido. Respondé SOLO con el objeto JSON, sin texto adicional.", $pdfPath, json: true);
                $payload = $this->parse($raw);

                return $this->normalize($payload);
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new RuntimeException('La IA no devolvió un resultado utilizable: '.$lastError);
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $raw): array
    {
        $clean = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw)));

        if (($start = strpos($clean, '{')) !== false && ($end = strrpos($clean, '}')) !== false) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $data = json_decode($clean, true);

        if (! is_array($data)) {
            throw new RuntimeException('JSON inválido');
        }

        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'nights' => ['nullable', 'integer', 'min:0', 'max:365'],
            'itinerary' => ['nullable', 'array'],
            'included' => ['nullable', 'array'],
            'excluded' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'image_search_queries' => ['nullable', 'array'],
            'confidence' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('JSON incompleto: '.$validator->errors()->first());
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        $lines = fn ($value) => array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) $value)));

        return [
            'title' => trim($data['title']),
            'destination' => trim($data['destination']),
            'country' => $data['country'] ?? null,
            'nights' => isset($data['nights']) ? (int) $data['nights'] : null,
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'itinerary' => collect($data['itinerary'] ?? [])
                ->filter(fn ($d) => is_array($d) && filled($d['title'] ?? $d['description'] ?? null))
                ->values()
                ->map(fn ($d, $i) => ['day' => (string) ($d['day'] ?? $i + 1), 'title' => (string) ($d['title'] ?? ''), 'description' => (string) ($d['description'] ?? '')])
                ->all(),
            'included' => $lines($data['included'] ?? []),
            'excluded' => $lines($data['excluded'] ?? []),
            'tags' => $lines($data['tags'] ?? []),
            'price_from' => is_numeric($data['price_from'] ?? null) ? (float) $data['price_from'] : null,
            'currency' => in_array($data['currency'] ?? null, ['USD', 'ARS'], true) ? $data['currency'] : null,
            'price_basis' => $data['price_basis'] ?? null,
            'seasons' => (array) ($data['seasons'] ?? []),
            'image_search_queries' => array_slice($lines($data['image_search_queries'] ?? []), 0, 4),
            'confidence' => (array) ($data['confidence'] ?? []),
        ];
    }

    private function prompt(?string $text): string
    {
        $agency = get_agency_settings()?->company_name ?? 'la agencia';

        return <<<PROMPT
Sos un asistente de {$agency}, una agencia de viajes argentina. Convertí la siguiente oferta de viaje en un paquete para publicar.

REGLAS ESTRICTAS:
- NO inventes datos. Todo lo que no figure en la fuente va en null (o lista vacía). Nunca estimes precios, noches ni fechas.
- Escribí los textos con la voz de la agencia: español rioplatense, tono cálido y claro, tratando al cliente de "vos".
- ELIMINÁ el nombre, logo, teléfono, email, web y redes de la empresa o mayorista de origen. No lo menciones.
- "price_basis" solo si la fuente lo aclara: por_persona, base_doble, por_persona_base_doble, base_triple, base_cuadruple o precio_fijo.
- "image_search_queries": 2 a 4 búsquedas de fotos de lugares reales y reconocibles del destino (ej: "Plaza de Mayo Buenos Aires").
- "confidence": para cada campo importante (price, nights, itinerary, included, destination) indicá "alta", "media" o "baja" según cuán claro estaba en la fuente.

Respondé ÚNICAMENTE con un objeto JSON con esta forma:
{"title":"","destination":"","country":"","nights":null,"summary":"(máx 400 caracteres)","description":"",
"itinerary":[{"day":1,"title":"","description":""}],"included":[],"excluded":[],
"price_from":null,"currency":"USD|ARS|null","price_basis":null,
"seasons":[{"name":"","from":"YYYY-MM-DD","to":"YYYY-MM-DD","price_from":0}],
"tags":[],"image_search_queries":[],"confidence":{"price":"","nights":"","itinerary":"","included":"","destination":""}}

FUENTE:
{$this->sourceBlock($text)}
PROMPT;
    }

    private function sourceBlock(?string $text): string
    {
        return $text !== null && $text !== '' ? "\"\"\"\n{$text}\n\"\"\"" : '(el contenido está en el PDF adjunto)';
    }
}
