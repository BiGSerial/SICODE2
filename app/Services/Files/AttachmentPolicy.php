<?php

namespace App\Services\Files;

/**
 * Política única de anexos enviados pelos usuários: tipos aceitos e limites.
 * Padrão: imagem, PDF, Office e CAD. ZIP só nos fluxos que já tinham essa permissão.
 * Os componentes de upload, as telas (texto e `accept`) e a configuração do Livewire leem daqui.
 */
class AttachmentPolicy
{
    public const MAX_FILE_MB = 30;

    public const MAX_BATCH_MB = 100;

    /** @var array<string, array<int, string>> */
    private const GROUPS = [
        'Imagem' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp', 'svg'],
        'PDF'    => ['pdf'],
        'Office' => ['doc', 'docx', 'xls', 'xlsx', 'xlsm', 'ppt', 'pptx', 'odt', 'ods', 'odp'],
        'CAD'    => ['dwg', 'dxf', 'dws', 'dwt', 'dgn', 'rvt', 'rfa', 'skp'],
    ];

    /** Extensões cujo conteúdo é conferido pelo tipo real do arquivo (CAD, ZIP e Office baseado em zip só pela extensão). */
    public const CONTENT_CHECKED = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    public static function maxFileBytes(): int
    {
        return self::MAX_FILE_MB * 1024 * 1024;
    }

    public static function maxBatchBytes(): int
    {
        return self::MAX_BATCH_MB * 1024 * 1024;
    }

    /** @return array<string, array<int, string>> grupo => extensões */
    public static function groups(bool $allowZip = false): array
    {
        return self::GROUPS + ($allowZip ? ['ZIP' => ['zip']] : []);
    }

    /** @return array<int, string> */
    public static function extensions(bool $allowZip = false): array
    {
        return array_merge(...array_values(self::groups($allowZip)));
    }

    /** Valor do atributo `accept` do input de arquivo. */
    public static function accept(bool $allowZip = false): string
    {
        return implode(',', array_map(fn (string $ext) => '.' . $ext, self::extensions($allowZip)));
    }

    /** Texto curto para o usuário: "Imagem (JPG, PNG…), PDF, Office (…), CAD (…)". */
    public static function describe(bool $allowZip = false): string
    {
        return collect(self::groups($allowZip))
            ->map(fn (array $extensions, string $group) => count($extensions) > 1 ? $group . ' (' . strtoupper(implode(', ', $extensions)) . ')' : $group)
            ->implode(' · ');
    }
}
