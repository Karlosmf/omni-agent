<?php

use App\Services\AiConciergeService;
use function Livewire\Volt\{state, layout};

layout('components.layouts.app');

state([
    'messages' => [
        ['role' => 'assistant', 'content' => '¡Hola! Soy el asistente de Luopan Viajes. ¿En qué puedo ayudarte hoy?'],
    ],
    'currentMessage' => '',
    'name' => '',
    'phone' => '',
    'step' => 'intro', // intro, chat
]);

$sendMessage = function (AiConciergeService $aiService) {
    if (empty($this->currentMessage)) return;

    // Add user message to list
    $this->messages[] = ['role' => 'user', 'content' => $this->currentMessage];
    
    $message = $this->currentMessage;
    $this->currentMessage = '';

    // If it's the first message, we might need name/phone (or we can assume they are provided in a real WhatsApp integration)
    // For this MVP demo, we'll just process it.
    
    $lead = $aiService->processIncomingMessage(
        $this->name ?: 'Usuario Web', 
        $this->phone ?: '00000000', 
        $message, 
        'web'
    );

    // AI Response (Mocked via service for now)
    $this->messages[] = [
        'role' => 'assistant', 
        'content' => '¡Gracias! He registrado tu interés por ' . ($lead->ai_data['destination'] ?? 'tu destino') . '. Un agente se contactará contigo pronto si es necesario.'
    ];
};

?>

<div class="flex flex-col h-screen max-w-lg mx-auto bg-white shadow-xl">
    <!-- Header -->
    <div class="flex items-center p-4 bg-[#075E54] text-white">
        <div class="w-10 h-10 bg-gray-300 rounded-full flex-shrink-0">
            <img src="https://ui-avatars.com/api/?name=Luopan+Viajes&color=7F9CF5&background=EBF4FF" class="rounded-full" />
        </div>
        <div class="ml-3">
            <h1 class="font-bold">Luopan Viajes</h1>
            <p class="text-xs text-green-200">En línea</p>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 p-4 overflow-y-auto bg-[#E5DDD5] space-y-4">
        @foreach($messages as $msg)
            <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[80%] p-3 rounded-lg shadow-sm {{ $msg['role'] === 'user' ? 'bg-[#DCF8C6] rounded-tr-none' : 'bg-white rounded-tl-none' }}">
                    <p class="text-sm">{{ $msg['content'] }}</p>
                    <p class="text-[10px] text-gray-500 text-right mt-1">{{ now()->format('H:i') }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-[#F0F0F0] flex items-center gap-2">
        @if($step === 'intro')
            <div class="flex flex-col w-full gap-2">
                <input type="text" wire:model="name" placeholder="Tu nombre" class="flex-1 p-2 rounded-lg border-none focus:ring-1 focus:ring-green-500 text-sm" />
                <input type="text" wire:model="phone" placeholder="Tu teléfono" class="flex-1 p-2 rounded-lg border-none focus:ring-1 focus:ring-green-500 text-sm" />
                <button wire:click="$set('step', 'chat')" class="bg-[#25D366] text-white p-2 rounded-lg font-bold text-sm">Empezar Chat</button>
            </div>
        @else
            <input type="text" 
                   wire:model="currentMessage" 
                   wire:keydown.enter="sendMessage"
                   placeholder="Escribe un mensaje..." 
                   class="flex-1 p-2 rounded-full border-none focus:ring-1 focus:ring-green-500 text-sm" />
            <button wire:click="sendMessage" class="p-2 bg-[#075E54] text-white rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
            </button>
        @endif
    </div>
</div>