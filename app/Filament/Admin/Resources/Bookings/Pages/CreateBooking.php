<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount();

        $data = [];
        $lead = null;

        // 1. Check if customer_id is passed (e.g. from RelationManager)
        if (request()->has('customer_id')) {
            $customerId = request('customer_id');
            $data['customer_id'] = $customerId;

            // Find the most recent New lead for this customer that doesn't have a booking
            $lead = Lead::where('customer_id', $customerId)
                ->doesntHave('booking')
                ->latest()
                ->first();
        }

        // 2. Or check if lead_id is passed directly
        if (request()->has('lead_id')) {
            $leadId = request('lead_id');
            $lead = Lead::find($leadId);
            if ($lead) {
                $data['lead_id'] = $lead->id;
                if ($lead->customer_id) {
                    $data['customer_id'] = $lead->customer_id;
                }
            }
        }

        // 3. Populate data if lead found
        if ($lead) {
            $data['lead_id'] = $lead->id;

            $aiData = $lead->ai_data ?? [];

            // Destination
            if (! empty($aiData['destino'])) {
                $data['destination'] = $aiData['destino'];
            } elseif (! empty($aiData['destination'])) {
                $data['destination'] = $aiData['destination'];
            }

            // Passengers (numeric parse)
            $rawPassengers = $aiData['pasajeros'] ?? $aiData['passengers'] ?? null;
            if ($rawPassengers !== null) {
                if (is_numeric(trim((string) $rawPassengers))) {
                    $data['passengers'] = (int) $rawPassengers;
                } else {
                    preg_match_all('/(\d+)\s*(adultos?|niñ[os|as]+|ninos?|menores?|bebes?|bebés?|pasajeros?|personas?|menor)/i', $rawPassengers, $matches);
                    if (! empty($matches[1])) {
                        $data['passengers'] = array_sum($matches[1]);
                    } else {
                        preg_match('/\d+/', $rawPassengers, $firstNum);
                        $data['passengers'] = ! empty($firstNum) ? (int) $firstNum[0] : 1;
                    }
                }
            }

            // Travel Date — from ai_data['fecha'] (text) or ai_data['travel_date'] (ISO)
            if (! empty($aiData['travel_date'])) {
                $data['travel_date'] = $aiData['travel_date'];
            }

            // Nights
            if (! empty($aiData['noches']) && is_numeric($aiData['noches'])) {
                $data['nights'] = (int) $aiData['noches'];
            }

            // Holder Name from Customer
            if ($lead->customer) {
                $data['holder_name'] = $lead->customer->name;
            }

            // Build internal_notes with all AI-captured lead info
            $internalParts = [];
            if (! empty($aiData['resumen'])) {
                $internalParts[] = "Resumen IA: {$aiData['resumen']}";
            }
            if (! empty($aiData['presupuesto'])) {
                $internalParts[] = "Presupuesto consultante: {$aiData['presupuesto']}";
            }
            if (! empty($aiData['ciudad_salida'])) {
                $internalParts[] = "Ciudad de salida: {$aiData['ciudad_salida']}";
            }
            if (! empty($aiData['fecha'])) {
                $internalParts[] = "Fechas solicitadas: {$aiData['fecha']}";
            }
            if ($lead->raw_message) {
                $internalParts[] = "Mensaje original: {$lead->raw_message}";
            }
            if (! empty($internalParts)) {
                $data['internal_notes'] = implode("\n", $internalParts);
            }

            // Notes — ai_summary para el campo visible al cliente
            if ($lead->ai_summary) {
                $data['notes'] = $lead->ai_summary;
            }

            // Flash original passengers string so BookingForm can display it as label hint
            if ($rawPassengers) {
                session()->flash('lead_original_passengers', $rawPassengers);
            }
        } elseif (isset($data['customer_id'])) {
            $customer = User::find($data['customer_id']);
            if ($customer) {
                $data['holder_name'] = $customer->name;
            }
        }

        $this->form->fill($data);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Crear registro')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
