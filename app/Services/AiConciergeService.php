<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiConciergeService
{
    /**
     * Parse message and return structured data using Gemini API.
     */
    public function parseMessage(string $message): array
    {
        $apiKey = config('services.gemini.api_key');

        if (! $apiKey) {
            Log::warning('GEMINI_API_KEY is missing in config/services.php');

            return $this->getMockData($message);
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.$apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $this->buildPrompt($message)],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            Log::error('Gemini API Error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Gemini Connection Error: '.$e->getMessage());
        }

        return $this->getMockData($message);
    }

    private function buildPrompt(string $message): string
    {
        return <<<EOT
        Act as a travel agency AI assistant. Analyze the following message from a potential client:
        "{$message}"

        Extract the following information in JSON format:
        - destination (string, guess if implied, or "Unknown")
        - budget (integer, estimated total in USD, or 0 if not mentioned)
        - pax (integer, number of people, default to 1)
        - dates (string, preferred travel dates or "TBD")
        - summary (string, a brief professional summary of the request in Spanish)

        Return ONLY the JSON.
        EOT;
    }

    private function getMockData(string $message): array
    {
        return [
            'destination' => 'Unknown',
            'budget' => 0,
            'pax' => 1,
            'dates' => 'TBD',
            'summary' => 'Initial inquiry: '.substr($message, 0, 50).'...',
        ];
    }

    /**
     * Classify lead temperature based on data.
     */
    public function classifyTemperature(array $data): LeadTemperature
    {
        if (($data['budget'] ?? 0) > 3000) {
            return LeadTemperature::Hot;
        }

        if (($data['budget'] ?? 0) > 1000) {
            return LeadTemperature::Warm;
        }

        return LeadTemperature::Cool;
    }

    /**
     * Create a basic Lead in the database.
     */
    public function createLead(string $name, string $phone, string $message, string $source = 'whatsapp'): Lead
    {
        return Lead::create([
            'customer_name' => $name,
            'customer_phone' => $phone,
            'source' => $source,
            'raw_message' => $message,
            'status' => LeadStatus::New,
            'temperature' => LeadTemperature::Cool, // Default until analysis
            'needs_human_attention' => false,
            'ai_data' => [], // Empty initially
        ]);
    }

    /**
     * Analyze an existing Lead using Gemini and update it.
     */
    public function analyzeLead(Lead $lead): void
    {
        $aiData = $this->parseMessage($lead->raw_message);
        $temperature = $this->classifyTemperature($aiData);

        $lead->update([
            'ai_data' => $aiData,
            'ai_summary' => $aiData['summary'] ?? null,
            'temperature' => $temperature,
        ]);
    }

    /**
     * Process an incoming message and create a Lead (Sync version).
     */
    public function processIncomingMessage(string $name, string $phone, string $message, string $source = 'whatsapp'): Lead
    {
        $lead = $this->createLead($name, $phone, $message, $source);
        $this->analyzeLead($lead);

        return $lead;
    }
}
