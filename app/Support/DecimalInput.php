<?php

namespace App\Support;

class DecimalInput
{
    /**
     * Accept either "." or "," as the decimal mark. When both appear, the last one is the decimal mark.
     */
    public static function normalize(mixed $value): string
    {
        $text = str_replace(',', '.', trim((string) $value));
        $text = preg_replace('/[^\d.\-]/', '', $text) ?? '';

        if (substr_count($text, '.') <= 1) {
            return $text;
        }

        $last = strrpos($text, '.');

        return str_replace('.', '', substr($text, 0, $last)).substr($text, $last);
    }
}
