<?php

namespace App\Services\Bot;

use App\Models\Lead;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The inquiry's trip data as JSON: null always means "not answered yet".
 *
 * @phpstan-type Trip array{
 *     destino: ?string,
 *     fechas: array{desde: ?string, hasta: ?string, flexible: ?bool},
 *     presupuesto_usd: int|float|null,
 *     pasajeros: array{adultos: ?int, menores: ?int, edades_menores: ?list<int>},
 *     documentacion: array{pasaporte: ?bool, visa: ?bool},
 *     todo_incluido: ?bool,
 *     ciudad_salida: ?string,
 *     es_exterior: ?bool
 * }
 */
class TripData
{
    /**
     * @return Trip
     */
    public static function empty(): array
    {
        return [
            'destino' => null,
            'fechas' => ['desde' => null, 'hasta' => null, 'flexible' => null],
            'presupuesto_usd' => null,
            'pasajeros' => ['adultos' => null, 'menores' => null, 'edades_menores' => null],
            'documentacion' => ['pasaporte' => null, 'visa' => null],
            'todo_incluido' => null,
            'ciudad_salida' => null,
            'es_exterior' => null,
        ];
    }

    /**
     * The inquiry's data; older leads start from what the previous assistant already learned.
     *
     * @return Trip
     */
    public static function of(Lead $lead): array
    {
        if ($lead->trip_data) {
            return array_replace_recursive(self::empty(), $lead->trip_data);
        }

        $trip = self::empty();
        $trip['destino'] = $lead->ai_data['destino'] ?? null;
        $trip['ciudad_salida'] = $lead->ai_data['ciudad_salida'] ?? null;

        return $trip;
    }

    /**
     * Apply what the AI read in this turn. Only answered fields change, so a null never erases a known answer.
     *
     * @param  Trip  $trip
     * @param  array<string, mixed>  $turn
     * @return Trip
     */
    public static function merge(array $trip, array $turn): array
    {
        $trip['destino'] = self::text($turn['destino'] ?? null) ?? $trip['destino'];
        $trip['fechas']['desde'] = self::date($turn['fecha_desde'] ?? null) ?? $trip['fechas']['desde'];
        $trip['fechas']['hasta'] = self::date($turn['fecha_hasta'] ?? null) ?? $trip['fechas']['hasta'];
        $trip['fechas']['flexible'] = $turn['fechas_flexibles'] ?? $trip['fechas']['flexible'];
        $trip['presupuesto_usd'] = is_numeric($turn['presupuesto_usd'] ?? null) && $turn['presupuesto_usd'] > 0 ? $turn['presupuesto_usd'] + 0 : $trip['presupuesto_usd'];
        $trip['pasajeros']['adultos'] = self::count($turn['adultos'] ?? null) ?? $trip['pasajeros']['adultos'];
        $trip['pasajeros']['menores'] = self::count($turn['menores'] ?? null) ?? $trip['pasajeros']['menores'];
        $trip['pasajeros']['edades_menores'] = is_array($turn['edades_menores'] ?? null) && $turn['edades_menores'] !== []
            ? array_values(array_map('intval', $turn['edades_menores']))
            : $trip['pasajeros']['edades_menores'];
        $trip['documentacion']['pasaporte'] = $turn['pasaporte'] ?? $trip['documentacion']['pasaporte'];
        $trip['documentacion']['visa'] = $turn['visa'] ?? $trip['documentacion']['visa'];
        $trip['todo_incluido'] = $turn['todo_incluido'] ?? $trip['todo_incluido'];
        $trip['ciudad_salida'] = self::text($turn['ciudad_salida'] ?? null) ?? $trip['ciudad_salida'];
        $trip['es_exterior'] = $turn['es_exterior'] ?? $trip['es_exterior'];

        return $trip;
    }

    /**
     * Keep answers already in the first array and fill its gaps with the second (used when joining two inquiries).
     *
     * @param  Trip  $trip
     * @param  Trip  $other
     * @return Trip
     */
    public static function fillGaps(array $trip, array $other): array
    {
        foreach ($other as $key => $value) {
            if (is_array($value) && is_array($trip[$key] ?? null) && ! array_is_list($value)) {
                $trip[$key] = self::fillGaps($trip[$key], $value);
            } elseif (($trip[$key] ?? null) === null) {
                $trip[$key] = $value;
            }
        }

        return $trip;
    }

