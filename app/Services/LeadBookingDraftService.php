<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Carbon;

/**
 * Turns a lead's conversation into the data of a draft booking, for the agent to review.
 * The AI reads the whole chat; whatever it cannot give falls back to what the lead already collected.
 */
class LeadBookingDraftService
{
    public function __construct(private AiConciergeService $ai) {}

    /**
     * @return array{destination: ?string, travel_date: ?string, nights: ?int, adults: int, children: list<array{age: int}>, budget_usd: ?float, holder_name: ?string, phone: ?string, email: ?string, notes: ?string, departure_city: ?string, from_ai: bool}
     */
    public function draftFor(Lead $lead): array
    {
        $ai = $this->ai->extractBookingDraft($lead);
        $known = $lead->ai_data ?? [];
        [$adultsFallback, $childrenFallback] = $this->parsePassengers((string) ($known['pasajeros'] ?? ''));

        $adults = (int) ($ai['adults'] ?? 0) ?: $adultsFallback ?: 2;
        $ages = isset($ai['children_ages']) && is_array($ai['children_ages'])
            ? array_map('intval', array_values($ai['children_ages']))
            : array_fill(0, $childrenFallback, 0);

        return [
            'destination' => $this->text($ai['destination'] ?? null) ?? $this->text($known['destino'] ?? null),
            'travel_date' => $this->date($ai['travel_date'] ?? null),
            'nights' => $this->positiveInt($ai['nights'] ?? null) ?? $this->positiveInt($known['noches'] ?? null),
            'adults' => max(1, $adults),
            'children' => array_map(fn (int $age) => ['age' => max(0, $age)], $ages),
            'budget_usd' => isset($ai['budget_usd']) && is_numeric($ai['budget_usd']) && (float) $ai['budget_usd'] > 0 ? (float) $ai['budget_usd'] : null,
            'holder_name' => $this->text($ai['holder_name'] ?? null) ?? $this->text($lead->customer_name) ?? $this->text($lead->customer?->name),
            'phone' => $this->text($ai['phone'] ?? null),
            'email' => filter_var($ai['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
            'notes' => $this->text($ai['notes'] ?? null),
            'departure_city' => $this->text($ai['departure_city'] ?? null) ?? $this->text($known['ciudad_salida'] ?? null),
            'from_ai' => $ai !== [],
        ];
    }

    /**
     * "4 adultos, 3 niños" → [4, 3]. Used only when the AI could not read the chat.
     *
     * @return array{0: int, 1: int}
     */
    public function parsePassengers(string $text): array
    {
        preg_match('/(\d+)\s*(adult|pax|pasajer|person|persona)/iu', $text, $adults);
        preg_match('/(\d+)\s*(ni[ñn]|men|chic|beb)/iu', $text, $children);

        if (! $adults && ! $children && preg_match('/^\s*(\d+)\s*$/', $text, $only)) {
            return [(int) $only[1], 0];
        }

        return [(int) ($adults[1] ?? 0), (int) ($children[1] ?? 0)];
    }

    private function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->isFuture() ? $date->format('Y-m-d') : null;
    }
}
