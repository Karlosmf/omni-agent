<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? ($agencySettings->company_name ?? 'Omni-Agent') }}</title>

        @if($agencySettings?->favicon_path)
            <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $agencySettings->favicon_path) }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if($agencySettings)
            <style>
                :root {
                    --color-primary: {{ $agencySettings->fe_primary_color }};
                    --color-secondary: {{ $agencySettings->fe_secondary_color }};
                    --color-accent: {{ $agencySettings->fe_accent_color }};
                    --color-success: {{ $agencySettings->fe_success_color }};
                    --color-error: {{ $agencySettings->fe_error_color }};
                    --color-warning: {{ $agencySettings->fe_warning_color }};
                    --color-info: {{ $agencySettings->fe_info_color }};
                    --color-base-100: {{ $agencySettings->fe_base_100_color }};
                    --color-base-200: {{ $agencySettings->fe_base_200_color }};
                    --color-base-content: {{ $agencySettings->fe_base_content_color }};
                    
                    /* DaisyUI / MaryUI variables */
                    --p: {{ hex_to_oklch($agencySettings->fe_primary_color) }};
                    --s: {{ hex_to_oklch($agencySettings->fe_secondary_color) }};
                    --a: {{ hex_to_oklch($agencySettings->fe_accent_color) }};
                    --n: {{ hex_to_oklch($agencySettings->fe_neutral_color ?? '#3d4451') }};
                    --b1: {{ hex_to_oklch($agencySettings->fe_base_100_color) }};
                    --b2: {{ hex_to_oklch($agencySettings->fe_base_200_color) }};
                    --bc: {{ hex_to_oklch($agencySettings->fe_base_content_color) }};
                    --su: {{ hex_to_oklch($agencySettings->fe_success_color) }};
                    --er: {{ hex_to_oklch($agencySettings->fe_error_color) }};
                    --wa: {{ hex_to_oklch($agencySettings->fe_warning_color) }};
                    --in: {{ hex_to_oklch($agencySettings->fe_info_color) }};
                }
            </style>
        @endif
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen">
            {{ $slot }}
        </div>
    </body>
</html>