    /**
     * Whether everything the team needs to quote has been answered. Passport and visa only apply to trips abroad.
     *
     * @param  Trip  $trip
     */
    public static function isComplete(array $trip): bool
    {
        $dates = $trip['fechas'];
        $passengers = $trip['pasajeros'];
        $minors = $passengers['menores'];

        $hasDates = ($dates['desde'] !== null && $dates['hasta'] !== null) || $dates['flexible'] === true;
        $hasMinors = $minors === 0 || ($minors > 0 && count($passengers['edades_menores'] ?? []) === $minors);
        $hasDocuments = $trip['es_exterior'] === false
            || ($trip['es_exterior'] === true && $trip['documentacion']['pasaporte'] !== null && $trip['documentacion']['visa'] !== null);

        return filled($trip['destino']) && $hasDates && $trip['presupuesto_usd'] !== null && $passengers['adultos'] !== null
            && $hasMinors && $hasDocuments && $trip['todo_incluido'] !== null && filled($trip['ciudad_salida']);
    }

    /**
     * The same data as short text, for the columns the inbox, the leads and the booking draft already read.
     *
     * @param  Trip  $trip
     * @return array<string, string>
     */
    public static function toLeadData(array $trip): array
    {
        $dates = array_filter([$trip['fechas']['desde'], $trip['fechas']['hasta']]);
        $passengers = $trip['pasajeros'];

        return array_filter([
            'destino' => $trip['destino'],
            'fecha' => $dates === [] ? null : implode(' a ', $dates).($trip['fechas']['flexible'] ? ' (flexible)' : ''),
            'presupuesto' => $trip['presupuesto_usd'] !== null ? 'USD '.$trip['presupuesto_usd'] : null,
            'pasajeros' => $passengers['adultos'] === null ? null : trim($passengers['adultos'].' adultos'
                .($passengers['menores'] ? ', '.$passengers['menores'].' menores' : '')
                .($passengers['edades_menores'] ? ' ('.implode(', ', $passengers['edades_menores']).' años)' : '')),
            'ciudad_salida' => $trip['ciudad_salida'],
        ]);
    }

    /**
     * For people reading the inquiry in the panel.
     *
     * @param  array<string, mixed>|null  $trip
     * @return array<string, string> Label => value, with what is missing marked as pending.
     */
    public static function summary(?array $trip): array
    {
        $trip = array_replace_recursive(self::empty(), $trip ?? []);
        $yesNo = fn (?bool $value): string => $value === null ? 'Sin responder' : ($value ? 'Sí' : 'No');
        $dates = $trip['fechas'];
        $passengers = $trip['pasajeros'];

        return [
            'Destino' => $trip['destino'] ?? 'Sin responder',
            'Fechas' => ($dates['desde'] || $dates['hasta'] ? ($dates['desde'] ?? '¿?').' a '.($dates['hasta'] ?? '¿?') : 'Sin responder').($dates['flexible'] ? ' (flexible)' : ''),
            'Presupuesto' => $trip['presupuesto_usd'] !== null ? 'USD '.$trip['presupuesto_usd'] : 'Sin responder',
            'Adultos' => (string) ($passengers['adultos'] ?? 'Sin responder'),
            'Menores' => (string) ($passengers['menores'] ?? 'Sin responder').($passengers['edades_menores'] ? ' ('.implode(', ', $passengers['edades_menores']).' años)' : ''),
            'Pasaporte' => $trip['es_exterior'] === false ? 'No aplica' : $yesNo($trip['documentacion']['pasaporte']),
            'Visa' => $trip['es_exterior'] === false ? 'No aplica' : $yesNo($trip['documentacion']['visa']),
            'Todo incluido' => $yesNo($trip['todo_incluido']),
            'Ciudad de salida' => $trip['ciudad_salida'] ?? 'Sin responder',
        ];
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function count(mixed $value): ?int
    {
        return is_numeric($value) && $value >= 0 ? (int) $value : null;
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }
}
