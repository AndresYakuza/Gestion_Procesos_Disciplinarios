<?php namespace App\Validation;

class CustomRules
{
    /**
     * max_word_length[120]
     * Falla si alguna "palabra" (segmento sin espacios) supera $max caracteres.
     */
    public function max_word_length(string $str, string $max, array $data): bool
    {
        $max = (int) $max ?: 120;

        // separamos por espacios (incluye saltos de línea, tabs, etc.)
        $words = preg_split('/\s+/u', trim($str)) ?: [];

        foreach ($words as $w) {
            if (mb_strlen($w, 'UTF-8') > $max) {
                return false;
            }
        }

        return true;
    }
}
