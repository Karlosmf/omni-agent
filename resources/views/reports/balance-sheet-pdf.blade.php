<!DOCTYPE html>
<html>

<head>
    <title>Balance Anual {{ $year }}</title>
    <style>
        body {
            font-family: sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #008069;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .total-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }

        .income {
            color: #22c55e;
        }

        .expense {
            color: #ef4444;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">LUOPAN VIAJES</div>
        <h3>Reporte de Balance Anual - {{ $year }}</h3>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Mes</th>
                <th>Ingresos ({{ $currency }})</th>
                <th>Egresos ({{ $currency }})</th>
                <th>Balance Neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyData as $month => $data)
                <tr>
                    <td class="text-left">{{ $data['label'] }}</td>
                    <td class="income">{{ number_format($data['income'], 2) }}</td>
                    <td class="expense">{{ number_format($data['expense'], 2) }}</td>
                    <td style="color: {{ $data['balance'] >= 0 ? 'black' : 'red' }}">
                        {{ number_format($data['balance'], 2) }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="text-left">TOTAL ANUAL</td>
                <td class="income">{{ number_format($totals['income'], 2) }}</td>
                <td class="expense">{{ number_format($totals['expense'], 2) }}</td>
                <td>{{ number_format($totals['balance'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>