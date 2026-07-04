<?php

namespace App\Livewire;

use App\Actions\Leads\CaptureLeadAction;
use App\Services\AiConciergeService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class PublicLeadForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public $success = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->minLength(3)
                    ->placeholder('Tu nombre'),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->placeholder('tu@email.com'),
                TextInput::make('phone')
                    ->label('Teléfono (Opcional)')
                    ->tel()
                    ->numeric()
                    ->placeholder('+54 9 ...'),
                Textarea::make('message')
                    ->label('Mensaje')
                    ->required()
                    ->minLength(10)
                    ->rows(3)
                    ->placeholder('Contanos qué estás buscando...'),
                Checkbox::make('accept_policies')
                    ->label(fn () => new HtmlString('Acepto las <a href="'.route('pages.privacidad').'" target="_blank" class="text-amber-600 hover:underline font-medium">Políticas de Privacidad</a>'))
                    ->accepted()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(AiConciergeService $aiService, CaptureLeadAction $captureLeadAction)
    {
        $data = $this->form->getState();

        $lead = $captureLeadAction->execute([
            'customer_name' => $data['name'],
            'customer_phone' => $data['phone'] ?? 'Web-Form',
            'customer_email' => $data['email'],
            'source' => 'web_form',
            'raw_message' => $data['message'],
            'ai_data' => ['email' => $data['email']],
        ]);

        // Process message with AI to get a summary/intent
        // Process message with AI to get a summary and structured data
        try {
            $extraction = $aiService->extractLeadData("El usuario {$data['name']} escribió: {$data['message']}");

            // Merge existing ai_data (email) with extracted data
            $currentAiData = $lead->ai_data ?? [];
            $newAiData = array_merge($currentAiData, [
                'destino' => $extraction['destino'] ?? null,
                'presupuesto' => $extraction['presupuesto'] ?? null,
                'pasajeros' => $extraction['pasajeros'] ?? 1,
            ]);

            $lead->update([
                'ai_data' => $newAiData,
                'ai_summary' => $extraction['resumen'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error processing form with AI: '.$e->getMessage());
        }

        $this->form->fill();
        $this->success = true;

        $this->dispatch('lead-submitted');
    }

    public function render()
    {
        return view('livewire.public-lead-form');
    }
}
