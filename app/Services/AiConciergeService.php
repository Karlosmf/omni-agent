<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiConciergeService
{
    /**
     * Process a message through the Gemini AI.
     */
    /**
     * Process a message through the Gemini AI.
     */
    public function processMessage(string $messageContent, \App\Models\Lead $lead): string
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

            // Load History from DB
            $history = $lead->messages()
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();

            // Build simple context from history
            $context = '';
            foreach ($history as $msg) {
                $role = $msg->role === 'user' ? 'Usuario' : 'Asistente';
                $context .= "{$role}: {$msg->content}\n";
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
            - No inventes nada.";

            if (!empty($context)) {
                $systemPrompt .= "\n\nHISTORIAL:\n{$context}";
            }

            $result = Gemini::generativeModel('models/gemini-flash-latest')->generateContent("{$systemPrompt}\nUsuario: {$messageContent}");
            $responseText = $result->text();

            // Save Assistant Message
            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $responseText,
            ]);

            return $responseText;
        } catch (Throwable $e) {
            Log::error('AiConciergeService Error: ' . $e->getMessage());

            return 'Lo siento, no puedo procesar tu solicitud en este momento.';
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
            - 'resumen': Resumen corto INCLUYENDO: fechas/quincena, noches y ciudad de salida si están.
            - 'requiere_atencion': true si pide humano o parece molesto.

            Texto: \"{$message}\"";

            $result = Gemini::generativeModel('models/gemini-flash-latest')->generateContent($prompt);
            $text = str_replace(['```json', '```'], '', $result->text());

            return json_decode($text, true) ?? [];
        } catch (Throwable $e) {
            Log::error('AiExtraction Error: ' . $e->getMessage());

            return [];
        }
    }
}
