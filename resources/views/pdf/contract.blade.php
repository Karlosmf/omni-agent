<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acuerdo de Viaje - {{ $booking->file_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 250px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #000;
        }

        .header p {
            font-size: 12px;
            color: #555;
            margin: 2px 0 0;
        }

        .contract-content {
            margin-top: 20px;
            text-align: justify;
        }

        .contract-content h2, .contract-content h3 {
            color: #000;
        }
        
        .footer {
            margin-top: 40px;
            font-size: 10px;
            color: #777;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    @php
        $settings = $settings ?? \App\Models\AgencySetting::first();
        
        $customerName = $booking->holder_name ?? '_______________';
        $customerDni = $booking->lead?->document_number ?? '_______________';
        $customerEmail = $booking->lead?->email ?? '_______________';
        $customerPhone = $booking->lead?->phone ?? '_______________';
        $totalTravel = ($booking->currency ?? 'USD') . ' ' . number_format($booking->total_sell, 2, ',', '.');
        $destination = $booking->destination ?? '_______________';
        $travelDate = $booking->travel_date ? $booking->travel_date->format('d/m/Y') : '_______________';
        
        $template = $settings?->contract_template ?? '<p>No se ha configurado la plantilla del acuerdo en los ajustes de la agencia.</p>';
        
        $content = str_replace(
            ['[NOMBRE_CLIENTE]', '[DNI_CLIENTE]', '[EMAIL_CLIENTE]', '[TELEFONO_CLIENTE]', '[TOTAL_VIAJE]', '[DESTINO]', '[FECHA_VIAJE]'],
            [$customerName, $customerDni, $customerEmail, $customerPhone, $totalTravel, $destination, $travelDate],
            $template
        );
    @endphp

    <div class="header">
        @if($settings && $settings->logotipo_path)
            @php
                $logoUrl = Storage::disk('branding')->url($settings->logotipo_path);
                // Si estamos en entorno local, pasamos la ruta absoluta del storage app/public para dompdf
                if(app()->environment('local')) {
                   $logoUrl = storage_path('app/public/branding/' . $settings->logotipo_path);
                }
            @endphp
            @if(file_exists($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $settings->company_name }}">
            @else
                <img src="{{ public_path('storage/branding/' . $settings->logotipo_path) }}" alt="{{ $settings->company_name }}" onerror="this.style.display='none'">
            @endif
        @endif
        <p>
            <strong>{{ $settings?->company_name ?? config('app.name') }}</strong>
            @if($settings?->legajo) | Legajo: {{ $settings->legajo }} @endif
            @if($settings?->cuit) | CUIT: {{ $settings->cuit }} @endif
        </p>
        <p>Expediente: {{ $booking->file_number }} | Fecha de emisión: {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="contract-content">
        {!! $content !!}
    </div>

    <br><br><br>
    
    <table width="100%" style="margin-top: 50px;">
        <tr>
            <td width="45%" style="text-align: center; border-top: 1px solid #000; padding-top: 5px;">
                Firma del Pasajero Principal<br>
                Aclaración: {{ $customerName }}<br>
                DNI/Pasaporte: {{ $customerDni }}
            </td>
            <td width="10%"></td>
            <td width="45%" style="text-align: center; border-top: 1px solid #000; padding-top: 5px;">
                Firma Agencia<br>
                {{ $settings?->company_name ?? config('app.name') }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Generado por {{ config('app.name', 'Omni-Agent') }} el {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
