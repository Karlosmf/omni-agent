<?php

namespace App\Traits;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

trait GeneratesItineraryWithAi
{
    /**
     * Returns the shared form schema used inside the AI itinerary generation Action.
     *
     * @return array<int, Component>
     */
    protected static function itineraryAiForm(): array
    {
        return [
            Textarea::make('prompt')
                ->label('Instrucción para la IA')
                ->placeholder('Ej: Generame 7 días en Roma visitando lo más importante.')
                ->required(),
        ];
    }

    /**
     * Send a success notification after a successful AI generation.
     */
    protected static function notifyItinerarySuccess(): void
    {
        Notification::make()
            ->title('Itinerario generado con éxito')
            ->success()
            ->send();
    }

    /**
     * Send a danger notification when AI generation fails.
     */
    protected static function notifyItineraryFailure(): void
    {
        Notification::make()
            ->title('Error al generar. Intenta de nuevo.')
            ->danger()
            ->send();
    }
}
