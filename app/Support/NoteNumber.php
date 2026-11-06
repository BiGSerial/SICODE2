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

    /**
     * Chave de comparação sem zeros à esquerda ("0003182898" e "3182898" são a mesma nota).
     * Só para casar origem x notes.note; o valor gravado continua o da ingestão.
     */
    public static function key(mixed $value): ?string
    {
        $value = self::normalize($value);

        return $value === null ? null : ltrim($value, '0');
    }

    /**
     * Grafias possíveis em notes.note para a mesma nota (como veio, sem zeros e com 10 dígitos),
     * para consultar por índice sem aplicar função na coluna.
     *
     * @return array<int, string>
     */
    public static function variants(mixed $value): array
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return [];
        }

        $key = ltrim($normalized, '0');

        return array_values(array_unique([$normalized, $key, str_pad($key, 10, '0', STR_PAD_LEFT)]));
    }
}
