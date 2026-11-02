<?php

namespace App\Livewire;

use App\Actions\Leads\CaptureLeadAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\VisitOrigin;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class PublicCotizador extends Component
{
    public $destination = '';

    public $trip_type = 'vuelo_hotel';

    public $travel_date_start = '';

    public $travel_date_end = '';

    public $adults = 2;

    public $children = 0;

    public $name = '';

    public $email = '';

    public $phone = '';

    public $step = 1;

    public $isSubmitted = false;

    /** Honeypot: real visitors never see or fill this field. */
    public string $website = '';

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'destination' => 'required|string|max:255',
                'trip_type' => 'required|string',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'travel_date_start' => 'required|date|after_or_equal:today',
                'travel_date_end' => 'required|date|after_or_equal:travel_date_start',
                'adults' => 'required|integer|min:1',
                'children' => 'required|integer|min:0',
            ]);
        }

        $this->step++;
    }

    public function previousStep()
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit()
    {
        if ($this->website !== '') {
            $this->isSubmitted = true;

            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
        ]);
        try {
            $lead = app(CaptureLeadAction::class)->execute([
                ...VisitOrigin::forLead(),
                'customer_name' => $this->name,
                'customer_email' => $this->email,
                'customer_phone' => $this->phone,
                'source' => 'cotizador_publico',
                'raw_message' => "Solicitud desde el cotizador web.\nDestino: {$this->destination}\nFechas: {$this->travel_date_start} al {$this->travel_date_end}\nPasajeros: {$this->adults} adultos, {$this->children} niños.",
                'ai_data' => [
                    'destino' => $this->destination,
                    'tipo_viaje' => $this->trip_type,
                    'fecha_salida' => $this->travel_date_start,
                    'fecha_regreso' => $this->travel_date_end,
                    'adultos' => $this->adults,
                    'ninos' => $this->children,
                    'pasajeros' => $this->adults + $this->children,
                ],
            ]);
            $lead->update(['needs_human_attention' => true]); // Requiere atención porque fue manual
        } catch (\Throwable $e) {
            Log::error('Cotizador error: '.$e->getMessage());
            $this->addError('submit', 'No pudimos enviar tu solicitud. Probá de nuevo en unos minutos.');

            return;
        }

        // Notify admin
        $admin = User::where('role', UserRole::Admin)->first();
        if ($admin) {
            Notification::make()
                ->title('Nueva cotización web')
                ->body("El cliente {$this->name} solicitó cotización a {$this->destination}.")
                ->success()
                ->sendToDatabase($admin);
        }

        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.public-cotizador')->layout('components.layouts.app');
    }
}
