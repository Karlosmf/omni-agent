<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - #{{ $transaction->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #333;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 200px;
        }

        .company-info {
            text-align: right;
            float: right;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .details {
            margin-bottom: 20px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th,
        .details-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
        }

        .notes {
            margin-top: 30px;
            font-style: italic;
            color: #666;
            width: 100%;
            border: 1px dashed #ccc;
            padding: 10px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="company-info">
            @php
                $settings = get_agency_settings();
                $whatsappLinks = collect($settings?->social_links ?? [])
                    ->filter(fn($link) => 
                        str_contains(strtolower($link['platform'] ?? ''), 'whatsapp') || 
                        str_contains(strtolower($link['icon'] ?? ''), 'whatsapp')
                    );
            @endphp
            <strong>{{ $settings?->company_name ?? config('app.name', 'nuestra agencia de viajes') }}</strong><br>
            @if($settings?->address)
                {!! nl2br(e($settings->address)) !!}<br>
            @endif

            @if($whatsappLinks->isNotEmpty())
                @foreach($whatsappLinks as $link)
                    @php
                        $platformName = $link['platform'] ?? 'WhatsApp';
                        $displayName = str_ireplace('WhatsApp', '', $platformName);
                        $displayName = trim($displayName) ?: 'WhatsApp';
                    @endphp
                    {{ $displayName }}: {{ $link['url'] }}<br>
                @endforeach
            @elseif($settings?->contact_phone)
                Tel: {{ $settings->contact_phone }}<br>
            @endif
        </div>
        @php
            $logoPath = get_agency_logo_path();
        @endphp
        <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" class="logo">
    </div>

    <div class="title">RECIBO DE PAGO</div>
    <div>Fecha: {{ $transaction->created_at->format('d/m/Y H:i') }}</div>
    <div>Nro. Transacción: #{{ $transaction->id }}</div>

    @if($transaction->booking)
        <div class="mb-4">
            <p><strong>Expediente:</strong> {{ $transaction->booking->file_number }}</p>
            @if($transaction->booking->client)
                <p><strong>Cliente:</strong> {{ $transaction->booking->client->name }}</p>
            @endif
            <p><strong>Destino:</strong> {{ $transaction->booking->destination }}</p>
            <p><strong>Fecha Salida:</strong>
                {{ $transaction->booking->start_date ? \Carbon\Carbon::parse($transaction->booking->start_date)->format('d/m/Y') : '-' }}
            </p>
        </div>
    @endif

    @if($transaction->payer_name)
        <div class="mb-4">
            <p><strong>Pagante / Beneficiario:</strong> {{ $transaction->payer_name }}</p>
        </div>
    @endif

    <table class="details-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transaction->notes ?? ($transaction->booking ? 'Pago a cuenta por expediente ' . $transaction->booking->file_number : 'Pago a cuenta') }}
                </td>
                <td style="text-align: right;">
                    {{ $transaction->currency == \App\Enums\Currency::USD ? 'USD' : 'ARS' }}
                    {{ number_format($transaction->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        Total Pagado: {{ $transaction->currency == \App\Enums\Currency::USD ? 'USD' : 'ARS' }}
        {{ number_format($transaction->amount, 2) }}
    </div>

    <div class="notes">
        <strong>Método de Pago:</strong> {{ $transaction->method }}<br>
        @if($transaction->exchange_rate > 1)
            <strong>Tipo de Cambio Ref:</strong> {{ number_format($transaction->exchange_rate, 2) }}<br>
        @endif
        @if($transaction->amount_usd_fixed > 0 && $transaction->currency != \App\Enums\Currency::USD)
            <strong>Equivalente USD:</strong> USD {{ number_format($transaction->amount_usd_fixed, 2) }}
        @endif
    </div>

    <div class="footer">
        Gracias por confiar en {{ $settings?->company_name ?? config('app.name', 'nuestra agencia') }}. <br>
        Este documento es un comprobante de pago no fiscal.
    </div>

</body>

</html>