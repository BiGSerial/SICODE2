<?php

namespace App\Services\Closure;

/**
 * Áreas que trocam pendências no Encerramento (origin_area / destination_area em closure_issues e area no mapa de status usuário).
 * Ponto único dos códigos, rótulos e do Service (pasta) que dá acesso à caixa de cada área.
 * Caixas existem SÓ para Medição e Fiscalização; o Desenho recebe RI (ClosureInternalReturn), não pendência.
 */
final class ClosureArea
{
    public const CLOSURE     = 'CLOSURE';
    public const PARTNER     = 'PARTNER';
    public const MEASUREMENT = 'MEASUREMENT';
    public const SUPERVISION = 'SUPERVISION';

    /** Pasta (services.folder) do Service que dá acesso à caixa da área. */
    private const INBOX_FOLDERS = [
        self::MEASUREMENT => 'pagamento',
        self::SUPERVISION => 'fiscalizacao',
    ];

    private const LABELS = [
        self::CLOSURE     => 'Encerramento',
        self::PARTNER     => 'Parceira',
        self::MEASUREMENT => 'Medição',
        self::SUPERVISION => 'Fiscalização',
    ];

    /** @return array<int, string> áreas que têm caixa (Medição e Fiscalização) */
    public static function inboxAreas(): array
    {
        return array_keys(self::INBOX_FOLDERS);
    }

    public static function hasInbox(string $area): bool
    {
        return isset(self::INBOX_FOLDERS[$area]);
    }

    public static function serviceFolder(string $area): ?string
    {
        return self::INBOX_FOLDERS[$area] ?? null;
    }

    /** Área cuja caixa pertence ao Service da pasta (`pagamento` => Medição); null se a pasta não tem caixa. */
    public static function forFolder(string $folder): ?string
    {
        $area = array_search($folder, self::INBOX_FOLDERS, true);

        return $area === false ? null : $area;
    }

    public static function label(string $area): string
    {
        return self::LABELS[$area] ?? $area;
    }

    /** @return array<string, string> área => rótulo, só as que têm caixa */
    public static function inboxOptions(): array
    {
        return array_map(fn (string $area) => self::LABELS[$area], array_combine(self::inboxAreas(), self::inboxAreas()));
    }
}
