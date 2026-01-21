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
    public function processMessage(string $message, array $history = []): string
    {
        try {
            $result = Gemini::generativeModel('models/gemini-flash-latest')->generateContent($message);

            return $result->text();
        } catch (Throwable $e) {
            Log::error('AiConciergeService Error: '.$e->getMessage());

            return 'Lo siento, no puedo procesar tu solicitud en este momento. Por favor verifica los logs o la configuración de API Key.';
        }
    }
}
