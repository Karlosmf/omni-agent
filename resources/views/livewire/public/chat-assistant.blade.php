<?php

use App\Services\AiConciergeService;
use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use function Livewire\Volt\{state, mount, boot};

state([
    'isOpen' => false,
    'messages' => [],
    'input' => '',
    'isLoading' => false,
    'leadId' => null,
    'embedded' => false,
]);

mount(function (bool $embedded = false) {
    $this->embedded = $embedded;
    if ($this->embedded) {
        $this->isOpen = true;
    }
    // Load from session or default
    $this->messages = session('chat_history', [
        ['role' => 'assistant', 'content' => '¡Hola! Soy tu asistente virtual de Luopan. 🌴✈️ ¿En qué puedo ayudarte hoy?']
    ]);
    $this->leadId = session('chat_lead_id');
});

$toggleChat = fn() => $this->isOpen = !$this->isOpen;

$sendMessage = function (AiConciergeService $aiService) {
    if (empty(trim($this->input)))
        return;

    // 1. Add User Message
    $userMsg = $this->input;
    $this->messages[] = ['role' => 'user', 'content' => $userMsg];
    $this->input = '';
    $this->isLoading = true;
    session(['chat_history' => $this->messages]);

    try {
        // 2. Manage Lead (Create or Retrieve)
        $lead = null;
        if ($this->leadId) {
            $lead = Lead::find($this->leadId);
        }

        if (!$lead) {
            // New Lead - "Web Guest"
            $lead = Lead::create([
                'customer_name' => 'Web Guest', // Default until identified
                'customer_phone' => 'Web-' . substr(session()->getId(), 0, 8),
                'source' => 'web_widget',
                'raw_message' => $userMsg,
                'status' => LeadStatus::New ,
                'temperature' => LeadTemperature::Cool,
                'needs_human_attention' => false,
                'ai_data' => ['history' => []],
            ]);
            $this->leadId = $lead->id;
            session(['chat_lead_id' => $lead->id]);
        }

        // 3. Process with AI
        // Pass history limited to last 10 messages for context
        $history = array_slice($this->messages, -10);
        $replyContent = $aiService->processMessage($userMsg, $history);

        // 4. Update Lead with latest interaction data if needed (optional for now)
        // $lead->update([...]);

    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Chatbot Error: " . $e->getMessage());
        $replyContent = "Lo siento, tuve una pequeña desconexión. ¿Me lo repites?";
    }

    // 5. Add AI Response
    $this->messages[] = ['role' => 'assistant', 'content' => $replyContent];
    session(['chat_history' => $this->messages]);
    $this->isLoading = false;
};

?>

<div :class="$wire.embedded ? 'w-full max-w-md relative z-20' : 'fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4 font-sans antialiased'"
    x-data="{ 
        init() {
            setTimeout(() => this.scrollToBottom(), 100);
        },
        scrollToBottom() { 
            const el = document.getElementById('chat-messages'); 
            if(el) el.scrollTop = el.scrollHeight; 
        } 
     }">

    <!-- Chat Window -->
    <div x-show="$wire.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-10 scale-95"
        class="w-full max-w-[360px] h-[600px] flex flex-col overflow-hidden shadow-2xl rounded-2xl border border-gray-100 bg-[#EFEAE2]"
        style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">

        <!-- Header (WhatsApp Green) -->
        <div class="bg-[#008069] text-white p-3 flex items-center justify-between shadow-sm shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                        <span class="text-xl">🤖</span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="font-bold text-base leading-tight">Luopan Viajes</h3>
                    <span class="text-xs text-green-100">En línea</span>
                </div>
            </div>

            <template x-if="!$wire.embedded">
                <button wire:click="$toggle('isOpen')" class="p-1 hover:bg-white/10 rounded-full transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </template>
        </div>

        <!-- Messages Area (Beige Pattern) -->
        <!-- Note: Adding a subtle pattern using CSS logic if needed, solid color for now -->
        <div id="chat-messages"
            class="flex-1 overflow-y-auto p-4 space-y-3 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat"
            x-effect="$wire.messages; setTimeout(() => scrollToBottom(), 50)">

            @foreach($messages as $msg)
                    <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] px-3 py-2 text-sm shadow-sm rounded-lg relative
                                        {{ $msg['role'] === 'user'
                ? 'bg-[#E7FFDB] text-gray-800 rounded-tr-none'
                : 'bg-white text-gray-800 rounded-tl-none' }}">

                            <div class="break-words">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>

                            <!-- Timestamp / Status (Fake for now) -->
                            <div class="flex justify-end items-center gap-1 mt-1 opacity-60">
                                <span class="text-[10px]">{{ now()->format('H:i') }}</span>
                                @if($msg['role'] === 'user')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                        class="w-3 h-3 text-blue-500">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </div>
                    </div>
            @endforeach

            <!-- Typing Indicator -->
            @if($isLoading)
                <div class="flex justify-start">
                    <div class="bg-white rounded-lg rounded-tl-none p-3 shadow-sm flex gap-1 items-center">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Input Area -->
        <div class="bg-[#F0F2F5] p-2 flex items-center gap-2 shrink-0">
            <form wire:submit="sendMessage" class="flex-1 flex items-center gap-2">
                <input type="text" wire:model="input" placeholder="Escribe un mensaje..."
                    class="flex-1 py-2 px-4 rounded-lg border-none focus:ring-1 focus:ring-green-500 text-sm bg-white placeholder:text-gray-400" />

                <button type="submit"
                    class="p-2 rounded-full bg-[#008069] text-white hover:bg-[#006C59] transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center w-10 h-10 shadow-sm"
                    wire:loading.attr="disabled">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                        class="w-5 h-5 ml-0.5">
                        <path
                            d="M3.478 2.404a.75.75 0 00-.926.941l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.404z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Toggle Button (WhatsApp Style) -->
    <template x-if="!$wire.embedded">
        <button wire:click="toggleChat"
            class="group h-14 w-14 rounded-full bg-[#25D366] text-white shadow-lg flex items-center justify-center hover:scale-110 hover:shadow-xl transition-all duration-300 relative z-50">

            <!-- Badge -->
            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span
                    class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[9px] font-bold items-center justify-center">1</span>
            </span>

            <!-- Icons -->
            <div class="relative w-8 h-8">
                <svg x-show="!$wire.isOpen"
                    class="absolute inset-0 w-full h-full transform transition-transform duration-300"
                    :class="$wire.isOpen ? 'scale-0 rotate-90' : 'scale-100 rotate-0'"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>

                <svg x-show="$wire.isOpen"
                    class="absolute inset-0 w-full h-full transform transition-transform duration-300"
                    :class="$wire.isOpen ? 'scale-100 rotate-0' : 'scale-0 -rotate-90'"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </div>
        </button>
    </template>
</div>