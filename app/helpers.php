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
