<?php

namespace App\Filament\Admin\Resources\Leads\Actions;

use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Closes an inquiry as lost and asks why, so the reasons can be measured later.
 */
class MarkAsLostAction
{
    public static function make(string $name = 'mark_lost'): Action
    {
        return Action::make($name)
            ->label('Marcar como perdida')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Marcar la consulta como perdida')
            ->modalSubmitActionLabel('Marcar como perdida')
            ->schema(self::schema())
            ->visible(fn (Lead $record): bool => self::canBeLost($record))
            ->action(function (Lead $record, array $data): void {
                $record->markAsLost(self::reason($data['lost_reason']), $data['lost_note'] ?? null);

                Notification::make()
                    ->title($record->displayName().' quedó como perdida')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, Select|Textarea>
     */
    public static function schema(): array
    {
        return [
            Select::make('lost_reason')
                ->label('Motivo')
                ->options(LostReason::class)
                ->required()
                ->live(),
            Textarea::make('lost_note')
                ->label('Detalle')
                ->maxLength(500)
                ->rows(2)
                ->required(fn (Get $get): bool => $get('lost_reason') === LostReason::Other->value || $get('lost_reason') === LostReason::Other),
        ];
    }

    /**
     * The reason picked in the form, which may come as the enum or as its value.
     */
    public static function reason(LostReason|string $value): LostReason
    {
        return $value instanceof LostReason ? $value : LostReason::from($value);
    }

    /**
     * Sold inquiries and the ones already lost cannot be marked lost.
     */
    public static function canBeLost(Lead $lead): bool
    {
        return ! ($lead->status === LeadStatus::Closed && $lead->lost_reason !== null)
            && $lead->status !== LeadStatus::Booked
            && ! $lead->hasConfirmedBooking();
    }
}
