<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? ($agencySettings->company_name ?? 'Omni-Agent') }}</title>

    @if($agencySettings?->isotipo_path)
        <link rel="icon" type="image/x-icon" href="{{ get_agency_favicon() }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($agencySettings)
        <style>
            :root {
                --color-primary: {{ $agencySettings->fe_primary_color }};
                --color-secondary: {{ $agencySettings->fe_secondary_color }};
                
                /* DaisyUI / MaryUI variables if applicable */
                --p: {{ hex_to_oklch($agencySettings->fe_primary_color) }};
                --s: {{ hex_to_oklch($agencySettings->fe_secondary_color) }};
            }
        </style>
    @endif
</head>

<body class="font-sans antialiased bg-gray-50">
    {{ $slot }}
</body>

</html>