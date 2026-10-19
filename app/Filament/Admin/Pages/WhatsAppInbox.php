<?php

namespace App\Filament\Admin\Pages;

use App\Enums\Channel;
use App\Enums\LeadTemperature;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\ChannelAccount;
use App\Models\Lead;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\CloudApiGateway;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppGateway;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Throwable;

class WhatsAppInbox extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static \UnitEnum|string|null $navigationGroup = 'Clientes';

    protected static ?string $navigationLabel = 'Bandeja de mensajes';

    protected static ?string $title = 'Bandeja de mensajes';

    protected static ?string $slug = 'whatsapp';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.admin.pages.whatsapp-inbox';

    #[Url]
    public ?int $lead = null;

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $channel = 'all';

    #[Url]
    public ?int $account = null;

    public string $reply = '';

    public ?int $templateId = null;

    /** @var list<string> */
    public array $templateVariables = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_leads']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = Lead::where('unread_count', '>', 0)->count();

        return $unread > 0 ? (string) $unread : null;
    }

    /**
     * Whether replies can go out: for a conversation, its own account; for the page, any channel at all.
     */
    public function isConfigured(?Lead $lead = null): bool
    {
        if ($lead) {
            if ($lead->conversationChannel()->isSocial()) {
                return (bool) $lead->channelAccount?->is_active && filled($lead->channelAccount->access_token);
            }

            $gateway = app(WhatsAppGateway::class);

            return ($gateway instanceof CloudApiGateway ? $gateway->forAccount($lead->channelAccount) : $gateway)->isConfigured();
        }

        return app(WhatsAppGateway::class)->isConfigured() || ChannelAccount::where('is_active', true)->exists();
    }

    /**
     * @return Collection<int, ChannelAccount>
     */
    public function accounts(): Collection
    {
        return ChannelAccount::where('is_active', true)
            ->when($this->channel !== 'all', fn (Builder $q) => $q->where('channel', $this->channel))
            ->orderBy('channel')->orderBy('label')->get();
    }

    /**
     * @return list<string>
     */
    private function inboxChannels(): array
    {
        return array_map(fn (Channel $c) => $c->value, Channel::inbox());
    }

    /**
     * @return Collection<int, Lead>
     */
    public function conversations(): Collection
    {
        $channels = Channel::tryFrom($this->channel) ? [$this->channel] : $this->inboxChannels();

        return Lead::query()
            ->whereHas('messages', fn (Builder $q) => $q->whereIn('channel', $channels))
            ->with(['customer', 'channelAccount'])
            ->withMax(['messages as last_message_at' => fn (Builder $q) => $q->whereIn('channel', $channels)], 'created_at')
            ->when($this->account, fn (Builder $q) => $q->where('channel_account_id', $this->account))
            ->when($this->filter === 'unread', fn (Builder $q) => $q->where('unread_count', '>', 0))
            ->when($this->filter === 'mine', fn (Builder $q) => $q->where('agent_id', auth()->id()))
            ->when($this->filter === 'hot', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('temperature', LeadTemperature::Hot)->orWhere('needs_human_attention', true)))
            ->orderByDesc('needs_human_attention')
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
        $this->account = null;
    }

    public function selectedLead(): ?Lead
    {
        return $this->lead ? Lead::with(['customer', 'channelAccount', 'messages' => fn ($q) => $q->whereIn('channel', $this->inboxChannels())->orderBy('id')])->find($this->lead) : null;
    }

    public function open(int $leadId): void
    {
        $this->lead = $leadId;
        $this->reset('reply', 'templateId', 'templateVariables');

        Lead::whereKey($leadId)->update(['unread_count' => 0]);
    }

    public function send(WhatsAppConversationService $conversations): void
    {
        $lead = $this->selectedLead();

        if (! $lead) {
            return;
        }

        try {
            if ($lead->isInsideReplyWindow()) {
                if (trim($this->reply) === '') {
                    return;
                }

                $conversations->sendAgentReply($lead, auth()->user(), trim($this->reply));
            } elseif ($lead->conversationChannel()->isSocial()) {
                Notification::make()->title('Pasaron más de 24 horas')->body('En '.$lead->conversationChannel()->getLabel().' solo se puede responder dentro de las 24 horas del último mensaje del cliente.')->warning()->send();

                return;
            } else {
                $template = WhatsAppTemplate::where('is_active', true)->find($this->templateId);

                if (! $template) {
                    Notification::make()->title('Elegí una plantilla')->warning()->send();

                    return;
                }

                $conversations->sendTemplate($lead, $template, array_slice(array_pad($this->templateVariables, $template->variables_count, ''), 0, $template->variables_count), auth()->user());
            }
        } catch (Throwable $e) {
            Notification::make()->title('No se pudo enviar')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->reset('reply', 'templateId', 'templateVariables');
    }

    public function toggleAi(WhatsAppConversationService $conversations): void
    {
        if ($lead = $this->selectedLead()) {
            $lead->ai_paused ? $conversations->resumeAi($lead) : $conversations->pauseAi($lead);
        }
    }

    public function leadUrl(Lead $lead): string
    {
        return LeadResource::getUrl('edit', ['record' => $lead]);
    }
}
