<?php

namespace App\Filament\Admin\Resources\BotNotices;

use App\Enums\BotNoticeType;
use App\Enums\LeadStatus;
use App\Filament\Admin\Pages\WhatsAppInbox;
use App\Filament\Admin\Resources\BotNotices\Pages\ListBotNotices;
use App\Models\BotNotice;
use App\Models\Lead;
use App\Services\Bot\InquiryMergeService;
use App\Services\Bot\TripData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use UnitEnum;

/**
 * The notices tray: what needs a person. It only shows during the team's working hours.
 */
class BotNoticeResource extends Resource
{
    protected static ?string $model = BotNotice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static UnitEnum|string|null $navigationGroup = 'Chat';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Aviso';

    protected static ?string $pluralModelLabel = 'Avisos';

    protected static ?string $navigationLabel = 'Avisos';

    protected static ?string $slug = 'avisos';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyPermission(['manage_leads']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = BotNotice::visibleCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('lead.customer')
            ->when(get_agency_settings()?->isWithinBusinessHours() === false, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(fn (): string => get_agency_settings()?->isWithinBusinessHours() === false ? 'Fuera del horario laboral' : 'No hay avisos')
            ->emptyStateDescription(fn (): ?string => get_agency_settings()?->isWithinBusinessHours() === false ? 'Los avisos se muestran en el horario configurado en el bot recepcionista.' : null)
            ->columns([
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('lead.customer_name')
                    ->label('Cliente')
                    ->state(fn (BotNotice $record): string => $record->lead->displayName()),
                TextColumn::make('destination')
                    ->label('Viaje')
                    ->state(fn (BotNotice $record): string => TripData::of($record->lead)['destino'] ?? 'Sin destino todavía'),
                TextColumn::make('detail')->label('Detalle')->wrap()->placeholder('—'),
                TextColumn::make('created_at')->label('Creado')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Leídos')
                    ->nullable()
                    ->trueLabel('Solo leídos')
                    ->falseLabel('Sin leer')
                    ->default(false),
            ])
            ->recordActions([
                Action::make('openConversation')
                    ->label('Ver conversación')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (BotNotice $record): string => WhatsAppInbox::getUrl(['lead' => $record->lead_id])),
                Action::make('confirmNew')
                    ->label('Confirmar como nueva')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (BotNotice $record): bool => self::awaitsVerification($record))
                    ->action(function (BotNotice $record): void {
                        $record->lead->update(['status' => LeadStatus::New]);
                        $record->markAsRead();
                    }),
                Action::make('mergeInto')
                    ->label('Unir a la consulta de…')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->visible(fn (BotNotice $record): bool => self::awaitsVerification($record))
                    ->modalDescription('Los datos y los mensajes pasan a la consulta elegida y esta se elimina.')
                    ->schema([
                        Select::make('target_id')
                            ->label('Consulta abierta')
                            ->options(fn (BotNotice $record): array => self::otherOpenInquiries($record->lead))
                            ->required(),
                    ])
                    ->action(function (BotNotice $record, array $data, InquiryMergeService $merger): void {
                        $target = Lead::where('customer_id', $record->lead->customer_id)->open()->findOrFail($data['target_id']);

                        try {
                            $merger->merge($record->lead, $target);
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Consultas unidas')->success()->send();
                    }),
                Action::make('markRead')
                    ->label('Marcar leído')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (BotNotice $record): bool => $record->read_at === null && ! self::awaitsVerification($record))
                    ->action(fn (BotNotice $record) => $record->markAsRead()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBotNotices::route('/'),
        ];
    }

    private static function awaitsVerification(BotNotice $notice): bool
    {
        return $notice->type === BotNoticeType::VerifyNewInquiry && $notice->read_at === null && $notice->lead->status === LeadStatus::PendingReview;
    }

    /**
     * @return array<int, string>
     */
    private static function otherOpenInquiries(Lead $lead): array
    {
        return Lead::where('customer_id', $lead->customer_id)->open()->whereKeyNot($lead->id)->latest('id')->get()
            ->mapWithKeys(fn (Lead $inquiry) => [$inquiry->id => (TripData::of($inquiry)['destino'] ?? 'Sin destino')." (#{$inquiry->id}, {$inquiry->status->getLabel()})"])
            ->all();
    }
}
