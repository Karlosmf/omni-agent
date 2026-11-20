<?php

namespace App\Filament\Admin\Schemas;

use App\Enums\AiProvider;
use App\Models\AgencySetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/**
 * The AI provider fields, shared by the agency settings and the bot panel. Both edit the same single provider and key.
 * The saved key never reaches the browser: the field starts empty, shows the last characters, and leaving it empty keeps the key.
 */
class AiProviderFields
{
    /**
     * @return list<Component|TextInput|ToggleButtons>
     */
    public static function make(): array
    {
        return [
            ToggleButtons::make('ai_provider')
                ->label('Proveedor de IA')
                ->options(AiProvider::class)
                ->default(AiProvider::None)
                ->inline()
                ->live()
                ->columnSpanFull(),
            TextInput::make('ai_api_key')
                ->label('API Key de IA')
                ->placeholder(fn (Get $get): string => self::provider($get)->getApiKeyPlaceholder())
                ->helperText(function (Get $get): string {
                    $saved = AgencySetting::first()?->maskedAiApiKey();

                    return trim(self::provider($get)->getHelperText().($saved ? " Guardada: {$saved}. Dejala vacía para no cambiarla." : ''));
                })
                ->password()
                ->autocomplete('new-password')
                ->visible(fn (Get $get): bool => self::provider($get) !== AiProvider::None)
                ->columnSpanFull(),
            TextInput::make('ai_base_url')
                ->label('URL del proveedor')
                ->placeholder('https://api.proveedor.com/v1')
                ->url()
                ->required(fn (Get $get): bool => self::provider($get) === AiProvider::OpenAiCompatible)
                ->visible(fn (Get $get): bool => self::provider($get) === AiProvider::OpenAiCompatible)
                ->columnSpanFull(),
            TextInput::make('ai_model')
                ->label('Modelo')
                ->placeholder(fn (Get $get): string => self::provider($get)->getDefaultModel())
                ->helperText('Dejalo vacío para usar el modelo recomendado. Cambialo si el proveedor retira ese modelo.')
                ->required(fn (Get $get): bool => self::provider($get) === AiProvider::OpenAiCompatible)
                ->visible(fn (Get $get): bool => self::provider($get) !== AiProvider::None)
                ->columnSpanFull(),
        ];
    }

    private static function provider(Get $get): AiProvider
    {
        $value = $get('ai_provider');

        return $value instanceof AiProvider ? $value : (AiProvider::tryFrom((string) $value) ?? AiProvider::None);
    }
}
