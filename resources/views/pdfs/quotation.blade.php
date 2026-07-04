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
            margin-top: 50px;
            text-align: justify;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 15px;
            line-height: 1.4;
        }
    </style>
</head>

<body>

    <div class="header">
        @php
            $settings = get_agency_settings();
            $logoPath = get_agency_logotipo_path();
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
        <p>La presente cotización constituye únicamente una propuesta comercial y no implica reserva, bloqueo de lugares, emisión de servicios ni contratación definitiva.</p>
        <p>Las tarifas, disponibilidad, impuestos, percepciones, tipos de cambio, horarios, itinerarios y demás condiciones corresponden a la información disponible al momento de su emisión y podrán ser modificados por los respectivos prestadores o por circunstancias ajenas al control razonable de {{ $settings?->company_name ?? config('app.name', 'la agencia') }}, hasta la confirmación y emisión de los servicios.</p>
        <p>Los precios informados están sujetos a disponibilidad al momento de la reserva efectiva. La presente cotización no garantiza cupos, plazas, habitaciones ni servicios.</p>
        <p>La contratación quedará perfeccionada únicamente con la aceptación del pasajero, el pago correspondiente, la confirmación de disponibilidad por los prestadores y la emisión de la documentación de viaje.</p>
        <p>Cuando la cotización se encuentre expresada en moneda extranjera o dependa de tarifas internacionales, su conversión a pesos, así como impuestos y percepciones aplicables, se determinarán conforme a la normativa vigente al momento del pago, salvo indicación expresa en contrario.</p>
        <p>Salvo que se indique expresamente, la asistencia al viajero no se encuentra incluida en esta cotización. Se recomienda su contratación, así como verificar con anticipación la documentación personal, requisitos migratorios, sanitarios y demás exigencias aplicables al viaje.</p>
        <p>{{ $settings?->company_name ?? config('app.name', 'La agencia') }} actúa como agencia intermediaria, gestionando servicios prestados por terceros independientes, sin perjuicio de las obligaciones que le impone la legislación vigente.</p>
        <p>La contratación definitiva se regirá por las Condiciones Generales de Contratación de {{ $settings?->company_name ?? config('app.name', 'la agencia') }}, las condiciones particulares de los prestadores intervinientes y la normativa aplicable, incluyendo la Ley N.º 18.829, la Ley N.º 24.240, el Código Civil y Comercial de la Nación y demás disposiciones vigentes. Los errores materiales o tipográficos podrán ser rectificados antes de la confirmación definitiva de la reserva.</p>
    </div>

</body>

</html>