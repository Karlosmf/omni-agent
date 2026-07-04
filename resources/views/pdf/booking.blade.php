<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Expediente de Viaje #{{ $booking->file_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #075E54;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .logo-img {
            max-height: 50px;
        }

        .company-info {
            float: right;
            text-align: right;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .section {
            margin-bottom: 20px;
        }

        .grid {
            width: 100%;
            margin-bottom: 15px;
        }

        .grid td {
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #555;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.items th,
        table.items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table.items th {
            background-color: #f2f2f2;
            color: #333;
        }

        .total-row td {
            font-weight: bold;
            background-color: #eee;
        }

        table.items th.right-align,
        table.items td.right-align,
        .right-align {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            font-size: 8px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            text-align: justify;
            line-height: 1.3;
        }
    </style>
</head>

<body>

    <div class="header">
        @php
            $settings = get_agency_settings();
            $logoPath = get_agency_logotipo_path();
        @endphp
        <table width="100%">
            <tr>
                <td class="logo"><img src="{{ $logoPath }}" class="logo-img"></td>
                <td class="company-info">
                    <strong>{{ $settings?->company_name ?? config('app.name', 'nuestra agencia de viajes') }}</strong><br>
                    @if($settings?->address)
                        {!! nl2br(e($settings->address)) !!}<br>
                    @endif
                    @if($settings?->contact_email)
                        {{ $settings->contact_email }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="title">Detalle del Expediente</div>
        <table class="grid">
            <tr>
                <td width="50%">
                    <span class="label">Nro Expediente:</span> {{ $booking->file_number }}<br>
                    <span class="label">Fecha Emisión:</span> {{ now()->format('d/m/Y') }}<br>
                    <span class="label">Estado:</span> {{ $booking->status->getLabel() }}
                </td>
                <td width="50%">
                    <span class="label">Titular:</span> {{ $booking->holder_name }}<br>
                    <span class="label">Fecha Viaje:</span> {{ $booking->travel_date->format('d/m/Y') }}<br>
                    <span class="label">Contacto:</span> {{ $booking->lead?->customer_phone ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="title">Servicios Contratados</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Proveedor</th>
                    <th class="right-align">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->items as $item)
                    <tr>
                        <td>{{ $item->serviceType?->name }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->supplier?->name ?? $item->supplier_name }}</td>
                        <td class="right-align">{{ $item->currency }} {{ number_format($item->sell, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="right-align">TOTAL</td>
                    <td class="right-align">{{ $booking->currency }} {{ number_format($booking->total_sell, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($booking->transactions->isNotEmpty())
        <div class="section">
            <div class="title">Pagos Registrados</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th class="right-align">Moneda Orig.</th>
                        <th class="right-align">Monto (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->transactions->where('type', \App\Enums\TransactionType::Cobro) as $trx)
                        <tr>
                            <td>{{ $trx->created_at->format('d/m/Y') }}</td>
                            <td>{{ $trx->method }}</td>
                            <td class="right-align">{{ $trx->amount }} {{ $trx->currency->value }}</td>
                            <td class="right-align">USD {{ number_format($trx->amount_usd_fixed, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        <p><strong>Condiciones Generales:</strong></p>
        <p>La presente cotización constituye únicamente una propuesta comercial y no implica reserva, bloqueo de lugares, emisión de servicios ni contratación definitiva.</p>
        <p>Las tarifas, disponibilidad, impuestos, percepciones, tipos de cambio, horarios, itinerarios y demás condiciones corresponden a la información disponible al momento de su emisión y podrán ser modificados por los respectivos prestadores o por circunstancias ajenas al control razonable de {{ $settings?->company_name ?? config('app.name', 'nuestra agencia') }}, hasta la confirmación y emisión de los servicios.</p>
        <p>Los precios informados están sujetos a disponibilidad al momento de la reserva efectiva. La presente cotización no garantiza cupos, plazas, habitaciones ni servicios.</p>
        <p>La contratación quedará perfeccionada únicamente con la aceptación del pasajero, el pago correspondiente, la confirmación de disponibilidad por los prestadores y la emisión de la documentación de viaje.</p>
        <p>Cuando la cotización incluya servicios internacionales, los valores expresados en moneda extranjera (USD/EUR) podrán ser abonados en pesos argentinos al tipo de cambio oficial vendedor más el Impuesto PAIS (30%) y la Percepción a cuenta de Ganancias/Bienes Personales (30%), según normativas vigentes al momento del pago, salvo que se abone mediante transferencia en dólares billete.</p>
        <p>Se recomienda a todos los pasajeros la contratación de una asistencia al viajero (seguro médico) con cobertura amplia.</p>
        <p>Es responsabilidad exclusiva del pasajero contar con la documentación personal y sanitaria vigente y requerida (pasaporte, visas, vacunas, permisos de menores) para el ingreso o tránsito en los países de destino.</p>
        <p>La vigencia de esta cotización es de 24 horas desde su envío, pudiendo sufrir modificaciones sin previo aviso debido a la fluctuación cambiaria o variaciones en las tarifas aéreas o de los operadores turísticos.</p>
    </div>

</body>

</html>