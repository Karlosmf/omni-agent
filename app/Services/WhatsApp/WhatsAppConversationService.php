<?php

namespace App\Services\WhatsApp;

use App\Actions\Leads\CaptureLeadAction;
use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Models\ChannelAccount;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\Channels\InboundMessage;
use App\Services\Channels\MetaSocialGateway;
use App\Support\PhoneNumber;
use Filament\Notifications\Notification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Runs the conversation pipeline for every inbox channel (WhatsApp, Instagram, Messenger):
 * store the message, answer with the AI, hand off to a person, and send agent replies.
 */
class WhatsAppConversationService
{
    public const AI_REPLIES_PER_MINUTE = 10;

    public const CLOSING_MARKER = 'Ya tengo todo lo que necesito';

    public function __construct(
        private WhatsAppGateway $gateway,
        private CaptureLeadAction $capture,
        private ProcessChatbotInteractionAction $chatbot,
        private MetaSocialGateway $social,
    ) {}

    /**
     * Store an incoming WhatsApp message, reply with the AI unless a person has taken over, and hand off when needed.
     *
     * @param  array<string, mixed>  $message  A "messages[]" item of the Cloud API webhook.
     * @param  string|null  $phoneNumberId  The business number that received it (null = the .env default).
     */
    public function receive(array $message, ?string $profileName = null, ?string $phoneNumberId = null): ?Lead
    {
        $externalId = (string) ($message['id'] ?? '');

        if ($externalId === '' || Message::where('external_id', $externalId)->exists()) {
            return null;
        }

        $phone = PhoneNumber::toE164('+'.ltrim((string) ($message['from'] ?? ''), '+'));

        if ($phone === null) {
            return null;
        }

        $account = $phoneNumberId ? ChannelAccount::findActive(Channel::WhatsApp, $phoneNumberId) : null;
        $text = $this->textOf($message);
        $lead = $this->findOrCreateLead($phone, $profileName, $text, $account);

        return $this->storeInbound($lead, Channel::WhatsApp, $externalId, $text, ($message['type'] ?? 'text') === 'text');
    }

    /**
     * Store an incoming Instagram / Messenger message. Unknown or disabled accounts are ignored.
     */
    public function receiveSocial(InboundMessage $incoming): ?Lead
    {
        if ($incoming->externalId === '' || Message::where('external_id', $incoming->externalId)->exists()) {
            return null;
        }

        $account = ChannelAccount::findActive($incoming->channel, $incoming->accountExternalId);

        if (! $account) {
            Log::info("Ignoring {$incoming->channel->value} message for unknown account {$incoming->accountExternalId}");

            return null;
        }

        $lead = $this->findOrCreateSocialLead($account, $incoming);

        return $this->storeInbound($lead, $incoming->channel, $incoming->externalId, $incoming->text, $incoming->isText);
    }

    private function storeInbound(Lead $lead, Channel $channel, string $externalId, string $text, bool $isText): ?Lead
    {
        try {
            $lead->messages()->create([
                'role' => 'user',
                'content' => $text,
                'channel' => $channel->value,
                'direction' => 'in',
                'external_id' => $externalId,
                'status' => 'received',
            ]);
        } catch (UniqueConstraintViolationException) {
            // Meta delivered the same message twice at once: the other job already handles it.
            return null;
        }

        $lead->forceFill([
            'last_inbound_at' => now(),
            'whatsapp_last_inbound_at' => $channel === Channel::WhatsApp ? now() : $lead->whatsapp_last_inbound_at,
            'unread_count' => $lead->unread_count + 1,
        ])->saveQuietly();

        if ($isText && ! $lead->ai_paused) {
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
        if (! $lead->isInsideReplyWindow()) {
            $only = $lead->conversationChannel() === Channel::WhatsApp ? ': solo se pueden enviar plantillas aprobadas.' : '.';

            throw new \RuntimeException('Pasaron más de 24 horas desde el último mensaje del cliente'.$only);
        }

        $message = $this->deliver($lead, fn (string $to) => $this->sendTextVia($lead, $to, $text), $text, $agent);
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
        if ($lead->conversationChannel() !== Channel::WhatsApp) {
            throw new \RuntimeException('Las plantillas solo existen en WhatsApp.');
        }

        $message = $this->deliver($lead, fn (string $to) => $this->whatsappGateway($lead)->sendTemplate($to, $template, $variables), $template->render($variables), $agent);

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

        $lastAssistantId = (int) $lead->messages()->where('role', 'assistant')->max('id');

        $reply = $this->chatbot->execute($text, $lead, $history, recordUserMessage: false);

        // The AI service stores its reply. When it failed it stored nothing, so record the apology
        // instead of re-tagging an older message.
        $stored = $lead->messages()->where('role', 'assistant')->where('id', '>', $lastAssistantId)->latest('id')->first()
            ?? $lead->messages()->create(['role' => 'assistant', 'content' => $reply]);
        $stored->update(['channel' => $lead->conversationChannel()->value, 'direction' => 'out']);

        try {
            $id = $this->sendTextVia($lead, $this->recipientOf($lead), $reply);
            $stored->update(['external_id' => $id ?: null, 'status' => 'sent']);
        } catch (Throwable $e) {
            Log::warning("WhatsApp AI reply not sent for lead {$lead->id}: ".$e->getMessage());
            $stored->update(['status' => 'failed']);
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
                ->title($lead->conversationChannel()->getLabel().': un cliente espera una persona')
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
        $to = $this->recipientOf($lead);

        $message = $lead->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'channel' => $lead->conversationChannel()->value,
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
            'first_response_at' => $lead->first_response_at ?? now(),
            'status' => $lead->status === LeadStatus::New ? LeadStatus::Contacted : $lead->status,
        ])->save();
    }

