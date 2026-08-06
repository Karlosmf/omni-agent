<x-mail::message>
# ¡Hola {{ $lead->customer->name }}!

Recibimos tu consulta con éxito. Un agente de **{{ $settings['name'] ?? 'OmniAgent' }}** está revisando los detalles y pronto se pondrá en contacto con vos.

Si nos dejaste tu WhatsApp, te escribiremos por allí para enviarte la propuesta más rápida.

<x-mail::button :url="config('app.url')">
Visitar nuestro sitio web
</x-mail::button>

Gracias por elegirnos,<br>
El equipo de {{ $settings['name'] ?? 'OmniAgent' }}
</x-mail::message>
