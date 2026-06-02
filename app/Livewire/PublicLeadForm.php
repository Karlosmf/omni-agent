<?php

namespace App\Livewire;

use App\Models\Lead;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
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
            ])
            ->statePath('data');
    }

    public function submit(\App\Services\AiConciergeService $aiService, \App\Actions\Leads\CaptureLeadAction $captureLeadAction)
    {
        $data = $this->form->getState();

        $lead = $captureLeadAction->execute([
            'customer_name'  => $data['name'],
            'customer_phone' => $data['phone'] ?? 'Web-Form',
            'customer_email' => $data['email'],
            'source'         => 'web_form',
            'raw_message'    => $data['message'],
            'ai_data'        => ['email' => $data['email']],
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
            \Illuminate\Support\Facades\Log::error('Error processing form with AI: '.$e->getMessage());
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
