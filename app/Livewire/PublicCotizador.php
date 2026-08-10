<?php

namespace App\Livewire;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
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
        $this->step--;
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
        ]);
        $customer = User::where('email', $this->email)->first();
        if (! $customer) {
            $customer = new User;
            $customer->forceFill([
                'email' => $this->email,
                'name' => $this->name,
                'phone' => $this->phone,
                'role' => UserRole::Customer,
                'password' => bcrypt(Str::random(16)),
            ])->save();
        }

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'source' => 'cotizador_publico',
            'status' => LeadStatus::New,
            'ai_data' => [
                'destino' => $this->destination,
                'tipo_viaje' => $this->trip_type,
                'fecha_salida' => $this->travel_date_start,
                'fecha_regreso' => $this->travel_date_end,
                'adultos' => $this->adults,
                'ninos' => $this->children,
                'pasajeros' => $this->adults + $this->children,
            ],
            'needs_human_attention' => true, // Requiere atención porque fue manual
            'raw_message' => "Solicitud desde el cotizador web.\nDestino: {$this->destination}\nFechas: {$this->travel_date_start} al {$this->travel_date_end}\nPasajeros: {$this->adults} adultos, {$this->children} niños.",
        ]);

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
