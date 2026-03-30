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
                    --color-primary: {{ $agencySettings->primary_color }};
                    --color-secondary: {{ $agencySettings->secondary_color }};
                    --color-accent: {{ $agencySettings->accent_color }};
                    --color-success: {{ $agencySettings->success_color }};
                    --color-error: {{ $agencySettings->error_color }};
                    --color-warning: {{ $agencySettings->warning_color }};
                    --color-info: {{ $agencySettings->info_color }};
                    --color-base-100: {{ $agencySettings->base_100_color }};
                    --color-base-200: {{ $agencySettings->base_200_color }};
                    --color-base-content: {{ $agencySettings->base_content_color }};
                    
                    /* DaisyUI / MaryUI variables */
                    --p: {{ hex_to_oklch($agencySettings->primary_color) }};
                    --s: {{ hex_to_oklch($agencySettings->secondary_color) }};
                    --a: {{ hex_to_oklch($agencySettings->accent_color) }};
                    --n: {{ hex_to_oklch($agencySettings->neutral_color ?? '#3d4451') }};
                    --b1: {{ hex_to_oklch($agencySettings->base_100_color) }};
                    --b2: {{ hex_to_oklch($agencySettings->base_200_color) }};
                    --bc: {{ hex_to_oklch($agencySettings->base_content_color) }};
                    --su: {{ hex_to_oklch($agencySettings->success_color) }};
                    --er: {{ hex_to_oklch($agencySettings->error_color) }};
                    --wa: {{ hex_to_oklch($agencySettings->warning_color) }};
                    --in: {{ hex_to_oklch($agencySettings->info_color) }};
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
