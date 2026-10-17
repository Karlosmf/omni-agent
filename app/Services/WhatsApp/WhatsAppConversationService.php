<?php

namespace App\Services\WhatsApp;

use App\Actions\Leads\CaptureLeadAction;
use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Support\PhoneNumber;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class WhatsAppConversationService
{
    public const AI_REPLIES_PER_MINUTE = 10;

    public const CLOSING_MARKER = 'Ya tengo todo lo que necesito';

    public function __construct(
        private WhatsAppGateway $gateway,
        private CaptureLeadAction $capture,
        private ProcessChatbotInteractionAction $chatbot,
    ) {}

    /**
     * Store an incoming message, reply with the AI unless a person has taken over, and hand off when needed.
     *
     * @param  array<string, mixed>  $message  A "messages[]" item of the Cloud API webhook.
     */
    public function receive(array $message, ?string $profileName = null): ?Lead
    {
        $externalId = (string) ($message['id'] ?? '');

        if ($externalId === '' || Message::where('external_id', $externalId)->exists()) {
            return null;
        }

        $phone = PhoneNumber::toE164('+'.ltrim((string) ($message['from'] ?? ''), '+'));

        if ($phone === null) {
            return null;
        }

        $text = $this->textOf($message);
        $lead = $this->findOrCreateLead($phone, $profileName, $text);

        $lead->messages()->create([
            'role' => 'user',
            'content' => $text,
            'channel' => 'whatsapp',
            'direction' => 'in',
            'external_id' => $externalId,
            'status' => 'received',
        ]);

        $lead->forceFill([
            'whatsapp_last_inbound_at' => now(),
            'unread_count' => $lead->unread_count + 1,
        ])->saveQuietly();

        if (($message['type'] ?? 'text') === 'text' && ! $lead->ai_paused) {
            $this->replyWithAi($lead, $text);
        }

        return $lead;
    }

    /**
     * An agent answers from the inbox. Taking over pauses the AI for this conversation.
     *
     * @throws \RuntimeException When free-form text is sent outside the 24 h window.
     */
    public function sendAgentReply(Lead $lead, User $agent, string $text): Message
    {
        if (! $lead->isInsideWhatsAppWindow()) {
            throw new \RuntimeException('Pasaron más de 24 horas desde el último mensaje del cliente: solo se pueden enviar plantillas aprobadas.');
        }

        $message = $this->deliver($lead, fn (string $to) => $this->gateway->sendText($to, $text), $text, $agent);
        $this->markAttended($lead, $agent);

        return $message;
    }

    /**
     * Send an approved template (the only thing allowed outside the 24 h window).
     *
     * @param  list<string>  $variables
     */
    public function sendTemplate(Lead $lead, WhatsAppTemplate $template, array $variables, ?User $agent = null): Message
    {
        $message = $this->deliver($lead, fn (string $to) => $this->gateway->sendTemplate($to, $template, $variables), $template->render($variables), $agent);

        if ($agent) {
            $this->markAttended($lead, $agent);
        }

        return $message;
    }

    public function pauseAi(Lead $lead): void
    {
        $lead->update(['ai_paused' => true]);
    }

    public function resumeAi(Lead $lead): void
    {
        $lead->update(['ai_paused' => false]);
    }

    private function replyWithAi(Lead $lead, string $text): void
    {
        $key = 'wa-ai:'.$lead->id;

        if (RateLimiter::tooManyAttempts($key, self::AI_REPLIES_PER_MINUTE)) {
            Log::info("WhatsApp AI throttled for lead {$lead->id}");

            return;
        }

        RateLimiter::hit($key, 60);

        $history = $lead->messages()->latest()->take(10)->get()->reverse()
            ->map(fn (Message $m) => ['role' => $m->role === 'user' ? 'user' : 'assistant', 'content' => $m->content])->values()->all();

        $reply = $this->chatbot->execute($text, $lead, $history, recordUserMessage: false);

        // The assistant message was stored by the AI service: tag it and send it.
        $stored = $lead->messages()->where('role', 'assistant')->latest('id')->first();
        $stored?->update(['channel' => 'whatsapp', 'direction' => 'out']);

        try {
            $id = $this->gateway->sendText((string) PhoneNumber::toE164($lead->customer?->phone), $reply);
            $stored?->update(['external_id' => $id ?: null, 'status' => 'sent']);
        } catch (Throwable $e) {
            Log::warning("WhatsApp AI reply not sent for lead {$lead->id}: ".$e->getMessage());
            $stored?->update(['status' => 'failed']);
        }

        $lead->refresh();

        if ($lead->needs_human_attention || str_contains($reply, self::CLOSING_MARKER)) {
            $this->handOffToHuman($lead);
        }
    }

    /**
     * The AI finished qualifying (or the customer asked for a person): stop answering and tell the team.
     */
    private function handOffToHuman(Lead $lead): void
    {
        $lead->forceFill(['ai_paused' => true, 'needs_human_attention' => true])->saveQuietly();

        $recipients = $lead->agent ? collect([$lead->agent]) : User::where('role', UserRole::Admin)->get();
        $name = $lead->customer_name ?: $lead->customer?->name ?: "Lead #{$lead->id}";

        foreach ($recipients as $user) {
            Notification::make()
                ->title('WhatsApp: un cliente espera una persona')
                ->body("{$name} terminó la calificación o pidió hablar con un asesor. La IA dejó de responder.")
                ->warning()
                ->sendToDatabase($user);
        }
    }

    /**
     * @param  callable(string): string  $send
     */
    private function deliver(Lead $lead, callable $send, string $content, ?User $agent): Message
    {
        $to = (string) PhoneNumber::toE164($lead->customer?->phone);

        $message = $lead->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'channel' => 'whatsapp',
            'direction' => 'out',
            'status' => 'sending',
            'sent_by_user_id' => $agent?->id,
        ]);

        try {
            $message->update(['external_id' => $send($to) ?: null, 'status' => 'sent']);
        } catch (Throwable $e) {
            $message->update(['status' => 'failed']);

            throw $e;
        }

        return $message;
    }

    private function markAttended(Lead $lead, User $agent): void
    {
        $lead->forceFill([
            'ai_paused' => true,
            'unread_count' => 0,
            'agent_id' => $lead->agent_id ?? $agent->id,
            'last_contacted_at' => $lead->last_contacted_at ?? now(),
            'status' => $lead->status === LeadStatus::New ? LeadStatus::Contacted : $lead->status,
        ])->save();
    }

    private function findOrCreateLead(string $phone, ?string $profileName, string $text): Lead
    {
        $customer = User::where('role', UserRole::Customer)->whereIn('phone', PhoneNumber::lookupVariants($phone))->first();

        $lead = $customer
            ? Lead::where('customer_id', $customer->id)->where('status', '!=', LeadStatus::Closed)->where('source', 'whatsapp')->latest('id')->first()
            : null;

        return $lead ?? $this->capture->execute([
            'customer_name' => $profileName,
            'customer_phone' => $phone,
            'source' => 'whatsapp',
            'raw_message' => $text,
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function textOf(array $message): string
    {
        return match ($message['type'] ?? 'text') {
            'text' => (string) ($message['text']['body'] ?? ''),
            'button' => (string) ($message['button']['text'] ?? '[Botón]'),
            'interactive' => (string) ($message['interactive']['button_reply']['title'] ?? $message['interactive']['list_reply']['title'] ?? '[Respuesta]'),
            'image' => '[Imagen]'.(isset($message['image']['caption']) ? ' '.$message['image']['caption'] : ''),
            'audio' => '[Audio]',
            'video' => '[Video]',
            'document' => '[Documento]'.(isset($message['document']['filename']) ? ' '.$message['document']['filename'] : ''),
            'location' => '[Ubicación]',
            'sticker' => '[Sticker]',
            default => '[Mensaje no compatible]',
        };
    }
}
