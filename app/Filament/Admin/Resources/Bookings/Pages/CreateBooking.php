<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
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
            $lead = \App\Models\Lead::where('customer_id', $customerId)
                ->doesntHave('booking')
                ->latest()
                ->first();
        }

        // 2. Or check if lead_id is passed directly
        if (request()->has('lead_id')) {
            $leadId = request('lead_id');
            $lead = \App\Models\Lead::find($leadId);
            if ($lead) {
                $data['lead_id'] = $lead->id;
                if ($lead->customer_id) {
                    $data['customer_id'] = $lead->customer_id;
                }
            }
        }

        // 3. Populate data if lead found
        if ($lead) {
            $data['lead_id'] = $lead->id; // Ensure lead_id is set

            // Try to parse AI data
            // AI data is cast to array in Lead model
            $aiData = $lead->ai_data ?? [];

            if (isset($aiData['destination'])) {
                $data['destination'] = $aiData['destination'];
            } elseif (isset($aiData['destino'])) {
                $data['destination'] = $aiData['destino'];
            }

            if (isset($aiData['passengers'])) {
                $data['passengers'] = (int) filter_var($aiData['passengers'], FILTER_SANITIZE_NUMBER_INT);
            } elseif (isset($aiData['pasajeros'])) {
                $data['passengers'] = (int) filter_var($aiData['pasajeros'], FILTER_SANITIZE_NUMBER_INT);
            }

            // Travel Date
            if (isset($aiData['travel_date'])) {
                $data['travel_date'] = $aiData['travel_date'];
            }

            // Notes from summary
            if ($lead->ai_summary) {
                $data['notes'] = 'Resumen IA: '.$lead->ai_summary."\n\nMensaje Original: ".$lead->raw_message;
            }

            // Holder Name from Customer
            if ($lead->customer) {
                $data['holder_name'] = $lead->customer->name;
            }
        } elseif (isset($data['customer_id'])) {
            // Fallback if no lead but customer exists
            $customer = \App\Models\User::find($data['customer_id']);
            if ($customer) {
                $data['holder_name'] = $customer->name;
            }
        }

        $this->form->fill($data);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Crear registro')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
