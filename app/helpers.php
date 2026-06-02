<?php

if (! function_exists('hex_to_oklch')) {
    /**
     * Convert HEX color to OKLCH format for DaisyUI 5.0 (Tailwind v4)
     * This is a simplified version for common uses.
     */
    function hex_to_oklch(?string $hex): string
    {
        if (empty($hex)) {
            return "1.00 0.000 0.0"; // Fallback to white
        }

        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // OKLCH approximation (very rough):
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        
        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }
        
        $oklch_l = $l; 
        $oklch_c = $s * 0.4; 
        $oklch_h = $h * 360;
        
        return sprintf("%.2f %.3f %.1f", $oklch_l, $oklch_c, $oklch_h);
    }
}

if (! function_exists('get_agency_settings')) {
    function get_agency_settings(): ?\App\Models\AgencySetting
    {
        try {
            return \Illuminate\Support\Facades\Cache::rememberForever('agency_settings', function () {
                return \App\Models\AgencySetting::first();
            });
        } catch (\Exception $e) {
            return \App\Models\AgencySetting::first();
        }
    }
}

if (! function_exists('get_agency_logotipo_url')) {
    function get_agency_logotipo_url(): string
    {
        $settings = get_agency_settings();
        
        if ($settings && $settings->logotipo_path) {
            return asset('images/branding/' . $settings->logotipo_path);
        }

        return asset('images/branding/logo-full.png');
    }
}

if (! function_exists('get_agency_logotipo_path')) {
    function get_agency_logotipo_path(): string
    {
        $settings = get_agency_settings();
        
        if ($settings && $settings->logotipo_path) {
            return public_path('images/branding/' . $settings->logotipo_path);
        }

        return public_path('images/branding/logo-full.png');
    }
}

if (! function_exists('get_agency_isotipo_url')) {
    function get_agency_isotipo_url(): string
    {
        $settings = get_agency_settings();
        
        if ($settings && $settings->isotipo_path) {
            return asset('images/branding/' . $settings->isotipo_path);
        }

        return asset('images/branding/logo-icon.png');
    }
}

if (! function_exists('get_agency_favicon')) {
    function get_agency_favicon(): string
    {
        return get_agency_isotipo_url();
    }
}

if (! function_exists('format_social_link')) {
    function format_social_link(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:' . $url;
        }

        if (preg_match('/^\+?[0-9]{7,15}$/', str_replace([' ', '-', '(', ')'], '', $url))) {
            return 'tel:' . str_replace([' ', '-', '(', ')'], '', $url);
        }

        if (!str_starts_with($url, 'http') && !str_starts_with($url, '/') && !str_contains($url, ':')) {
            return 'https://' . $url;
        }

        return $url;
    }
}
