<?php

namespace App\Services\WhatsApp;

use App\Actions\Leads\CaptureLeadAction;
use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Enums\BotNoticeType;
use App\Enums\Channel;
use App\Enums\ChatMenuAction;
use App\Enums\ChatMenuSource;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BotNotice;
use App\Models\ChannelAccount;
use App\Models\ChatMenu;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\Bot\ChatMenuService;
use App\Services\Bot\ReceptionistBot;
use App\Services\Channels\InboundMessage;
use App\Services\Channels\MetaSocialGateway;
use App\Support\PhoneNumber;
use App\Support\ReplyButtons;
use Filament\Notifications\Notification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
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
        private ReceptionistBot $bot,
        private ChatMenuService $menus,
    ) {}

    /**
     * Store an incoming WhatsApp message, reply with the AI unless a person has taken over, and hand off when needed.
     *
     * @param  array<string, mixed>  $message  A "messages[]" item of the Cloud API webhook.
     * @param  string|null  $phoneNumberId  The business number that received it (null = the .env default).
     * @param  array<string, mixed>|null  $rawPayload  The provider's event as it arrived (defaults to the message itself).
     */
    public function receive(array $message, ?string $profileName = null, ?string $phoneNumberId = null, ?array $rawPayload = null): ?Lead
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
        $this->keepExistingClientsWithPeople($lead);

        // A tapped reply button or list row is an answer in words, like typed text.
        $isText = in_array($message['type'] ?? 'text', ['text', 'button', 'interactive'], true);

        return $this->storeInbound($lead, Channel::WhatsApp, $externalId, $text, $isText, $rawPayload ?? $message, $this->tapIdOf($message));
    }

    /**
     * Coexistence: the team answered from the WhatsApp Business app on the phone. The reply is stored in the
     * conversation and the AI steps back, as when an agent answers from the inbox. Chats we have no lead for are
     * not turned into leads: a person is already handling them.
     *
     * @param  array<string, mixed>  $echo  A "message_echoes[]" item of the "smb_message_echoes" webhook field.
     * @param  array<string, mixed>|null  $rawPayload  The provider's event as it arrived (defaults to the echo itself).
     */
    public function receiveBusinessAppEcho(array $echo, ?string $phoneNumberId = null, ?array $rawPayload = null): ?Lead
    {
        $externalId = (string) ($echo['id'] ?? '');
        $phone = PhoneNumber::toE164('+'.ltrim((string) ($echo['to'] ?? ''), '+'));

        if ($externalId === '' || $phone === null || Message::where('external_id', $externalId)->exists()) {
            return null;
        }

        $customer = $this->customerByPhone($phone);
        $lead = $customer ? $this->openWhatsAppLeadFor($customer) : null;
        $sentAt = isset($echo['timestamp']) ? Carbon::createFromTimestamp((int) $echo['timestamp']) : now();

        if (! $lead) {
            ReceptionistBot::rememberHumanChat($phone, $sentAt);

            return null;
        }

        // A message written on the phone before this conversation started (e.g. read again when polling) is not a takeover.
        if ($sentAt->lt($lead->created_at)) {
            return null;
        }

        try {
            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $this->textOf($echo),
                'channel' => Channel::WhatsApp->value,
                'direction' => 'out',
                'external_id' => $externalId,
                'status' => 'sent',
                'sent_from' => Message::SENT_FROM_PHONE,
                'payload' => $rawPayload ?? $echo,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $lead->forceFill([
            ...ReceptionistBot::pauseAfterHumanReply($lead),
            'unread_count' => 0,
            'last_contacted_at' => $lead->last_contacted_at ?? now(),
            'first_response_at' => $lead->first_response_at ?? now(),
            'status' => $lead->status === LeadStatus::New ? LeadStatus::Contacted : $lead->status,
        ])->saveQuietly();

        return $lead;
    }

    /**
     * The number is also the agency's everyday line: someone who already booked a trip talks to a person, not to the qualifying AI.
     */
    private function keepExistingClientsWithPeople(Lead $lead): void
    {
        if (! ReceptionistBot::isEnabled() && ! $lead->ai_paused && $lead->customer?->bookings()->exists()) {
            $lead->forceFill(['ai_paused' => true, 'needs_human_attention' => true])->saveQuietly();
        }
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

    /**
     * @param  array<string, mixed>|null  $payload  The provider's raw event.
     * @param  string|null  $tapId  The id of the reply button or list row the customer tapped.
     */
    private function storeInbound(Lead $lead, Channel $channel, string $externalId, string $text, bool $isText, ?array $payload = null, ?string $tapId = null): ?Lead
    {
        try {
            $inbound = $lead->messages()->create([
                'role' => 'user',
                'content' => $text,
                'channel' => $channel->value,
                'direction' => 'in',
                'external_id' => $externalId,
                'status' => 'received',
                'payload' => $payload,
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

        if ($channel === Channel::WhatsApp && $this->answerWithMenu($lead, $inbound, $isText, $tapId)) {
            return $lead;
        }

        // A tapped menu option may have rewritten the message into the option's title: that is what the AI reads.
        if (ReceptionistBot::isEnabled()) {
            $this->replyWithBot($lead, $inbound, $isText);
        } elseif ($isText && ! $lead->isAiPaused()) {
            $this->replyWithAi($lead, (string) $inbound->content);
        }

        return $lead;
    }

    /**
     * Chat menus (WhatsApp list messages): a keyword opens one, and a tap on its rows is answered here.
     * Returns false when the bot or the assistant should answer instead (including a tap meant for them).
     */
    private function answerWithMenu(Lead $lead, Message $inbound, bool $isText, ?string $tapId): bool
    {
        if (! $isText || ReceptionistBot::isPersonAttending($lead)) {
            return false;
        }

        if ($tapId !== null) {
            return str_starts_with($tapId, ChatMenuService::ID_PREFIX) && $this->answerMenuTap($lead, $inbound, $tapId);
        }

        $menu = $this->menus->forText((string) $inbound->content);

        if (! $menu) {
            return false;
        }

        $this->sendMenu($lead, $menu);

        return true;
    }

    private function answerMenuTap(Lead $lead, Message $inbound, string $tapId): bool
    {
        $tap = $this->menus->resolveTap($tapId);

        // The menu was deleted or switched off after it was sent: the bot reads the option like any answer.
        if ($tap === null) {
            return false;
        }

        $menu = $tap['menu'];

        if ($tap['more']) {
            $this->sendMenu($lead, $menu, $tap['destination'], $tap['offset']);

            return true;
        }

        if ($tap['title'] === null) {
            $this->sendAutomatic($lead, 'Esa opción ya no está disponible. Te muestro las opciones actualizadas.');
            $this->sendMenu($lead, $menu);

            return true;
        }

        $inbound->update(['content' => $tap['title']]);

        if ($tap['package']) {
            $lead->forceFill(['travel_package_id' => $tap['package']->id])->saveQuietly();
        }

        if ($menu->on_select === ChatMenuAction::Reply && filled($menu->reply_text)) {
            $this->sendAutomatic($lead, str_replace('{opcion}', $tap['title'], (string) $menu->reply_text));

            return true;
        }

        $next = $menu->on_select === ChatMenuAction::Menu ? $menu->nextMenu : null;

        if ($next?->is_active) {
            // A chosen destination narrows the packages of the next menu.
            $this->sendMenu($lead, $next, $menu->source === ChatMenuSource::Destinations ? $tap['value'] : null);

            return true;
        }

        return false;
    }

    private function sendMenu(Lead $lead, ChatMenu $menu, ?string $destination = null, int $offset = 0): void
    {
        $rows = $this->menus->rows($menu, $destination, $offset);

        if ($rows === []) {
            $this->sendAutomatic($lead, 'En este momento no hay opciones para mostrarte. Contame qué estás buscando y te ayudamos.');

            return;
        }

        $this->sendAutomatic(
            $lead,
            $menu->body,
            fn (string $to) => $this->whatsappGateway($lead)->sendList($to, $menu->body, $menu->button_text ?: 'Ver opciones', $rows, $menu->header, $menu->name),
            ['list' => array_column($rows, 'title')],
        );
    }

    /**
     * A message no AI wrote (a menu or its fixed answer). If it cannot go out, a person has to pick the conversation up.
     *
     * @param  (callable(string): string)|null  $send  How to send it; plain text when null.
     * @param  array<string, mixed>|null  $payload  What went with the text, for the inbox.
     */
    private function sendAutomatic(Lead $lead, string $text, ?callable $send = null, ?array $payload = null): void
    {
        $stored = $lead->messages()->create([
            'role' => 'assistant',
            'content' => $text,
            'channel' => $lead->conversationChannel()->value,
            'direction' => 'out',
            'status' => 'sending',
            'sent_from' => Message::SENT_FROM_MENU,
            'payload' => $payload,
        ]);

        try {
            $to = $this->recipientOf($lead);
            $id = $send ? $send($to) : $this->sendTextVia($lead, $to, $text);
            $stored->update(['external_id' => $id ?: null, 'status' => 'sent']);
        } catch (Throwable $e) {
            Log::warning("Chat menu not sent for lead {$lead->id}: ".$e->getMessage());
            $stored->update(['status' => 'failed']);
            BotNotice::raise($lead, BotNoticeType::Handoff, 'No se pudo enviar un menú del chat al cliente.');
            $lead->forceFill(['needs_human_attention' => true])->saveQuietly();
        }
    }

    /**
     * An agent answers from the inbox. Taking over pauses the AI for this conversation.
     *
     * @throws \RuntimeException When free-form text is sent outside the 24 h window.
     */
    public function sendAgentReply(Lead $lead, User $agent, string $text): Message
    {
        $humanAgent = ! $lead->isInsideReplyWindow() && $lead->canReplyAsHumanAgent();

        if (! $humanAgent && ! $lead->isInsideReplyWindow()) {
            $only = $lead->conversationChannel() === Channel::WhatsApp ? ': solo se pueden enviar plantillas aprobadas.' : '.';

            throw new \RuntimeException('Pasaron más de 24 horas desde el último mensaje del cliente'.$only);
        }

        $message = $this->deliver($lead, fn (string $to) => $this->sendTextVia($lead, $to, $text, $humanAgent), $text, $agent);
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

    /**
     * An agent sends a document (the proposal PDF) from a public URL. Only on WhatsApp and inside the 24 h window.
     *
     * @throws \RuntimeException When the channel or the window does not allow it.
     */
    public function sendAgentDocument(Lead $lead, User $agent, string $url, string $filename, ?string $caption = null): Message
    {
        if ($lead->conversationChannel() !== Channel::WhatsApp) {
            throw new \RuntimeException('Por ahora los documentos solo se envían por WhatsApp.');
        }

        if (! $lead->isInsideReplyWindow()) {
            throw new \RuntimeException('Pasaron más de 24 horas desde el último mensaje del cliente: solo se pueden enviar plantillas aprobadas.');
        }

        $caption = trim((string) $caption) ?: null;

        $message = $this->deliver(
            $lead,
            fn (string $to) => $this->whatsappGateway($lead)->sendDocument($to, $url, $filename, $caption),
            $caption ?? "📎 {$filename}",
            $agent,
            ['attachment' => ['url' => $url, 'name' => $filename]],
        );
        $this->markAttended($lead, $agent);

        return $message;
    }

    /**
     * The WhatsApp conversation a booking can be sent to: its own lead, or the customer's open WhatsApp inquiry.
     */
    public function conversationForBooking(Booking $booking): ?Lead
    {
        $lead = $booking->lead;

        if ($lead && $lead->conversationChannel() === Channel::WhatsApp) {
            return $lead;
        }

        return $booking->customer ? $this->openWhatsAppLeadFor($booking->customer) : null;
    }

    /**
     * Whether replies can go out on this conversation's channel and account.
     */
    public function isConfiguredFor(Lead $lead): bool
    {
        if ($lead->conversationChannel()->isSocial()) {
            return (bool) $lead->channelAccount?->is_active && filled($lead->channelAccount->access_token);
        }

        return $this->whatsappGateway($lead)->isConfigured();
    }

    /**
     * Whether earlier messages of this conversation can be brought from the provider (only WhatsApp through Zernio keeps them).
     */
    public function canImportHistory(Lead $lead): bool
    {
        return $this->gateway instanceof ZernioGateway
            && $this->gateway->isConfigured()
            && $lead->conversationChannel() === Channel::WhatsApp
            && PhoneNumber::toE164($lead->customer?->phone) !== null;
    }

    /**
     * Brings the messages of the last days from Zernio into the conversation, in their real order, so the team and the
     * bot see what was said before the app was listening. Nothing is answered, counted as unread or paused, and a
     * message already stored is skipped.
     *
     * @return int How many messages were added.
     *
     * @throws \RuntimeException When Zernio is not configured or does not answer.
     */
    public function importWhatsAppHistory(Lead $lead, int $days = 30): int
    {
        if (! $this->canImportHistory($lead)) {
            throw new \RuntimeException('El historial solo se puede traer de conversaciones de WhatsApp conectadas por Zernio.');
        }

        /** @var ZernioGateway $zernio */
        $zernio = $this->gateway;
        $phone = (string) PhoneNumber::toE164($lead->customer->phone);
        $conversationId = $zernio->conversationIdFor($phone);

        if ($conversationId === null) {
            return 0;
        }

        $messages = collect($zernio->messagesSince($conversationId, now()->subDays($days)))
            ->filter(fn (array $message) => filled($message['id'] ?? null))
            ->unique('id');

        $known = Message::whereIn('external_id', $messages->pluck('id'))->pluck('external_id')->all();
        $events = app(ZernioEventHandler::class);
        $added = 0;

        foreach ($messages->reject(fn (array $message) => in_array($message['id'], $known, true)) as $message) {
            $incoming = ($message['direction'] ?? '') === 'incoming';
            $source = $message['metadata']['source'] ?? null;
            $sentAt = Carbon::parse($message['createdAt'] ?? $message['sentAt'] ?? now())->setTimezone(config('app.timezone'));

            $text = $this->textOf($events->toCloudMessage($message['id'], $phone, [
                'text' => $message['message'] ?? null,
                'attachments' => $message['attachments'] ?? [],
                'metadata' => $message['metadata'] ?? [],
            ]));

            $stored = new Message([
                'lead_id' => $lead->id,
                'role' => $incoming ? 'user' : 'assistant',
                'content' => $text,
                'channel' => Channel::WhatsApp->value,
                'direction' => $incoming ? 'in' : 'out',
                'external_id' => $message['id'],
                'status' => $incoming ? 'received' : ($message['deliveryStatus'] ?? 'sent'),
                'sent_from' => $incoming ? null : ($source === 'whatsapp_business_app' || $source === 'coexistence_history' ? Message::SENT_FROM_PHONE : Message::SENT_FROM_ZERNIO),
                'payload' => ['imported' => true, 'zernio' => $message],
            ]);
            $stored->forceFill(['created_at' => $sentAt, 'updated_at' => now()]);

            try {
                $stored->save();
                $added++;
            } catch (UniqueConstraintViolationException) {
                // Arrived live while importing: it is already in the conversation.
            }
        }

        return $added;
    }

    /**
     * Paused by hand: stays paused until someone resumes it.
     */
    public function pauseAi(Lead $lead): void
    {
        $lead->update(['ai_paused' => true, 'ai_paused_until' => null]);
    }

    public function resumeAi(Lead $lead): void
    {
        $lead->update(['ai_paused' => false, 'ai_paused_until' => null]);
    }

    /**
     * The receptionist answers and the reply goes out here. If it cannot go out, a person has to pick the conversation up.
     */
    private function replyWithBot(Lead $lead, Message $inbound, bool $isText): void
    {
        $key = 'wa-ai:'.$lead->id;

        if (RateLimiter::tooManyAttempts($key, self::AI_REPLIES_PER_MINUTE)) {
            Log::info("Receptionist bot throttled for lead {$lead->id}");

            return;
        }

        RateLimiter::hit($key, 60);

        $reply = $this->bot->respond($lead, $inbound, $isText);

        if (! $reply) {
            return;
        }

        $target = $reply->lead;

        $stored = $target->messages()->create([
            'role' => 'assistant',
            'content' => $reply->text,
            'channel' => $target->conversationChannel()->value,
            'direction' => 'out',
            'status' => 'sending',
            'sent_from' => Message::SENT_FROM_BOT,
            'input_tokens' => $reply->inputTokens,
            'output_tokens' => $reply->outputTokens,
            'ai_model' => $reply->model,
            'payload' => $reply->buttons !== [] ? ['buttons' => $reply->buttons] : null,
        ]);

        try {
            $id = $this->sendTextVia($target, $this->recipientOf($target), $reply->text, buttons: $reply->buttons);
            $stored->update(['external_id' => $id ?: null, 'status' => 'sent']);
        } catch (Throwable $e) {
            Log::warning("Bot reply not sent for lead {$target->id}: ".$e->getMessage());
            $stored->update(['status' => 'failed']);
            BotNotice::raise($target, BotNoticeType::Handoff, 'No se pudo enviar la respuesta del bot al cliente.');
            $target->forceFill(['needs_human_attention' => true])->saveQuietly();
        }
    }

    private function replyWithAi(Lead $lead, string $text): void
    {
        $key = 'wa-ai:'.$lead->id;

        if (RateLimiter::tooManyAttempts($key, self::AI_REPLIES_PER_MINUTE)) {
            Log::info("WhatsApp AI throttled for lead {$lead->id}");

            return;
        }

        RateLimiter::hit($key, 60);

        $history = $lead->messages()->newestFirst()->take(10)->get()->reverse()
            ->map(fn (Message $m) => ['role' => $m->role === 'user' ? 'user' : 'assistant', 'content' => $m->content])->values()->all();

        $lastAssistantId = (int) $lead->messages()->where('role', 'assistant')->max('id');
        $wasQualified = (bool) ($lead->ai_data['calificacion_completa'] ?? false);

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

        $qualifiedNow = ! $wasQualified && ($lead->ai_data['calificacion_completa'] ?? false);

        if ($lead->needs_human_attention || $qualifiedNow || str_contains($reply, self::CLOSING_MARKER)) {
            $this->handOffToHuman($lead);
        }
    }

    /**
     * The AI finished qualifying (or the customer asked for a person): stop answering and tell the team.
     */
    private function handOffToHuman(Lead $lead): void
    {
        $lead->forceFill(['ai_paused' => true, 'needs_human_attention' => true])->saveQuietly();

        $recipients = $lead->staffToNotify();
        $name = $lead->displayName();

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
     * @param  array<string, mixed>|null  $payload  What went with the text (buttons, attachment), for the inbox.
     */
    private function deliver(Lead $lead, callable $send, string $content, ?User $agent, ?array $payload = null): Message
    {
        $to = $this->recipientOf($lead);

        $message = $lead->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'channel' => $lead->conversationChannel()->value,
            'direction' => 'out',
            'status' => 'sending',
            'sent_by_user_id' => $agent?->id,
            'payload' => $payload,
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
            ...ReceptionistBot::pauseAfterHumanReply($lead),
            'unread_count' => 0,
            'agent_id' => $lead->agent_id ?? $agent->id,
            'last_contacted_at' => $lead->last_contacted_at ?? now(),
            'first_response_at' => $lead->first_response_at ?? now(),
            'status' => $lead->status === LeadStatus::New ? LeadStatus::Contacted : $lead->status,
        ])->save();
    }

    private function findOrCreateLead(string $phone, ?string $profileName, string $text, ?ChannelAccount $account = null): Lead
    {
        $customer = $this->customerByPhone($phone);
        $lead = $customer ? $this->openWhatsAppLeadFor($customer) : null;

        $lead ??= $this->webLeadForWhatsApp($text, $phone);

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

    private function customerByPhone(string $phone): ?User
    {
        return User::where('role', UserRole::Customer)->whereIn('phone', PhoneNumber::lookupVariants($phone))->first();
    }

    /**
     * The customer's latest WhatsApp inquiry that is still open.
     */
    public function openWhatsAppLeadFor(User $customer): ?Lead
    {
        return Lead::where('customer_id', $customer->id)->where('status', '!=', LeadStatus::Closed)
            ->where(fn ($query) => $query->where('source', 'whatsapp')->orWhere('channel', Channel::WhatsApp->value))
            ->latest('id')->first();
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

        if ($web = Lead::findByLinkCode($incoming->referral['ref'] ?? null) ?? Lead::findByLinkCode($incoming->text)) {
            return $this->attachToChannel($web, $incoming->channel, $account, $incoming->senderId);
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

    /**
     * Someone tapped "Seguir por Instagram/Messenger" in the web chat. Meta may send the ref on its own,
     * before any message: remember who it is so their first message lands in the web lead.
     */
    public function linkSocialReferral(Channel $channel, string $accountExternalId, string $senderId, string $ref): ?Lead
    {
        $account = ChannelAccount::findActive($channel, $accountExternalId);
        $web = Lead::findByLinkCode($ref);

        if (! $account || ! $web || $senderId === '') {
            return null;
        }

        $alreadyOpen = Lead::where('channel', $channel->value)->where('channel_user_id', $senderId)
            ->where('channel_account_id', $account->id)->where('status', '!=', LeadStatus::Closed)->exists();

        return $alreadyOpen ? null : $this->attachToChannel($web, $channel, $account, $senderId);
    }

    /**
     * A WhatsApp message with the code of a web chat, from the same phone the customer gave there.
     */
    private function webLeadForWhatsApp(string $text, string $phone): ?Lead
    {
        $web = Lead::findByLinkCode($text);

        if (! $web || ! in_array(PhoneNumber::toE164($web->customer?->phone), PhoneNumber::lookupVariants($phone), true)) {
            return null;
        }

        return $this->attachToChannel($web, Channel::WhatsApp, null, null);
    }

    /**
     * The web lead keeps its data and history; the conversation now continues on the channel. The code is single-use.
     */
    private function attachToChannel(Lead $lead, Channel $channel, ?ChannelAccount $account, ?string $channelUserId): Lead
    {
        $lead->forceFill([
            'channel' => $channel->value,
            'channel_user_id' => $channelUserId,
            'channel_account_id' => $account?->id ?? $lead->channel_account_id,
            'channel_link_code' => null,
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

    /**
     * @param  list<string>  $buttons  Reply buttons on WhatsApp; written into the text on channels without them.
     */
    private function sendTextVia(Lead $lead, string $to, string $text, bool $humanAgent = false, array $buttons = []): string
    {
        if (! $lead->conversationChannel()->isSocial()) {
            return $this->whatsappGateway($lead)->sendText($to, $text, $buttons);
        }

        $account = $lead->channelAccount;

        if (! $account || ! $account->is_active) {
            throw new \RuntimeException('La cuenta de '.$lead->conversationChannel()->getLabel().' de esta conversación no está conectada.');
        }

        return $this->social->sendText($account, $to, ReplyButtons::asText($text, $buttons), $humanAgent);
    }

    private function whatsappGateway(Lead $lead): WhatsAppGateway
    {
        return $this->gateway instanceof CloudApiGateway
            ? $this->gateway->forAccount($lead->channelAccount)
            : $this->gateway;
    }

    /**
     * The id of the reply button or list row the customer tapped, if the message is a tap.
     *
     * @param  array<string, mixed>  $message
     */
    private function tapIdOf(array $message): ?string
    {
        if (($message['type'] ?? null) !== 'interactive') {
            return null;
        }

        $id = trim((string) ($message['interactive']['list_reply']['id'] ?? $message['interactive']['button_reply']['id'] ?? ''));

        return $id === '' ? null : $id;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function textOf(array $message): string
    {
        return match ($message['type'] ?? 'text') {
            'text' => (string) ($message['text']['body'] ?? ''),
            'button' => (string) ($message['button']['text'] ?? '[Botón]'),
            'interactive' => (string) ($message['interactive']['button_reply']['title'] ?? $message['interactive']['list_reply']['title'] ?? '') ?: '[Respuesta]',
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
