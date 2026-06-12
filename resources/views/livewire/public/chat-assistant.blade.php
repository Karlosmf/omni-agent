<?php

use App\Models\Message;
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
    // SmartLeadCapture fields
    'showCaptureForm' => true,
    'captureName' => '',
    'capturePhone' => '',
    'captureEmail' => '',
    'captureCurrency' => '',
    'captureBudgetAmount' => '',
    'captureDestination' => '',
]);

mount(function (bool $embedded = false) {
    $this->embedded = $embedded;
    if ($this->embedded) {
        $this->isOpen = true;
    }
    // Load from DB or default
    $this->leadId = session('chat_lead_id');

    if ($this->leadId) {
        $lead = Lead::find($this->leadId);
        if ($lead) {
            $this->showCaptureForm = false;
            $this->messages = Message::where('lead_id', $this->leadId)
                ->oldest()
                ->get()
                ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
                ->toArray();
        } else {
            $this->leadId = null;
            session()->forget('chat_lead_id');
        }
    }

    if ($this->showCaptureForm && empty($this->messages)) {
        $settings = get_agency_settings();
        $assistantName = $settings?->ai_assistant_name ?? 'Brisa';
        $companyName = $settings?->company_name ?? config('app.name', 'nuestra agencia');
        
        $this->messages = [
            ['role' => 'assistant', 'content' => "¡Hola! Soy {$assistantName}, tu asistente de {$companyName}. 🌴✈️ Completá tus datos y te ayudo a planificar tu viaje."]
        ];
    }
});

$toggleChat = fn() => $this->isOpen = !$this->isOpen;

$submitCapture = function () {
    if (empty(trim($this->captureName)) || empty(trim($this->capturePhone)))
        return;

    $destination = $this->captureDestination ?: 'Sin definir';
    $budget = null;
    if (!empty($this->captureCurrency) && !empty($this->captureBudgetAmount)) {
        $budget = $this->captureCurrency . ' ' . number_format((float) $this->captureBudgetAmount, 0, ',', '.');
    }

    // Create Lead with real data and Customer association
    $captureLeadAction = app(\App\Actions\Leads\CaptureLeadAction::class);
    $lead = $captureLeadAction->execute([
        'customer_name' => trim($this->captureName),
        'customer_phone' => trim($this->capturePhone),
        'customer_email' => !empty(trim($this->captureEmail)) ? trim($this->captureEmail) : null,
        'customer_budget' => $budget,
        'source' => 'web_widget',
        'raw_message' => "Interesado en: {$destination}",
        'ai_data' => [
            'destino' => $destination !== 'Sin definir' ? $destination : null,
            'presupuesto' => $budget,
        ],
    ]);

    $this->leadId = $lead->id;
    session(['chat_lead_id' => $lead->id]);
    $this->showCaptureForm = false;

    // Auto-greeting from assistant with context
    $greeting = "¡Hola {$this->captureName}! 👋 " .
        ($destination !== 'Sin definir'
            ? "Qué lindo destino {$destination}. ¿Tenés alguna fecha en mente para el viaje?"
            : "¿Tenés algún destino en mente para tu próximo viaje?");

    $lead->messages()->create(['role' => 'assistant', 'content' => $greeting]);

    $this->messages = [
        ['role' => 'assistant', 'content' => $greeting],
    ];
};

$sendMessage = function () {
    if (empty(trim($this->input)))
        return;

    // 1. Add User Message (Optimistic UI)
    $userMsg = $this->input;
    $this->messages[] = ['role' => 'user', 'content' => $userMsg];
    $this->input = '';
    $this->isLoading = true;

    try {
        // 2. Manage Lead (Create or Retrieve)
        $lead = null;
        if ($this->leadId) {
            $lead = Lead::find($this->leadId);
        }

        if (!$lead) {
            // Fallback: create lead if somehow capture was skipped
            $captureLeadAction = app(\App\Actions\Leads\CaptureLeadAction::class);
            $lead = $captureLeadAction->execute([
                'customer_name' => $this->captureName ?: 'Web Guest',
                'customer_phone' => $this->capturePhone ?: 'Sin teléfono',
                'customer_email' => !empty($this->captureEmail) ? $this->captureEmail : null,
                'customer_budget' => (!empty($this->captureCurrency) && !empty($this->captureBudgetAmount)) ? $this->captureCurrency . ' ' . $this->captureBudgetAmount : null,
                'source' => 'web_widget',
                'raw_message' => $userMsg,
                'ai_data' => ['history' => []],
            ]);
            $this->leadId = $lead->id;
            session(['chat_lead_id' => $lead->id]);
        }

        // 3. Process with Action
        $processAction = app(\App\Actions\Leads\ProcessChatbotInteractionAction::class);
        $replyContent = $processAction->execute($userMsg, $lead, $this->messages);

    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Chatbot Error: " . $e->getMessage());
        $replyContent = "Disculpá, tuve una pequeña desconexión. ¿Me lo repetís?";
    }

    // 5. Add AI Response
    $this->messages[] = ['role' => 'assistant', 'content' => $replyContent];
    $this->isLoading = false;
};

