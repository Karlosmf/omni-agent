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
    public function processMessage(string $message, array $history = []): string
    {
        try {
            // Build simple context from history
            $context = "";
            foreach ($history as $msg) {
                $role = $msg['role'] === 'user' ? 'Usuario' : 'Asistente';
                $context .= "{$role}: {$msg['content']}\n";
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

            $result = Gemini::generativeModel('models/gemini-flash-latest')->generateContent("{$systemPrompt}\nUsuario: {$message}");

            return $result->text();
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
