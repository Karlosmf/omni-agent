<?php

use App\Services\AiConciergeService;
use function Livewire\Volt\{state, mount, boot};

state([
    'isOpen' => false,
    'messages' => [],
    'input' => '',
    'isLoading' => false,
    'leadId' => null, // To track if we already created a lead in this session
]);

mount(function () {
    $this->messages = session('chat_history', [
        ['role' => 'assistant', 'content' => '¡Hola! Soy Luopan, tu asistente personal. ¿En qué puedo ayudarte a planear hoy?']
    ]);
    $this->leadId = session('chat_lead_id');
});

$toggleChat = fn() => $this->isOpen = !$this->isOpen;

$sendMessage = function (AiConciergeService $aiService) {
    if (empty(trim($this->input)))
        return;

    // 1. User Message
    $userMsg = $this->input;
    $this->messages[] = ['role' => 'user', 'content' => $userMsg];
    $this->input = '';
    $this->isLoading = true;

    // Save to session immediately
    session(['chat_history' => $this->messages]);

    // 2. AI Processing
    // We simulate a delay for natural feel if needed, but synchronous is fine for MVP

    // Check if we have a Lead ID in session to update, or create new.
    // For this MVP, we will create/find based on session. 
    // Since we don't force login, we assume "Web Guest" initially.

    try {
        $lead = null;
        if ($this->leadId) {
            $lead = \App\Models\Lead::find($this->leadId);
        }

        if (!$lead) {
            // Create new Lead
            $lead = $aiService->createLead(
                'Web Guest',
                'WebSession-' . session()->getId(),
                $userMsg,
                'web_widget'
            );
            $this->leadId = $lead->id;
            session(['chat_lead_id' => $lead->id]);

            // The AI response is already generated in createLead, but we need to fetch it.
            // aiService->createLead calls processIncomingMessage internally.
            // We need to get the LAST reply. 
            // Actually, createLead returns the lead, but logic in service might need adjustment 
            // to return the specific reply or we assume the summary/ai_data helps?
            // Wait, createLead in Service calls `processIncomingMessage` which returns string, 
            // but `createLead` returns `Lead` object. We lost the reply string there.

            // Refinement: let's call processIncomingMessage directly if we want the reply string clearly.
            // But createLead handles the first one. 
            // Let's assume the reply is generic or we call processIncomingMessage again? No, double charge.
            // Let's modify the service logic slightly in our head: 
            // "createLead" does the job. We can generate a generic welcome or fetch the intent.
            // BETTER: Re-use processIncomingMessage. If Lead exists -> process. If not -> create then process.
        } else {
            $reply = $aiService->processIncomingMessage($lead, $userMsg);
        }

        // Fix for the createLead return value issue: 
        // We will just call processIncomingMessage. If we need to create lead first manually:
        if (!isset($reply)) {
            // It was a new lead, and createLead swallowed the reply. 
            // Let's generate a reply based on the context or just "Processing..." 
            // ACTUALLY, checking AiConciergeService again... createLead calls processIncomingMessage but discards return.
            // We should fetch the AI reply from the AI service properly.
            // For now, let's just call processInputMessage always, handling creation if needed.
            // But processIncomingMessage requires a Lead object.

            // Quick fix: generic response for first interaction if we can't retrieve it easily, 
            // OR simpler: Just use processIncomingMessage always, creating lead manually first.
        }

    } catch (\Exception $e) {
        $reply = "Lo siento, tuve un problema técnico. ¿Podrías intentar de nuevo?";
    }

    // Re-implementation of logic to be robust:
    if (!isset($reply)) {
        // This means we used createLead logic which swallows reply.
        // Let's create lead manually here to get the reply.
        if (!$this->leadId || !($lead = \App\Models\Lead::find($this->leadId))) {
            $lead = \App\Models\Lead::create([
                'customer_name' => 'Web Guest',
                'customer_phone' => 'Web-' . substr(session()->getId(), 0, 6),
                'source' => 'web_widget',
                'raw_message' => $userMsg,
                'status' => \App\Enums\LeadStatus::New ,
                'temperature' => \App\Enums\LeadTemperature::Cool,
                'needs_human_attention' => false,
                'ai_data' => [],
            ]);
            $this->leadId = $lead->id;
            session(['chat_lead_id' => $lead->id]);
        }

        $reply = $aiService->processIncomingMessage($lead, $userMsg);
    }

    $this->messages[] = ['role' => 'assistant', 'content' => $reply];
    session(['chat_history' => $this->messages]);
    $this->isLoading = false;
};

?>

<div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4 font-sans"
    x-data="{ scrollToBottom() { $nextTick(() => { const el = document.getElementById('chat-messages'); el.scrollTop = el.scrollHeight; }) } }">

    <!-- Chat Window -->
    <div x-show="$wire.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-10 scale-95"
        class="w-[350px] h-[500px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-100"
        style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div
            class="bg-gradient-to-r from-slate-900 to-indigo-900 text-white p-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div
                        class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center border border-white/20 overflow-hidden">
                        <!-- Luopan Avatar / Placeholder -->
                        <span class="text-lg">🤖</span>
                    </div>
                    <span
                        class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-slate-900 rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-bold text-sm">Luopan Concierge</h3>
                    <p class="text-[10px] text-indigo-200 uppercase tracking-wider font-semibold">En Línea</p>
                </div>
            </div>
            <button wire:click="$toggle('isOpen')" class="text-white/50 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50" x-init="scrollToBottom()"
            x-effect="$wire.messages; scrollToBottom()">
            @foreach($messages as $msg)
                    <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm shadow-sm 
                                {{ $msg['role'] === 'user'
                ? 'bg-indigo-600 text-white rounded-tr-sm'
                : 'bg-white text-slate-700 border border-slate-100 rounded-tl-sm' 
                                }}">
                            {!! nl2br(e($msg['content'])) !!}
                        </div>
                    </div>
            @endforeach

            @if($isLoading)
                <div class="flex justify-start">
                    <div
                        class="bg-white border border-slate-100 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm flex items-center gap-1">
                        <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
                        <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Input -->
        <div class="p-4 bg-white border-t border-slate-100 shrink-0">
            <form wire:submit="sendMessage" class="relative flex items-center">
                <input type="text" wire:model="input" placeholder="Escribe tu consulta..."
                    class="w-full pl-4 pr-12 py-3 bg-slate-100 border-none rounded-full text-slate-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white transition placeholder:text-slate-400" />
                <button type="submit"
                    class="absolute right-2 p-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-700 transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4">
                        <path
                            d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                    </svg>
                </button>
            </form>
            <div class="text-center mt-2">
                <span class="text-[10px] text-slate-400 tracking-tight">Powered by Gemini AI</span>
            </div>
        </div>
    </div>

    <!-- Floating Toggle Button -->
    <button wire:click="toggleChat"
        class="group h-14 w-14 rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-xl flex items-center justify-center hover:scale-110 transition-all duration-300 relative overflow-hidden"
        :class="{ 'rotate-90': $wire.isOpen }">
        <div class="absolute inset-0 bg-white/20 group-hover:opacity-0 transition"></div>

        <!-- Closed Icon -->
        <svg x-show="!$wire.isOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
            stroke="currentColor" class="size-7 animate-in fade-in zoom-in duration-300">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
        </svg>

        <!-- Open Icon -->
        <svg x-show="$wire.isOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
            stroke="currentColor" class="size-7 animate-in fade-in zoom-in duration-300" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>
</div>