    private function findOrCreateLead(string $phone, ?string $profileName, string $text, ?ChannelAccount $account = null): Lead
    {
        $customer = User::where('role', UserRole::Customer)->whereIn('phone', PhoneNumber::lookupVariants($phone))->first();

        $lead = $customer
            ? Lead::where('customer_id', $customer->id)->where('status', '!=', LeadStatus::Closed)->where('source', 'whatsapp')->latest('id')->first()
            : null;

        $lead ??= $this->capture->execute([
            'customer_name' => $profileName,
            'customer_phone' => $phone,
            'source' => 'whatsapp',
            'raw_message' => $text,
        ]);

        if ($lead->channel === null || ($account && $lead->channel_account_id === null)) {
            $lead->forceFill(['channel' => Channel::WhatsApp->value, 'channel_account_id' => $lead->channel_account_id ?? $account?->id])->saveQuietly();
        }

        return $lead;
    }

    /**
     * Customers on Instagram / Messenger are identified by Meta's per-page user id, not by a phone.
     * A returning customer whose earlier lead was closed gets a new lead on the same customer.
     */
    private function findOrCreateSocialLead(ChannelAccount $account, InboundMessage $incoming): Lead
    {
        $known = Lead::where('channel', $incoming->channel->value)
            ->where('channel_user_id', $incoming->senderId)
            ->where('channel_account_id', $account->id);

        $open = (clone $known)->where('status', '!=', LeadStatus::Closed)->latest('id')->first();

        if ($open) {
            return $open;
        }

        $name = $incoming->profileName ?? $this->lookupProfileName($account, $incoming->senderId);
        $previous = $known->latest('id')->first();

        if ($previous) {
            $lead = Lead::create([
                'customer_id' => $previous->customer_id,
                'customer_name' => $previous->customer_name,
                'source' => $incoming->channel->value,
                'raw_message' => $incoming->text,
                'status' => LeadStatus::New,
                'temperature' => LeadTemperature::Cool,
                'ai_data' => [],
                'needs_human_attention' => false,
            ]);
        } else {
            $lead = $this->capture->execute([
                'customer_name' => $name,
                'source' => $incoming->channel->value,
                'raw_message' => $incoming->text,
            ]);
        }

        $lead->forceFill([
            'channel' => $incoming->channel->value,
            'channel_user_id' => $incoming->senderId,
            'channel_account_id' => $account->id,
            'attribution' => $incoming->referral ?: null,
        ])->saveQuietly();

        return $lead;
    }

    private function lookupProfileName(ChannelAccount $account, string $userId): ?string
    {
        try {
            return $this->social->profileName($account, $userId);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Where a reply goes: the customer's phone on WhatsApp, Meta's user id on Instagram / Messenger.
     */
    private function recipientOf(Lead $lead): string
    {
        return $lead->conversationChannel()->isSocial()
            ? (string) $lead->channel_user_id
            : (string) PhoneNumber::toE164($lead->customer?->phone);
    }

    private function sendTextVia(Lead $lead, string $to, string $text): string
    {
        if (! $lead->conversationChannel()->isSocial()) {
            return $this->whatsappGateway($lead)->sendText($to, $text);
        }

        $account = $lead->channelAccount;

        if (! $account || ! $account->is_active) {
            throw new \RuntimeException('La cuenta de '.$lead->conversationChannel()->getLabel().' de esta conversación no está conectada.');
        }

        return $this->social->sendText($account, $to, $text);
    }

    private function whatsappGateway(Lead $lead): WhatsAppGateway
    {
        return $this->gateway instanceof CloudApiGateway
            ? $this->gateway->forAccount($lead->channelAccount)
            : $this->gateway;
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
