<?php

namespace App\Services;

use App\Models\Lead;

class LeadEnrichmentService
{
    /**
     * Fields the AI extracts that are copied into the lead's ai_data.
     */
    private const AI_DATA_FIELDS = ['destino', 'presupuesto', 'pasajeros', 'fecha', 'noches', 'ciudad_salida'];

    /**
     * Merge an AI extraction into a lead (and its customer's name when it is still generic).
     *
     * @param  array<string, mixed>  $extraction
     */
    public function apply(Lead $lead, array $extraction): Lead
    {
        if ($extraction === []) {
            return $lead;
        }

        $newData = [];
        foreach (self::AI_DATA_FIELDS as $field) {
            if (isset($extraction[$field]) && $extraction[$field] !== '') {
                $newData[$field] = $extraction[$field];
            }
        }

        $update = [
            'ai_data' => array_merge($lead->ai_data ?? [], $newData),
            'ai_summary' => $extraction['resumen'] ?? $lead->ai_summary,
            'needs_human_attention' => (bool) ($extraction['requiere_atencion'] ?? false) || $lead->needs_human_attention,
        ];

        $name = trim((string) ($extraction['nombre'] ?? ''));
        if ($name !== '') {
            if ($this->isGenericName($lead->customer_name)) {
                $update['customer_name'] = $name;
            }

            if ($lead->customer && $this->isGenericName($lead->customer->name)) {
                $lead->customer->update(['name' => $name]);
            }
        }

        $lead->update($update);

        app(CustomerMergeService::class)->adoptContact($lead, $extraction['telefono'] ?? null, $extraction['email'] ?? null);

        return $lead;
    }

    private function isGenericName(?string $name): bool
    {
        return blank($name) || $name === 'Web Guest';
    }
}
