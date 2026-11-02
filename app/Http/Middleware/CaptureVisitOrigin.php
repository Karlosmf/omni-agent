<?php

namespace App\Http\Middleware;

use App\Support\VisitOrigin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recuerda en la sesión de qué red llegó la visita, para saludar y atribuir el lead.
 */
class CaptureVisitOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession() && ! $request->is('admin*', 'livewire*', 'webhooks/*', 'cron/*', 'api/*')) {
            VisitOrigin::capture($request);
        }

        return $next($request);
    }
}
