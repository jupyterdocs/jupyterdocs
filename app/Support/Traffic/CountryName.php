<?php

namespace App\Support\Traffic;

class CountryName
{
    /** "NG" => "Nigeria" (falls back to the code when intl is unavailable). */
    public static function for(?string $code): string
    {
        if (! $code) {
            return 'Unknown';
        }

        $code = strtoupper($code);

        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');

            if ($name && $name !== '-'.$code && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }

    /** Flag emoji from a two-letter code; empty for unknown. */
    public static function flag(?string $code): string
    {
        if (! $code || ! preg_match('/^[A-Za-z]{2}$/', $code)) {
            return '';
        }

        $code = strtoupper($code);

        return mb_chr(0x1F1E6 + ord($code[0]) - 65).mb_chr(0x1F1E6 + ord($code[1]) - 65);
    }
}
