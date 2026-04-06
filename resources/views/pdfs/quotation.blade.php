<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Presupuesto {{ $record->quotation_number }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #333;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #eab308;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo-img {
            max-height: 50px;
        }

        .company-info {
            float: right;
            text-align: right;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 14px;
            color: #666;
        }

        .details-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .details-table th,
        .details-table td {
            padding: 8px;
            text-align: left;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .services-table th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }

        .services-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .total-section {
            float: right;
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #eab308;
            border-top: 2px solid #eab308;
            margin-top: 10px;
            padding-top: 10px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header">
        @php
            $settings = get_agency_settings();
            $logoPath = get_agency_logo_path();
        @endphp
        <div class="company-info">
            <strong>{{ $settings?->company_name ?? config('app.name', 'nuestra agencia de viajes') }}</strong><br>
            @if($settings?->address)
                {!! nl2br(e($settings->address)) !!}<br>
            @endif
            @if($settings?->contact_email)
                Email: {{ $settings->contact_email }}
            @endif
        </div>
        <div>
            @if($logoPath)
                <img src="{{ $logoPath }}" class="logo-img"><br>
            @endif
            <div class="title">PRESUPUESTO</div>
            <div class="subtitle">#{{ $record->quotation_number }}</div>
            <div class="subtitle">Válido hasta: {{ $record->valid_until?->format('d/m/Y') }}</div>
        </div>
    </div>

    <table class="details-table">
        <tr>
            <td width="15%"><strong>Cliente:</strong></td>
            <td width="35%">{{ $record->customer->name ?? 'Consumidor Final' }}</td>
            <td width="15%"><strong>Fechas:</strong></td>
            <td width="35%">{{ $record->travel_date?->format('d/m/Y') }} @if($record->nights) ({{ $record->nights }}
            noches) @endif</td>
        </tr>
        <tr>
            <td><strong>Destino:</strong></td>
            <td>{{ $record->destination }}</td>
            <td><strong>Pasajeros:</strong></td>
            <td>{{ $record->passengers }}</td>
        </tr>
    </table>

    <table class="services-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th width="20%" style="text-align: right;">Precio</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($record->items))
                @foreach($record->items as $item)
                    <tr>
                        <td>{{ $item['description'] ?? '-' }}</td>
                        <td style="text-align: right;">USD {{ number_format($item['sell'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="2" style="text-align: center; color: #999;">Sin servicios detallados</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row total-final">
            <span>TOTAL</span>
            <span>USD {{ number_format($record->total_sell, 2) }}</span>
        </div>
        <div style="font-size: 12px; color: #666; margin-top: 10px; text-align: right;">
            * Precios sujetos a disponibilidad y cambios hasta confirmar la reserva.
        </div>
    </div>

    <div class="footer">
        Gracias por confiar en {{ $settings?->company_name ?? config('app.name', 'nosotros') }} para tu próximo viaje.
    </div>

</body>

</html>