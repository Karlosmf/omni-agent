<?php

use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, mount, form};

new class extends \Livewire\Volt\Component implements HasForms {
    use InteractsWithForms;

    public ?array $data = [];
    public bool $sent = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Teléfono (WhatsApp)')
                    ->required()
                    ->maxLength(20),
            ])
            ->statePath('data');
    }

    public function submit() 
    {
        $data = $this->form->getState();

        // Check if customer exists or create
        $customer = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'], 
                'phone' => $data['phone'],
                'role' => \App\Enums\UserRole::Customer,
                'password' => Hash::make(Str::random(12)),
            ]
        );

        // Create Lead
        Lead::create([
            'customer_id' => $customer->id,
            'customer_name' => $data['name'],
            'customer_phone' => $data['phone'],
            'source' => 'web_initial_form',
            'status' => \App\Enums\LeadStatus::New , // Assuming enum
            'temperature' => \App\Enums\LeadTemperature::Cool,
        ]);

        $this->sent = true;
    }
};

?>

<div class="p-6 bg-white rounded-lg shadow-lg max-w-md mx-auto">
    @if ($sent)
        <div class="text-center text-green-600">
            <h3 class="text-xl font-bold mb-2">¡Gracias!</h3>
            <p>Tus datos han sido recibidos. Pronto iniciaremos el chat personal.</p>
            <button wire:click="$set('sent', false)" class="mt-4 text-sm text-gray-500 underline">Volver</button>
        </div>
    @else
        <h2 class="text-lg font-bold mb-4 text-gray-800">Comenzar Consulta</h2>
        <form wire:submit="submit" class="space-y-4">
            {{ $this->form }}

            <button type="submit"
                class="w-full bg-indigo-600 text-white rounded-md py-2 px-4 hover:bg-indigo-700 transition">
                <span wire:loading.remove>Iniciar Chat</span>
                <span wire:loading>Procesando...</span>
            </button>
        </form>
    @endif
</div>