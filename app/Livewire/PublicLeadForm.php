<?php

namespace App\Livewire;

use App\Actions\Leads\CaptureLeadAction;
use App\Jobs\ProcessLeadExtraction;
use App\Support\VisitOrigin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class PublicLeadForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public $success = false;

    /** Honeypot: real visitors never see or fill this field. */
    public string $website = '';

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
                    ->regex('/^\+?[0-9\s\-()]{7,20}$/')
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

    public function submit(CaptureLeadAction $captureLeadAction)
    {
        if ($this->website !== '') {
            $this->success = true;

            return;
        }

        $data = $this->form->getState();

        $lead = $captureLeadAction->execute([
            ...VisitOrigin::forLead(),
            'customer_name' => $data['name'],
            'customer_phone' => $data['phone'] ?? null,
            'customer_email' => $data['email'],
            'source' => 'web_form',
            'raw_message' => $data['message'],
            'ai_data' => ['email' => $data['email']],
        ]);

        // The AI enrichment runs on the queue so the visitor does not wait for it.
        ProcessLeadExtraction::dispatch($lead, "El usuario {$data['name']} escribió: {$data['message']}");

        $this->form->fill();
        $this->success = true;

        $this->dispatch('lead-submitted');
    }

    public function render()
    {
        return view('livewire.public-lead-form');
    }
}
