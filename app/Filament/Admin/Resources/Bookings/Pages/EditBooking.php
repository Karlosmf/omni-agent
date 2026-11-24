<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Bookings\Widgets\BookingFinancialSummary;
use App\Mail\BookingProposalMail;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\Lead;
use App\Services\WhatsApp\WhatsAppConversationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Throwable;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            BookingFinancialSummary::class,
        ];
    }

    /**
     * A payment (or a new plan) can move the booking to "Señado": refresh the form so saving it
     * later does not write the stale status back.
     */
    #[On('installments-changed')]
    public function refreshAfterInstallmentChange(): void
    {
        $this->refreshFormData(['status']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('renew_validity')
                ->label('Renovar validez')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn ($record) => $record->isExpired() && in_array($record->status, [BookingStatus::Borrador, BookingStatus::Presupuesto], true))
                ->requiresConfirmation()
                ->modalDescription('La propuesta quedará vigente 7 días desde hoy.')
                ->action(function ($record) {
                    $record->renewValidity();
                    $this->refreshFormData(['valid_until']);
                }),
            Action::make('whatsapp_pdf')
                ->label('Enviar PDF por WhatsApp')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (Booking $record): bool => ($lead = $this->whatsAppConversation($record)) !== null && app(WhatsAppConversationService::class)->isConfiguredFor($lead))
                ->disabled(fn (Booking $record): bool => ! $this->whatsAppConversation($record)?->isInsideReplyWindow())
                ->tooltip(fn (Booking $record): ?string => $this->whatsAppConversation($record)?->isInsideReplyWindow()
                    ? null
                    : 'Pasaron más de 24 horas desde el último mensaje del cliente: esperá a que vuelva a escribir o mandale una plantilla desde la bandeja.')
                ->form([
                    Radio::make('format')
                        ->label('Formato del documento')
                        ->options([
                            'budget_only' => 'Solo Presupuesto',
                            'full' => 'Presupuesto + Detalle de la Idea de Viaje',
                        ])
                        ->default('budget_only')
                        ->required(),
                    Textarea::make('message')
                        ->label('Mensaje')
                        ->rows(4)
                        ->maxLength(1000)
                        ->default(fn (Booking $record): string => $this->whatsAppCaption($record)),
                ])
                ->action(function (Booking $record, array $data, WhatsAppConversationService $conversations) {
                    $lead = $this->whatsAppConversation($record);

                    if (! $lead) {
                        Notification::make()->title('Este cliente no tiene una conversación de WhatsApp abierta')->warning()->send();

                        return;
                    }

                    try {
                        $conversations->sendAgentDocument(
                            $lead,
                            auth()->user(),
                            $record->publicPdfUrl($data['format']),
                            'presupuesto-'.$record->file_number.'.pdf',
                            $data['message'] ?? null,
                        );
                    } catch (Throwable $e) {
                        Notification::make()->title('No se pudo enviar el presupuesto')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    BookingActivity::log($record, 'whatsapp_sent', 'Presupuesto (PDF) enviado por WhatsApp a '.($lead->customer?->phone ?? 'cliente'));

                    Notification::make()
                        ->title('Presupuesto enviado por WhatsApp')
                        ->body('El PDF quedó en la conversación de la bandeja de mensajes.')
                        ->success()
                        ->send();
                }),
            Action::make('whatsapp')
                ->label('Abrir en WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(function ($record) {
                    $phone = preg_replace('/[^0-9]/', '', $record->customer?->phone ?? '');

                    $text = "Hola *{$record->holder_name}*! 👋\n\n";
                    if ($record->destination) {
                        $text .= "Te comparto el detalle de tu viaje a *{$record->destination}* ✈️\n";
                    } else {
                        $text .= "Te comparto el detalle de tu viaje ✈️\n";
                    }

                    if ($record->status === BookingStatus::Borrador || $record->status === BookingStatus::Presupuesto) {
                        $text .= "Total de la cotización: *{$record->currency} ".number_format($record->total_sell, 2)."*\n";
                    } else {
                        $text .= "Tu viaje está confirmado ✅\n";
                    }

                    $text .= "\nPodés ver todo el itinerario, la propuesta y tus vouchers ingresando acá:\n";
                    $text .= $record->publicUrl()."\n\n";
                    $text .= 'Cualquier duda, ¡estamos a tu disposición!';

                    return 'https://wa.me/'.$phone.'?text='.urlencode($text);
                })
                ->openUrlInNewTab(),
            Action::make('send_email')
                ->label('Enviar por Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->form([
                    TextInput::make('email')
                        ->label('Email del cliente')
                        ->email()
                        ->required()
                        ->default(fn (Booking $record): ?string => $record->customer?->email),
                    Radio::make('format')
                        ->label('Formato del documento')
                        ->options([
                            'budget_only' => 'Solo Presupuesto',
                            'full' => 'Presupuesto + Detalle de la Idea de Viaje',
                        ])
                        ->default('budget_only')
                        ->required(),
                ])
                ->action(function (Booking $record, array $data) {
                    $pdfContent = Pdf::loadView('pdf.booking', [
                        'booking' => $record,
                        'format' => $data['format'],
                        'travelPackage' => $record->proposalTravelPackage(),
                    ])->output();

                    Mail::to($data['email'])
                        ->send(new BookingProposalMail($record, $pdfContent));

                    BookingActivity::log($record, 'email_sent', "Presupuesto enviado a {$data['email']}");

                    Notification::make()
                        ->title('Presupuesto enviado por email')
                        ->body("Se envió el PDF a {$data['email']}")
                        ->success()
                        ->send();
                }),
            Action::make('pdf')
                ->label('Presupuesto (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    Radio::make('format')
                        ->label('Formato del documento')
                        ->options([
                            'budget_only' => 'Solo Presupuesto',
                            'full' => 'Presupuesto + Detalle de la Idea de Viaje',
                        ])
                        ->default('budget_only')
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    return response()->streamDownload(function () use ($record, $data) {
                        echo Pdf::loadView('pdf.booking', [
                            'booking' => $record,
                            'format' => $data['format'],
                            'travelPackage' => $record->proposalTravelPackage(),
                        ])->output();
                    }, 'presupuesto-'.$record->file_number.'.pdf');
                }),
            Action::make('contract_pdf')
                ->label('Acuerdo (PDF)')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->visible(fn (Booking $record) => $record->status->isConfirmed())
                ->action(function ($record) {
                    return response()->streamDownload(function () use ($record) {
                        $settings = get_agency_settings();
                        echo Pdf::loadView('pdf.contract', ['booking' => $record, 'settings' => $settings])->output();
                    }, 'acuerdo-'.$record->file_number.'.pdf');
                }),
            DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
    }

    /** @var array<int, Lead|null> */
    private array $whatsAppConversations = [];

    /**
     * The WhatsApp conversation the proposal can be sent to, looked up once per request.
     */
    private function whatsAppConversation(Booking $booking): ?Lead
    {
        if (! array_key_exists($booking->id, $this->whatsAppConversations)) {
            $this->whatsAppConversations[$booking->id] = app(WhatsAppConversationService::class)->conversationForBooking($booking);
        }

        return $this->whatsAppConversations[$booking->id];
    }

    private function whatsAppCaption(Booking $booking): string
    {
        $name = trim((string) ($booking->holder_name ?: $booking->customer?->name));
        $trip = $booking->destination ? " a {$booking->destination}" : '';

        return "¡Hola {$name}! 👋 Te comparto el presupuesto de tu viaje{$trip} ✈️ Cualquier duda, estamos a tu disposición.";
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['lead_id'])) {
            $lead = Lead::find($data['lead_id']);
            if ($lead) {
                $aiData = $lead->ai_data ?? [];
                $originalPassengersInfo = $aiData['pasajeros'] ?? $aiData['passengers'] ?? null;
                if ($originalPassengersInfo) {
                    session()->flash('lead_original_passengers', $originalPassengersInfo);
                }
            }
        }

        return $data;
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->icon('heroicon-o-x-mark');
    }
}
