<?php

namespace App\Support\Legal;

use InvalidArgumentException;

class LegalPartyDocument
{
    public static function digits(string $document): string
    {
        return preg_replace('/\D+/', '', $document) ?? '';
    }

    public static function type(string $document): ?string
    {
        return match (strlen(self::digits($document))) {
            11 => 'cpf',
            14 => 'cnpj',
            default => null,
        };
    }

    public static function validate(string $document): bool
    {
        $digits = self::digits($document);

        return match (strlen($digits)) {
            11 => self::validateCpf($digits),
            14 => self::validateCnpj($digits),
            default => false,
        };
    }

    public static function assertValid(string $document): array
    {
        $digits = self::digits($document);
        $type = self::type($digits);

        if ($type === null || !self::validate($digits)) {
            throw new InvalidArgumentException('CPF/CNPJ inválido.');
        }

        return [$digits, $type];
    }

    public static function hash(string $document): string
    {
        return hash_hmac('sha256', self::digits($document), self::hashKey());
    }

    public static function last4(string $document): string
    {
        return substr(self::digits($document), -4);
    }

    public static function mask(string $document, ?string $type = null): string
    {
        $digits = self::digits($document);
        $type ??= self::type($digits);

        return match ($type) {
            'cpf' => '***.***.***-' . substr($digits, -2),
            'cnpj' => '**.***.***/****-' . substr($digits, -2),
            default => '****' . substr($digits, -4),
        };
    }

    public static function format(string $document, ?string $type = null): string
    {
        $digits = self::digits($document);
        $type ??= self::type($digits);

        if ($type === 'cpf' && strlen($digits) === 11) {
            return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
        }

        if ($type === 'cnpj' && strlen($digits) === 14) {
            return substr($digits, 0, 2) . '.' . substr($digits, 2, 3) . '.' . substr($digits, 5, 3) . '/' . substr($digits, 8, 4) . '-' . substr($digits, 12, 2);
        }

        return $document;
    }

    private static function validateCpf(string $digits): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;
            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $digits[$index] * (($position + 1) - $index);
            }

            $checkDigit = ((10 * $sum) % 11) % 10;
            if ((int) $digits[$position] !== $checkDigit) {
                return false;
            }
        }

        return true;
    }

    private static function validateCnpj(string $digits): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $firstWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        return self::cnpjCheckDigit($digits, $firstWeights) === (int) $digits[12]
            && self::cnpjCheckDigit($digits, $secondWeights) === (int) $digits[13];
    }

    private static function cnpjCheckDigit(string $digits, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += (int) $digits[$index] * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private static function hashKey(): string
    {
        return (string) config('app.pii_hash_key', config('app.key'));
    }
}
