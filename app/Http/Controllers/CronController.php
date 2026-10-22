<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Para hostings sin cron: un servicio externo gratuito llama a esta dirección cada minuto
 * y se ejecuta lo mismo que haría «php artisan schedule:run» (incluida la cola).
 */
class CronController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = config('omni-hosting.cron_token');

        // Sin token configurado la ruta no existe; con token equivocado, prohibido.
        abort_if(blank($expected), 404);
        abort_unless(hash_equals((string) $expected, (string) ($request->query('token') ?? $request->bearerToken())), 403);

        @set_time_limit(120);
        Artisan::call('schedule:run');

        return response()->json(['ok' => true, 'ran_at' => now()->toIso8601String()]);
    }
}
