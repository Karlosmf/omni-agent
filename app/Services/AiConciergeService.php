<?php

namespace App\Services;

use App\Ai\AgencyAi;
use App\Ai\Agents\BookingDraftAgent;
use App\Ai\Agents\ConciergeAgent;
use App\Ai\Agents\ItineraryAgent;
use App\Ai\Agents\LeadExtractionAgent;
use App\Ai\Agents\TextAgent;
use App\Enums\AiProvider;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

/**
 * Everything the app asks of the AI, on top of the Laravel AI SDK. The agents (app/Ai/Agents) hold the prompts
 * and output schemas; this service picks the agency's provider, retries, stores the results and tracks usage.
 */
class AiConciergeService
{
    /**
     * Extra attempts after a transient provider failure (rate limit, overload, dropped connection).
     */
    private const MAX_RETRIES = 1;

    public function __construct(private ?AgencyAi $agencyAi = null)
    {
        $this->agencyAi ??= new AgencyAi;
    }

    /**
     * Answer a customer message and learn from it, in a single AI call. Returns the reply to send.
     */
    public function processMessage(string $messageContent, Lead $lead, bool $recordUserMessage = true): string
    {
        try {
            $userMessage = $recordUserMessage
                ? $lead->messages()->create(['role' => 'user', 'content' => $messageContent])
                : $lead->messages()->where('role', 'user')->newestFirst()->first();

            if (! $this->agencyAi->isEnabled()) {
                return $this->fallbackBotLogic($messageContent, $lead);
            }

            if ($this->budgetExhausted()) {
                return $this->limitReached($lead);
            }

            $response = $this->prompt(new ConciergeAgent($lead, $userMessage?->id), $messageContent);
            $turn = $response->toArray();
            $reply = trim((string) ($turn['reply'] ?? ''));

            if ($reply === '') {
                throw new \RuntimeException('AI_EMPTY_REPLY');
            }

            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $reply,
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
                'ai_model' => $response->meta->model ?? null,
            ]);

            $this->learnFromTurn($lead, $turn);

