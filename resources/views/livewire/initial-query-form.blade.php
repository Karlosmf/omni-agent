<?php

use App\Models\Lead;
use App\Models\Customer;
use function Livewire\Volt\{state, rules};

state([
    'name' => '',
    'email' => '',
    'phone' => '',
    'sent' => false,
]);

rules([
    'name' => 'required|string|max:255',
    'email' => 'required|email|max:255',
    'phone' => 'required|string|max:20',
]);

$submit = function () {
    $this->validate();

    // Check if customer exists or create
    $customer = Customer::firstOrCreate(
        ['email' => $this->email],
        ['name' => $this->name, 'phone' => $this->phone]
    );

    // Create Lead
    Lead::create([
        'customer_id' => $customer->id,
        'customer_name' => $this->name,
        'customer_phone' => $this->phone,
        'source' => 'web_initial_form',
        'status' => \App\Enums\LeadStatus::New , // Assuming enum
        'temperature' => \App\Enums\LeadTemperature::Cool,
    ]);

    $this->sent = true;
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
            <div>
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input wire:model="name" type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input wire:model="email" type="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono (WhatsApp)</label>
                <input wire:model="phone" type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white rounded-md py-2 px-4 hover:bg-indigo-700 transition">
                <span wire:loading.remove>Iniciar Chat</span>
                <span wire:loading>Procesando...</span>
            </button>
        </form>
    @endif
</div>