<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiConciergeService
{
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    /**
     * Send a message to Gemini with conversation history and get a structured response.
     */
    public function sendMessage(string $history, string $newMessage): array
    {
        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            Log::warning('GEMINI_API_KEY is missing in config/services.php');

            return $this->getMockData($newMessage);
        }

        try {
            $prompt = $this->buildSystemPrompt($history, $newMessage);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'response_mime_type' => 'application/json',
                            'temperature' => 0.7,
                        ],
                    ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->sanitizeResponse($data);
                }
            }

            Log::error('Gemini API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Gemini Connection Error: ' . $e->getMessage());
        }

        return $this->getMockData($newMessage);
    }

    private function buildSystemPrompt(string $history, string $newMessage): string
    {
        return <<<EOT
        You are "Luopan", an expert travel concierge for "Luopan Viajes y Turismo".
        Your goal is to assist users, capture lead information, and answer questions about travel.

        **Persona & Tone:**
        - Professional, friendly, and helpful.
        - Respond ONLY in Spanish (Rioplatense variant natural, but professional).
        - Be concise but complete.
        - NEVER invent prices or flight availability. If asked, say you will check with a human agent.

        **Task:**
        1. Analyze the conversation history and the new message.
        2. Identify the user's INTENT (booking_inquiry, general_question, complaint, other).
        3. Extract LEAD DATA if present (destination, dates, pax, budget).
        4. Generate a helpful text REPLY to the user.

        **History:**
        {$history}

        **New Message:**
        "{$newMessage}"

        **Output Format (JSON ONLY):**
        {
            "intent": "string (booking_inquiry|general_question|complaint|other)",
            "lead_data": {
                "destination": "string or null",
                "dates": "string or null",
                "pax": "integer or null",
                "budget": "integer or null (USD)"
            },
            "reply": "string (The text response for the user)"
        }
        EOT;
    }

    private function sanitizeResponse(array $data): array
    {
        // Ensure default structure
        return [
            'intent' => $data['intent'] ?? 'other',
            'lead_data' => [
                'destination' => $data['lead_data']['destination'] ?? null,
                'dates' => $data['lead_data']['dates'] ?? null,
                'pax' => $data['lead_data']['pax'] ?? null,
                'budget' => $data['lead_data']['budget'] ?? null,
            ],
            'reply' => $data['reply'] ?? 'Lo siento, tuve un problema procesando tu mensaje.',
        ];
    }

    private function getMockData(string $message): array
    {
        return [
            'intent' => 'other',
            'lead_data' => [
                'destination' => null,
                'dates' => null,
                'pax' => 1,
                'budget' => 0,
            ],
            'reply' => 'Hola, soy Luopan (Modo Demo). Recibí tu mensaje: "' . $message . '". Por favor configura mi API Key para que pueda responderte realmente.',
        ];
    }

    /**
     * Process an incoming message, update the Lead, and return the AI response.
     */
    public function processIncomingMessage(Lead $lead, string $message): string
    {
        $history = "User previously said: \"{$lead->raw_message}\"";

        // 2. Call AI
        $response = $this->sendMessage($history, $message);

        // 3. Update Lead Data
        $currentAiData = $lead->ai_data ?? [];
        $newLeadData = array_merge($currentAiData, $response['lead_data']);

        // 4. Determine Temperature
        $temperature = $this->classifyTemperature($newLeadData);

        // 5. Update Lead
        $lead->update([
            'ai_data' => $newLeadData,
            'ai_summary' => "Intent: {$response['intent']}. Last Dest: " . ($newLeadData['destination'] ?? '?'),
            'temperature' => $temperature,
        ]);

        return $response['reply'];
    }

    public function classifyTemperature(array $data): LeadTemperature
    {
        $budget = $data['budget'] ?? 0;
        if ($budget > 3000)
            return LeadTemperature::Hot;
        if ($budget > 1000)
            return LeadTemperature::Warm;
        return LeadTemperature::Cool;
    }

    // Updated createLead with Safe Defaults and Try/Catch
    public function createLead(string $name, string $phone, string $message, string $source = 'whatsapp'): Lead
    {
        $lead = Lead::create([
            'customer_name' => $name,
            'customer_phone' => $phone,
            'source' => $source,
            'raw_message' => $message,
            'status' => LeadStatus::New , // Explicit default
            'temperature' => LeadTemperature::Cool, // Explicit default
            'needs_human_attention' => false,
            'ai_data' => [],
        ]);

        try {
            $this->processIncomingMessage($lead, $message);
        } catch (\Exception $e) {
            Log::error('AI Analysis failed on create: ' . $e->getMessage());
        }

        return $lead;
    }
}
