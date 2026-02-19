<?php

namespace App\Services;

use App\Models\Lead;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiConciergeService
{
    /**
     * Maximum retries for Gemini API calls.
     */
    private const MAX_RETRIES = 2;

    /**
     * Messages to include in context for Gemini.
     */
    private const CONTEXT_MESSAGE_COUNT = 15;

    /**
     * Process a message through the Gemini AI.
     */
    public function processMessage(string $messageContent, Lead $lead): string
    {
        try {
            // Save User Message
            $lead->messages()->create([
                'role' => 'user',
                'content' => $messageContent,
            ]);

            // Dynamic Temperature Logic
            $lowerMsg = strtolower($messageContent);
            if (str_contains($lowerMsg, 'humano') || str_contains($lowerMsg, 'agente') || str_contains($lowerMsg, 'asesor')) {
                $lead->update([
                    'temperature' => \App\Enums\LeadTemperature::Hot,
                    'needs_human_attention' => true,
                ]);
            } elseif ($lead->temperature === \App\Enums\LeadTemperature::Cool && (str_contains($lowerMsg, 'fecha') || str_contains($lowerMsg, 'presupuesto') || str_contains($lowerMsg, 'reserva'))) {
                $lead->update(['temperature' => \App\Enums\LeadTemperature::Warm]);
            }

            // Load History from DB (expanded to 15 messages for deeper context)
            $history = $lead->messages()
                ->orderBy('created_at', 'desc')
                ->take(self::CONTEXT_MESSAGE_COUNT)
                ->get()
                ->reverse();

            // Build simple context from history
            $context = '';
            foreach ($history as $msg) {
                $role = $msg->role === 'user' ? 'Usuario' : 'Asistente';
                $context .= "{$role}: {$msg->content}\n";
            }

            // Include lead data in system prompt for continuity
            $leadContext = '';
            $aiData = $lead->ai_data ?? [];
            if (!empty($aiData)) {
                $leadContext = "\n\nDATOS DEL LEAD:";
                if (!empty($aiData['destino'])) {
                    $leadContext .= "\n- Destino: {$aiData['destino']}";
                }
                if (!empty($aiData['presupuesto'])) {
                    $leadContext .= "\n- Presupuesto: {$aiData['presupuesto']}";
                }
                if (!empty($aiData['pasajeros'])) {
                    $leadContext .= "\n- Pasajeros: {$aiData['pasajeros']}";
                }
            }
            if ($lead->customer_name && $lead->customer_name !== 'Web Guest') {
                $leadContext .= "\n- Nombre: {$lead->customer_name}";
            }

            $systemPrompt = "Eres 'Brisa', la asistente virtual de Luopan Viajes.
            TU OBJETIVO: Recabar información CLAVE de forma RÁPIDA y ESCUETA. No des consejos ni sugieras vuelos/hoteles. Solo pregunta.
            
            DATOS A CONSEGUIR (Uno por uno, no abrumes):
            1. Fecha aprox del viaje: ¿Primera o segunda quincena? ¿Tiene flexibilidad?
            2. Cantidad de noches deseadas.
            3. Ciudad de salida (Solo pregunta desde dónde quieren salir, no ofrezcas transporte).
            4. Destino y Pasajeros (si no lo dijeron).

            CIERRE:
            Una vez que tengas estos datos básicos, CIERRA LA CALIFICACIÓN con este mensaje exacto:
            '¡Perfecto! Ya tengo lo necesario. Nela o Belén se van a comunicar con vos a la brevedad para armarte la propuesta. 😉'

            REGLAS:
            - Sé MUY BREVE. Máximo 1 oración por respuesta.
            - Si preguntan precios o vuelos, di que eso lo arman las chicas (Nela/Belén).
            - No inventes nada.{$leadContext}";

            if (!empty($context)) {
                $systemPrompt .= "\n\nHISTORIAL:\n{$context}";
            }

            $responseText = $this->callGeminiWithRetry("{$systemPrompt}\nUsuario: {$messageContent}");

            // Save Assistant Message
            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $responseText,
            ]);

            return $responseText;
        } catch (Throwable $e) {
            Log::error('AiConciergeService Error: ' . $e->getMessage());

            return 'Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo en unos segundos? 🙏';
        }
    }

    /**
     * Process a message through the Gemini AI with streaming response.
     */
    public function processMessageStream(string $messageContent, Lead $lead): \Generator
    {
        try {
            // Save User Message
            $lead->messages()->create([
                'role' => 'user',
                'content' => $messageContent,
            ]);

            // Dynamic Temperature Logic
            $lowerMsg = strtolower($messageContent);
            if (str_contains($lowerMsg, 'humano') || str_contains($lowerMsg, 'agente') || str_contains($lowerMsg, 'asesor')) {
                $lead->update([
                    'temperature' => \App\Enums\LeadTemperature::Hot,
                    'needs_human_attention' => true,
                ]);
            } elseif ($lead->temperature === \App\Enums\LeadTemperature::Cool && (str_contains($lowerMsg, 'fecha') || str_contains($lowerMsg, 'presupuesto') || str_contains($lowerMsg, 'reserva'))) {
                $lead->update(['temperature' => \App\Enums\LeadTemperature::Warm]);
            }

            // Load History
            $history = $lead->messages()
                ->orderBy('created_at', 'desc')
                ->take(self::CONTEXT_MESSAGE_COUNT)
                ->get()
                ->reverse();

            $context = '';
            foreach ($history as $msg) {
                $role = $msg->role === 'user' ? 'Usuario' : 'Asistente';
                $context .= "{$role}: {$msg->content}\n";
            }

            // Lead Context
            $leadContext = '';
            $aiData = $lead->ai_data ?? [];
            if (!empty($aiData)) {
                $leadContext = "\n\nDATOS DEL LEAD:";
                if (!empty($aiData['destino']))
                    $leadContext .= "\n- Destino: {$aiData['destino']}";
                if (!empty($aiData['presupuesto']))
                    $leadContext .= "\n- Presupuesto: {$aiData['presupuesto']}";
                if (!empty($aiData['pasajeros']))
                    $leadContext .= "\n- Pasajeros: {$aiData['pasajeros']}";
            }
            if ($lead->customer_name && $lead->customer_name !== 'Web Guest') {
                $leadContext .= "\n- Nombre: {$lead->customer_name}";
            }

            $systemPrompt = "Eres 'Brisa', la asistente virtual de Luopan Viajes.
            TU OBJETIVO: Recabar información CLAVE de forma RÁPIDA y ESCUETA. No des consejos ni sugieras vuelos/hoteles. Solo pregunta.
            
            DATOS A CONSEGUIR (Uno por uno, no abrumes):
            1. Fecha aprox del viaje: ¿Primera o segunda quincena? ¿Tiene flexibilidad?
            2. Cantidad de noches deseadas.
            3. Ciudad de salida (Solo pregunta desde dónde quieren salir, no ofrezcas transporte).
            4. Destino y Pasajeros (si no lo dijeron).

            CIERRE:
            Una vez que tengas estos datos básicos, CIERRA LA CALIFICACIÓN con este mensaje exacto:
            '¡Perfecto! Ya tengo lo necesario. Nela o Belén se van a comunicar con vos a la brevedad para armarte la propuesta. 😉'

            REGLAS:
            - Sé MUY BREVE. Máximo 1 oración por respuesta.
            - Si preguntan precios o vuelos, di que eso lo arman las chicas (Nela/Belén).
            - No inventes nada.{$leadContext}";

            if (!empty($context)) {
                $systemPrompt .= "\n\nHISTORIAL:\n{$context}";
            }

            $fullResponse = '';
            $stream = Gemini::generativeModel('models/gemini-flash-latest')->generateContentStream("{$systemPrompt}\nUsuario: {$messageContent}");

            foreach ($stream as $response) {
                $chunk = $response->text();
                $fullResponse .= $chunk;
                yield $chunk;
            }

            // Save Assistant Message
            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);

        } catch (Throwable $e) {
            Log::error('AiConciergeService Stream Error: ' . $e->getMessage());
            yield 'Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo? 🙏';
        }
    }

    /**
     * Extract structured data from a message using AI.
     */
    public function extractLeadData(string $message): array
    {
        try {
            $prompt = "Analiza el historial y extrae JSON estricto:
            - 'destino': Lugar mencionado.
            - 'presupuesto': Monto mencionado.
            - 'pasajeros': Cantidad.
            - 'nombre': Nombre del usuario si se presentó (ej: 'Hola soy Juan' → 'Juan').
            - 'resumen': Resumen corto INCLUYENDO: fechas/quincena, noches y ciudad de salida si están.
            - 'requiere_atencion': true si pide humano o parece molesto.

            Texto: \"{$message}\"";

            $text = $this->callGeminiWithRetry($prompt);
            $text = str_replace(['```json', '```'], '', $text);

            return json_decode($text, true) ?? [];
        } catch (Throwable $e) {
            Log::error('AiExtraction Error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Call Gemini API with retry logic and exponential backoff.
     */
    private function callGeminiWithRetry(string $prompt): string
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                if ($attempt > 0) {
                    usleep($attempt * 500_000); // 0.5s, 1s backoff
                }

                $result = Gemini::generativeModel('models/gemini-flash-latest')->generateContent($prompt);

                return $result->text();
            } catch (Throwable $e) {
                $lastException = $e;
                Log::warning("Gemini API attempt {$attempt} failed: " . $e->getMessage());
            }
        }

        throw $lastException;
    }
}