?>

<div :class="$wire.embedded ? 'w-full max-w-md relative z-20' : 'fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4 font-sans antialiased'"
    x-data="{ 
        init() {
            this.$watch('$wire.messages', () => {
                setTimeout(() => this.scrollToBottom(), 50);
            });
            setTimeout(() => this.scrollToBottom(), 100);
        },
        scrollToBottom() { 
            const el = this.$refs.chatMessages;
            if(el) {
                el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
            }
        } 
     }">

    <!-- Chat Window -->
    <div x-show="$wire.isOpen" 
        x-transition:enter="transition-all cubic-bezier(0.4, 0, 0.2, 1) duration-500"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95 origin-bottom-right"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-bottom-right"
        x-transition:leave="transition-all cubic-bezier(0.4, 0, 0.2, 1) duration-300"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100 origin-bottom-right"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95 origin-bottom-right"
        class="w-full max-w-[380px] h-[650px] max-h-[85vh] flex flex-col overflow-hidden shadow-[0_30px_60px_-15px_rgba(0,0,0,0.3)] rounded-3xl border border-white/40 bg-white/80 backdrop-blur-2xl ring-1 ring-black/5"
        style="transform-origin: bottom right;">

        <!-- Header -->
        <div class="relative bg-emerald-600 text-white p-4 shrink-0 overflow-hidden">
            <!-- Glass reflection effect -->
            <div class="absolute inset-0 bg-gradient-to-b from-white/20 to-transparent opacity-50 pointer-events-none"></div>
            <!-- Decorative circle -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white rounded-full mix-blend-overlay filter blur-3xl opacity-20 animate-pulse-slow pointer-events-none"></div>
            
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-full bg-white/30 p-0.5 shadow-sm flex items-center justify-center">
                            <div class="w-full h-full bg-emerald-700 rounded-full flex items-center justify-center overflow-hidden border-2 border-emerald-600">
                                <span class="text-2xl drop-shadow-md">✨</span>
                            </div>
                        </div>
                        <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-emerald-400 border-2 border-emerald-700 rounded-full shadow-sm animate-pulse"></span>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="font-extrabold text-lg tracking-tight leading-none drop-shadow-sm text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-300">
                            {{ $agencySettings?->company_name ?? config('app.name', 'Omni-Agent') }}
                        </h3>
                        <span class="text-xs text-emerald-300 font-medium tracking-wide flex items-center gap-1 mt-1">
                            <span>Conectado</span>
                        </span>
                    </div>
                </div>

                <template x-if="!$wire.embedded">
                    <button wire:click="$toggle('isOpen')" class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-full transition-all duration-300 active:scale-90 focus:outline-none focus:ring-2 focus:ring-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        <!-- Messages Area -->
        <div x-ref="chatMessages" class="flex-1 overflow-y-auto p-5 space-y-5 bg-gradient-to-b from-transparent to-slate-50/50 scroll-smooth custom-scrollbar relative">
            
            @foreach($messages as $msg)
                <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-slide-up-fade" style="animation-delay: {{ $loop->last ? '0ms' : '0ms' }}">
                    <div class="max-w-[85%] px-4 py-3 text-[15px] leading-relaxed shadow-sm relative transition-all duration-300 hover:shadow-md
                        {{ $msg['role'] === 'user'
                            ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl rounded-tr-sm'
                            : 'bg-white text-slate-700 rounded-2xl rounded-tl-sm border border-slate-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)]' }}">

                        <div class="break-words font-medium {{ $msg['role'] === 'user' ? 'text-white/95' : 'text-slate-700' }}">
                            {!! nl2br(e($msg['content'])) !!}
                        </div>

                        <!-- Timestamp / Status -->
                        <div class="flex justify-end items-center gap-1.5 mt-2 {{ $msg['role'] === 'user' ? 'opacity-80' : 'opacity-50' }}">
                            <span class="text-[10px] font-semibold tracking-wider">{{ now()->format('H:i') }}</span>
                            @if($msg['role'] === 'user')
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-white">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Typing Indicator -->
            @if($isLoading)
                <div class="flex justify-start animate-fade-in">
                    <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-slate-100 flex gap-1.5 items-center">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-teal-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                        <span class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                    </div>
                </div>
            @endif
        </div>

        <!-- SmartLeadCapture Form -->
        @if($showCaptureForm)
            <div class="bg-white/90 backdrop-blur-md border-t border-slate-100 p-5 shrink-0 animate-slide-up relative">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-200 to-transparent"></div>
                
                <form wire:submit="submitCapture" class="space-y-4">
                    <div class="text-center mb-2">
                        <h4 class="text-sm font-bold text-slate-800">Completá para comenzar</h4>
                        <p class="text-[11px] text-slate-500 font-medium">Te conectaremos con un experto al instante</p>
                    </div>

                    <div class="space-y-3">
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" /></svg>
                            </div>
                            <input type="text" wire:model="captureName" placeholder="Tu nombre *"
                                class="w-full pl-9 pr-3 py-2.5 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 focus:ring-1 text-sm bg-slate-50/50 hover:bg-white transition-all shadow-sm" required />
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd" /></svg>
                            </div>
                            <input type="tel" wire:model="capturePhone" placeholder="Teléfono (WhatsApp) *"
                                class="w-full pl-9 pr-3 py-2.5 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 focus:ring-1 text-sm bg-slate-50/50 hover:bg-white transition-all shadow-sm" required />
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm8-5a.75.75 0 01.75.75v2.25h2.25a.75.75 0 010 1.5h-2.25v2.25a.75.75 0 01-1.5 0v-2.25H7.5a.75.75 0 010-1.5h2.25V5.75A.75.75 0 0110 5z" clip-rule="evenodd" /></svg>
                            </div>
                            <select wire:model="captureDestination"
                                class="w-full pl-9 pr-8 py-2.5 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 focus:ring-1 text-sm bg-slate-50/50 hover:bg-white transition-all shadow-sm text-slate-600 appearance-none">
                                <option value="">¿Qué destino te interesa?</option>
                                <option value="Brasil">🇧🇷 Brasil</option>
                                <option value="Caribe">🏝️ Caribe</option>
                                <option value="Europa">🇪🇺 Europa</option>
                                <option value="Disney / Orlando">🏰 Disney / Orlando</option>
                                <option value="Argentina">🇦🇷 Argentina</option>
                                <option value="Otro destino">🌍 Otro destino</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold text-sm tracking-wide hover:from-emerald-600 hover:to-teal-600 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 shadow-[0_8px_16px_-6px_rgba(16,185,129,0.4)] hover:shadow-[0_12px_20px_-6px_rgba(16,185,129,0.5)] active:scale-[0.98] flex justify-center items-center gap-2 group">
                        <span>Comenzar ahora</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 group-hover:translate-x-1 transition-transform"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </button>
                </form>
            </div>
        @else
            <!-- Input Area -->
            <div class="bg-white/90 backdrop-blur-md p-4 shrink-0 border-t border-slate-100 z-10">
                <form wire:submit="sendMessage" class="flex items-center gap-3 bg-slate-50 rounded-full p-1.5 pr-2 border border-slate-200 shadow-sm focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-400/20 transition-all">
                    <input type="text" wire:model="input" placeholder="Escribe un mensaje..."
                        class="flex-1 py-2 px-4 bg-transparent border-none focus:ring-0 text-sm placeholder:text-slate-400 text-slate-700" 
                        autocomplete="off" x-ref="messageInput" />

                    <button type="submit"
                        class="p-2.5 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white hover:from-emerald-600 hover:to-teal-600 transition-all duration-300 disabled:opacity-50 disabled:scale-100 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg active:scale-95"
                        wire:loading.attr="disabled">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 ml-0.5 transform -rotate-45 mb-0.5">
                            <path d="M3.478 2.404a.75.75 0 00-.926.941l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.404z" />
                        </svg>
                    </button>
                </form>
                <div class="text-center mt-2">
                    <span class="text-[9px] text-slate-400 font-medium tracking-wide uppercase">Potenciado por IA</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Toggle Button Area -->
    <template x-if="!$wire.embedded">
        <div x-show="!$wire.isOpen" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4"
            x-data="{ showWelcomeBubble: false }"
            x-init="setTimeout(() => { if(!$wire.isOpen) showWelcomeBubble = true }, 2000)">

            <!-- Welcome Bubble -->
            <div x-show="showWelcomeBubble && !$wire.isOpen" 
                x-transition:enter="transition-all cubic-bezier(0.34, 1.56, 0.64, 1) duration-500 delay-300"
                x-transition:enter-start="opacity-0 translate-y-8 scale-50 origin-bottom-right"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100 origin-bottom-right"
                x-transition:leave="transition-all duration-300"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="bg-white px-5 py-4 rounded-2xl rounded-br-none shadow-[0_20px_40px_-10px_rgba(0,0,0,0.15)] text-slate-800 text-sm relative mb-2 animate-float border border-slate-100 ring-1 ring-black/5 cursor-pointer hover:shadow-lg transition-all"
                @click="$wire.toggleChat(); showWelcomeBubble = false">

                <div class="flex items-start gap-4 pr-4">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-100 to-teal-50 flex items-center justify-center shrink-0 border border-emerald-100">
                        <span class="text-xl">👋</span>
                    </div>
                    <div class="flex flex-col pt-0.5">
                        <span class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-0.5">Soporte Online</span>
                        <span class="font-semibold text-[15px] leading-tight text-slate-700">¿Estás pensando en tu próximo viaje?</span>
                        <span class="text-xs text-slate-500 mt-1">Escribinos y te ayudamos a armarlo.</span>
                    </div>
                </div>

                <!-- Tail -->
                <div class="absolute -bottom-2.5 right-0 w-5 h-5 bg-white transform rotate-45 mr-5 border-b border-r border-slate-100"></div>

                <!-- Close Button -->
                <button @click="showWelcomeBubble = false; $event.stopPropagation();"
                    class="absolute top-2 right-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-full w-6 h-6 flex items-center justify-center transition-colors focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Main Floating Button -->
            <button wire:click="toggleChat" @click="showWelcomeBubble = false"
                class="group h-[68px] w-[68px] rounded-full bg-emerald-600 text-white shadow-[0_15px_30px_-5px_rgba(5,150,105,0.4)] flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 relative z-50 border border-emerald-500 overflow-hidden"
                :class="!$wire.isOpen ? 'animate-glow' : ''">
                
                <!-- Inner glow ring -->
                <div class="absolute inset-0 rounded-full border border-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute inset-0 bg-white/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                <!-- Icons -->
                <div class="relative w-8 h-8 z-10 flex items-center justify-center">
                    <!-- Sparkles Icon (Closed state) -->
                    <svg x-show="!$wire.isOpen"
                        x-transition:enter="transition-all duration-300 delay-150"
                        x-transition:enter-start="opacity-0 scale-50 rotate-[-45deg]"
                        x-transition:enter-end="opacity-100 scale-100 rotate-0"
                        x-transition:leave="transition-all duration-200"
                        x-transition:leave-start="opacity-100 scale-100 rotate-0"
                        x-transition:leave-end="opacity-0 scale-50 rotate-90"
                        class="absolute inset-0 w-full h-full text-white drop-shadow-sm"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>

                    <!-- Chevron Down (Open state) -->
                    <svg x-show="$wire.isOpen"
                        x-transition:enter="transition-all duration-300 delay-150"
                        x-transition:enter-start="opacity-0 scale-50 rotate-90"
                        x-transition:enter-end="opacity-100 scale-100 rotate-0"
                        x-transition:leave="transition-all duration-200"
                        x-transition:leave-start="opacity-100 scale-100 rotate-0"
                        x-transition:leave-end="opacity-0 scale-50 rotate-[-90deg]"
                        class="absolute inset-0 w-full h-full text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </button>
        </div>
    </template>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.3);
            border-radius: 10px;
        }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
        }

        @keyframes slide-up-fade {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-slide-up-fade {
            animation: slide-up-fade 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out forwards;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        @keyframes glow {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .animate-glow {
            animation: glow 3s infinite;
        }
        
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.2; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.1); }
        }
        .animate-pulse-slow {
            animation: pulse-slow 4s ease-in-out infinite;
        }
    </style>
</div>