<?php

namespace App\Filament\Admin\Pages;

use App\Enums\LeadTemperature;
use App\Filament\Admin\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\WhatsAppTemplate;
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

    protected static ?string $navigationLabel = 'Bandeja de WhatsApp';

    protected static ?string $title = 'Bandeja de WhatsApp';

    protected static ?string $slug = 'whatsapp';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.admin.pages.whatsapp-inbox';

    #[Url]
    public ?int $lead = null;

    #[Url]
    public string $filter = 'all';

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

    public function isConfigured(): bool
    {
        return app(WhatsAppGateway::class)->isConfigured();
    }

    /**
     * @return Collection<int, Lead>
     */
    public function conversations(): Collection
    {
        return Lead::query()
            ->whereHas('messages', fn (Builder $q) => $q->where('channel', 'whatsapp'))
            ->with('customer')
            ->withMax(['messages as last_message_at' => fn (Builder $q) => $q->where('channel', 'whatsapp')], 'created_at')
            ->when($this->filter === 'unread', fn (Builder $q) => $q->where('unread_count', '>', 0))
            ->when($this->filter === 'mine', fn (Builder $q) => $q->where('agent_id', auth()->id()))
            ->when($this->filter === 'hot', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('temperature', LeadTemperature::Hot)->orWhere('needs_human_attention', true)))
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();
    }

    public function selectedLead(): ?Lead
    {
        return $this->lead ? Lead::with(['customer', 'messages' => fn ($q) => $q->where('channel', 'whatsapp')->orderBy('id')])->find($this->lead) : null;
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
            if ($lead->isInsideWhatsAppWindow()) {
                if (trim($this->reply) === '') {
                    return;
                }

                $conversations->sendAgentReply($lead, auth()->user(), trim($this->reply));
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
