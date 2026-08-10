<x-mail::message>
# ¡Hola {{ $booking->holder_name }}!

Te enviamos el presupuesto de tu viaje{{ $booking->destination ? ' a **'.$booking->destination.'**' : '' }}.

@if($booking->total_sell)
**Total: {{ $booking->currency }} {{ number_format($booking->total_sell, 2) }}**
@endif

@if($booking->valid_until)
> Este presupuesto es válido hasta el **{{ $booking->valid_until->format('d/m/Y') }}**.
@endif

Encontrás el PDF adjunto en este correo. También podés ver todos los detalles online:

<x-mail::button :url="$booking->publicUrl()">
Ver Presupuesto Online
</x-mail::button>

¿Tenés alguna consulta? Respondé este email o escribinos por WhatsApp.

Gracias por elegirnos,<br>
El equipo de {{ $settings?->name ?? 'OmniAgent' }}
</x-mail::message>
