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
            <strong>Luopan Viajes & Turismo</strong><br>
            Belgrano 843, Local A<br>
            Reconquista, Santa Fe 3560<br>
            +5493482300052
        </div>
        <img src="{{ public_path('images/branding/logo-full.png') }}" alt="Luopan Logo" class="logo">
    </div>

    <div class="title">RECIBO DE PAGO</div>
    <div>Fecha: {{ $transaction->created_at->format('d/m/Y H:i') }}</div>
    <div>Nro. Transacción: #{{ $transaction->id }}</div>

    <div class="details">
        <h3>Detalles del Cliente</h3>
        <p>
            <strong>Cliente:</strong> {{ $transaction->booking->lead->name }}
            {{ $transaction->booking->lead->last_name }}<br>
            <strong>Expediente:</strong> {{ $transaction->booking->file_number }}
        </p>
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transaction->notes ?? 'Pago a cuenta por expediente ' . $transaction->booking->file_number }}
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
        Gracias por confiar en Luopan Viajes & Turismo. <br>
        Este documento es un comprobante de pago no fiscal.
    </div>

</body>

</html>