            return $reply;
        } catch (Throwable $e) {
            Log::error('AiConciergeService Error: '.$this->safeMessage($e));

            if (in_array($e->getMessage(), ['AI_API_KEY_MISSING', 'GEMINI_API_KEY_MISSING'])) {
                return $this->fallbackBotLogic($messageContent, $lead);
            }

            return 'Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo en unos segundos? 🙏';
        }
    }

    /**
     * Extract structured data from a customer's text (form, API, imported message).
     *
     * @return array<string, mixed> Keys: destino, presupuesto, pasajeros, fecha, noches, ciudad_salida, nombre, telefono, email, resumen, requiere_atencion. Empty when the AI is off or fails.
     */
    public function extractLeadData(string $message): array
    {
        try {
            return $this->prompt(new LeadExtractionAgent, "<texto>\n{$message}\n</texto>")->toArray();
        } catch (Throwable $e) {
            Log::error('AiExtraction Error: '.$this->safeMessage($e));

            return [];
        }
    }

    /**
     * Read the whole conversation (not just the last message) and extract what a booking needs.
     *
     * @return array<string, mixed> Keys: destination, travel_date (Y-m-d), nights, adults, children_ages, budget_usd,
     *                              departure_city, holder_name, phone, email, notes. Empty when the AI is off or fails.
     */
    public function extractBookingDraft(Lead $lead): array
    {
        $transcript = $lead->messages()->newestFirst()->limit(60)->get()->reverse()
            ->map(fn ($m) => ($m->role === 'user' ? 'Cliente' : 'Asesor').': '.$m->content)
            ->implode("\n");

        if (trim($transcript) === '') {
            return [];
        }

        try {
            return $this->prompt(new BookingDraftAgent, "<texto>\n{$transcript}\n</texto>")->toArray();
        } catch (Throwable $e) {
            Log::error('AiBookingDraft Error: '.$this->safeMessage($e));

            return [];
        }
    }

    /**
     * Draft an itinerary from a short request.
     *
     * @return list<array{day: int|string, title: string, description: string}>
     */
    public function generateItinerary(string $prompt): array
    {
        try {
            $days = $this->prompt(new ItineraryAgent, "<solicitud>{$prompt}</solicitud>")['days'] ?? [];

            return is_array($days) ? array_values($days) : [];
        } catch (Throwable $e) {
            Log::error('Error generating itinerary with AI: '.$this->safeMessage($e));

            return [];
        }
    }

    /**
     * Run a one-off prompt with the agency's configured provider and key, optionally reading a PDF.
     * With $json the model is asked to answer with a JSON object only (trip imports).
     *
     * @throws Throwable When the AI is disabled, has no key, or every retry failed.
     */
    public function generate(string $prompt, ?string $pdfPath = null, bool $json = false): string
    {
        $provider = $this->agencyAi->provider();

        if ($pdfPath !== null && $provider !== AiProvider::None && ! $provider->supportsPdf()) {
            throw new \Exception("{$provider->getLabel()} no puede leer PDFs. Usá Gemini u OpenAI, o importá desde un enlace.");
        }

        if ($json) {
            $prompt .= "\n\nRespondé ÚNICAMENTE con un objeto JSON válido, sin bloques de código ni texto adicional.";
        }

        $attachments = $pdfPath !== null ? [Document::fromPath($pdfPath)] : [];

        return $this->prompt(new TextAgent, $prompt, $attachments)->text;
    }

    /**
     * Check that the configured provider, key and model answer. Returns the error text, or null when it works.
     */
    public function testConnection(): ?string
    {
        try {
            $this->prompt(new TextAgent, 'Respondé solo con la palabra OK.', retries: 0);

            return null;
        } catch (Throwable $e) {
            return $e->getMessage() === 'AI_DISABLED'
                ? 'La IA está desactivada: elegí un proveedor.'
                : (in_array($e->getMessage(), ['AI_API_KEY_MISSING', 'GEMINI_API_KEY_MISSING']) ? 'Falta cargar la API key.' : $this->agencyAi->provider()->getLabel().': '.$this->safeMessage($e));
        }
    }

    /**
     * Prompt an agent with the agency's provider, retrying once on rate limits, overloads and dropped connections.
     *
     * @param  list<mixed>  $attachments
     */
    public function prompt(Agent $agent, string $text, array $attachments = [], int $retries = self::MAX_RETRIES): AgentResponse
    {
        [$provider, $model] = $this->agencyAi->resolve();

        for ($attempt = 0; ; $attempt++) {
            try {
                return $agent->prompt($text, $attachments, provider: $provider, model: $model);
            } catch (Throwable $e) {
                $transient = $e instanceof FailoverableException || $e instanceof ConnectionException;

                if (! $transient || $attempt >= $retries) {
                    throw $e;
                }

                Log::warning('AI attempt '.($attempt + 1).' failed: '.$this->safeMessage($e));
                usleep(($attempt + 1) * 1_000_000);
            }
        }
    }

    /**
     * Apply what the AI read from the customer's message: lead data, temperature and the qualification flag.
     *
     * @param  array<string, mixed>  $turn
     */
    private function learnFromTurn(Lead $lead, array $turn): void
    {
        app(LeadEnrichmentService::class)->apply($lead, [
            ...array_intersect_key($turn, array_flip(['destino', 'presupuesto', 'pasajeros', 'fecha', 'noches', 'ciudad_salida', 'nombre', 'telefono', 'email', 'resumen'])),
            'requiere_atencion' => (bool) ($turn['pide_persona'] ?? false),
        ]);

        $lead->refresh();

        $aiData = $lead->ai_data ?? [];
        $aiData['intencion'] = $turn['intencion'] ?? ($aiData['intencion'] ?? null);

        if (! empty($turn['calificacion_completa'])) {
            $aiData['calificacion_completa'] = true;
        }

        $update = ['ai_data' => array_filter($aiData, fn ($value) => $value !== null)];

        $update['temperature'] = ! empty($turn['pide_persona'])
            ? LeadTemperature::Hot
            : $this->raised($lead->temperature, LeadTemperature::tryFrom((string) ($turn['temperatura'] ?? '')));

        $lead->update($update);
    }

    /**
     * Temperature only goes up with the conversation: a calm message never cools down a hot lead.
     */
    private function raised(?LeadTemperature $current, ?LeadTemperature $suggested): LeadTemperature
    {
        $order = [LeadTemperature::Cool->value => 0, LeadTemperature::Warm->value => 1, LeadTemperature::Hot->value => 2];
        $current ??= LeadTemperature::Cool;

        return $suggested && $order[$suggested->value] > $order[$current->value] ? $suggested : $current;
    }

    /**
     * Whether this month's budget ran out, either in AI replies or in dollars.
     */
    public function budgetExhausted(): bool
    {
        $settings = get_agency_settings();
        $messageLimit = (int) $settings?->ai_monthly_message_limit;
        $spendLimit = (float) $settings?->ai_monthly_spend_limit_usd;

        if ($messageLimit > 0 && Message::whereNotNull('input_tokens')->where('created_at', '>=', now()->startOfMonth())->count() >= $messageLimit) {
            return true;
        }

        return $spendLimit > 0 && $this->monthlySpendUsd() >= $spendLimit;
    }

    /**
     * Estimated from the tokens of the stored replies and the prices configured in the bot panel. Zero without prices.
     */
    public function monthlySpendUsd(): float
    {
        $settings = get_agency_settings();
        $tokens = Message::whereNotNull('input_tokens')->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('COALESCE(SUM(input_tokens), 0) as input, COALESCE(SUM(output_tokens), 0) as output')
            ->first();

        return ((int) $tokens->input * (float) $settings?->ai_input_price_per_million
            + (int) $tokens->output * (float) $settings?->ai_output_price_per_million) / 1_000_000;
    }

    /**
     * Over the monthly budget: stop answering, hand the conversation to a person and tell the admins once.
     */
    private function limitReached(Lead $lead): string
    {
        $reply = 'Gracias por tu mensaje. Un asesor te va a responder a la brevedad.';

        $lead->forceFill(['needs_human_attention' => true])->saveQuietly();
        $lead->messages()->create(['role' => 'assistant', 'content' => $reply]);

        $this->alertBudgetExhausted();

        return $reply;
    }

    /**
     * Tell the admins, once a month, that the AI stopped answering.
     */
    public function alertBudgetExhausted(): void
    {
        if (Cache::add('ai-limit-alert:'.now()->format('Y-m'), true, now()->endOfMonth())) {
            foreach (User::where('role', UserRole::Admin)->get() as $admin) {
                Notification::make()
                    ->title('Se alcanzó el límite mensual de la IA')
                    ->body('La IA dejó de responder hasta el mes próximo. Los clientes esperan a una persona. Podés subir el límite en Ajustes → IA.')
                    ->warning()
                    ->sendToDatabase($admin);
            }
        }
    }

    /**
     * Provider errors can echo the request, so only a short slice of the message is ever logged or shown.
     */
    private function safeMessage(Throwable $e): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', $e->getMessage()), 200);
    }

    /**
     * Keyword temperature, used only by the basic bot when the AI is switched off.
     */
    private function updateLeadTemperature(string $messageContent, Lead $lead): void
    {
        if (preg_match('/\b(humano|asesor(a)?|una persona)\b/iu', $messageContent)) {
            $lead->update(['temperature' => LeadTemperature::Hot, 'needs_human_attention' => true]);
        } elseif ($lead->temperature === LeadTemperature::Cool && preg_match('/\b(fecha|presupuesto|reserva)\b/iu', $messageContent)) {
            $lead->update(['temperature' => LeadTemperature::Warm]);
        }
    }

    /**
     * Fallback logic when AI is not configured.
     */
    private function fallbackBotLogic(string $userMsg, Lead $lead): string
    {
        $this->updateLeadTemperature($userMsg, $lead);

        $aiData = is_array($lead->ai_data) ? $lead->ai_data : [];

        $lastAssistantMsg = $lead->messages()->where('role', 'assistant')->newestFirst()->first();
        $lastText = $lastAssistantMsg ? strtolower($lastAssistantMsg->content) : '';

        if (str_contains($lastText, 'destino en mente')) {
            $aiData['destino'] = $userMsg;
            $reply = '¿Tenés alguna fecha o mes en mente para el viaje?';
        } elseif (str_contains($lastText, 'fecha')) {
            $aiData['fecha'] = $userMsg;
            $reply = '¿Cuántos pasajeros viajan en total? (Si hay niños, por favor indicame las edades)';
        } elseif (str_contains($lastText, 'pasajeros')) {
            $aiData['pasajeros'] = $userMsg;
            $reply = '¿Desde qué ciudad les gustaría salir?';
        } elseif (str_contains($lastText, 'ciudad')) {
            $aiData['origen'] = $userMsg;
            $reply = '¿Tenés un presupuesto estimado en mente? (en USD o ARS — es para orientar la propuesta, no te preocupes si no lo tenés exacto)';
        } elseif (str_contains($lastText, 'presupuesto estimado')) {
            $aiData['presupuesto'] = $userMsg;
            $namesString = advisor_names(get_agency_settings(), 'nuestros agentes');
            $aiData['calificacion_completa'] = true;

            $reply = "¡Perfecto! Ya tengo todo lo que necesito. {$namesString} se van a comunicar con vos a la brevedad para armarte la propuesta. 😉";
        } else {
            $reply = 'Gracias por la información. En breve nos comunicaremos con vos.';
        }

        $lead->update(['ai_data' => $aiData]);

        $lead->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return $reply;
    }
}
