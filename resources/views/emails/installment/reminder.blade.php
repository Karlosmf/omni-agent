<x-mail::message>
# Recordatorio de pago

{{ $body }}

@if ($instructions)
**Cómo pagar**

{{ $instructions }}
@endif

<x-mail::button :url="$paymentUrl">
Ver mi plan de pagos
</x-mail::button>

Gracias,<br>
{{ $agency }}
</x-mail::message>
