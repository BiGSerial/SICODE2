<?php

namespace App\Support;

/**
 * Regra única para o número de nota (notes.note) vindo de origens externas:
 * vazio, "NULL" ou só zeros ("0", "000") não é nota e nunca pode virar registro.
 */
class NoteNumber
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtoupper($value) === 'NULL' || preg_match('/^0+$/', $value) === 1) {
            return null;
        }

        return $value;
    }

    public static function isValid(mixed $value): bool
    {
        return self::normalize($value) !== null;
    }
}
