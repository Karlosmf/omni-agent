<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expediente de Viaje #{{ $booking->file_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #075E54; margin-bottom: 20px; padding-bottom: 10px; }
        .logo { font-size: 24px; font-weight: bold; color: #075E54; }
        .company-info { float: right; text-align: right; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .section { margin-bottom: 20px; }
        .grid { width: 100%; margin-bottom: 15px; }
        .grid td { vertical-align: top; }
        .label { font-weight: bold; color: #555; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.items th { background-color: #f2f2f2; color: #333; }
        .total-row td { font-weight: bold; background-color: #eee; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <table width="100%">
            <tr>
                <td class="logo">Luopan Viajes</td>
                <td class="company-info">
                    Legajo #1234<br>
                    Av. Corrientes 1234, CABA<br>
                    contacto@luopan.com
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
                    <span class="label">Estado:</span> {{ ucfirst($booking->status->value) }}
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
                    <th align="right">Precio (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->items as $item)
                <tr>
                    <td>{{ ucfirst($item->type->value) }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->supplier?->name ?? $item->supplier_name }}</td>
                    <td align="right">USD {{ number_format($item->sell_usd, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" align="right">TOTAL</td>
                    <td align="right">USD {{ number_format($booking->total_sell_usd, 2) }}</td>
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
                    <th>Moneda Orig.</th>
                    <th align="right">Monto (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->transactions->where('type', \App\Enums\TransactionType::Cobro) as $trx)
                <tr>
                    <td>{{ $trx->created_at->format('d/m/Y') }}</td>
                    <td>{{ $trx->method }}</td>
                    <td>{{ $trx->amount }} {{ $trx->currency->value }}</td>
                    <td align="right">USD {{ number_format($trx->amount_usd_fixed, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Este documento es un comprobante no fiscal emitido por el sistema Omni-Agent.<br>
        Gracias por confiar en Luopan Viajes.
    </div>

</body>
</html>
