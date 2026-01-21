<?php

namespace App\Livewire;

use App\Models\Lead;
use Livewire\Component;

class PublicLeadForm extends Component
{
    public $name = '';

    public $email = '';

    public $phone = '';

    public $message = '';

    public $success = false;

    public function submit(\App\Services\AiConciergeService $aiService)
    {
        $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email',
            'phone' => 'nullable|numeric',
            'message' => 'required|min:10',
        ]);

        $lead = Lead::create([
            'customer_name' => $this->name,
            'customer_phone' => $this->phone ?? 'Web-Form',
            'source' => 'web_form',
            'raw_message' => $this->message,
            'status' => \App\Enums\LeadStatus::New ,
            'temperature' => \App\Enums\LeadTemperature::Cool,
            'ai_data' => [
                'email' => $this->email,
            ],
        ]);

        // Process message with AI to get a summary/intent
        // Process message with AI to get a summary and structured data
        try {
            $extraction = $aiService->extractLeadData("El usuario {$this->name} escribió: {$this->message}");

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
            \Illuminate\Support\Facades\Log::error('Error processing form with AI: ' . $e->getMessage());
        }

        $this->reset(['name', 'email', 'phone', 'message']);
        $this->success = true;

        $this->dispatch('lead-submitted');
    }

    public function render()
    {
        return view('livewire.public-lead-form');
    }
}
