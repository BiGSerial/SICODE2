<?php

namespace App\Support;

/** Pequenos auxiliares de apresentação do módulo Qualidade (avatares, tipos de arquivo, tamanhos). */
class QualityUi
{
    public static function initials(?string $name): string
    {
        if (!$name) {
            return '?';
        }

        return collect(preg_split('/\s+/', trim($name)))->filter(fn ($word) => mb_strlen($word) > 2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->take(2)->implode('') ?: mb_strtoupper(mb_substr($name, 0, 1));
    }

    /** Cor estável do avatar a partir do nome. */
    public static function avatarTone(?string $name): string
    {
        $tones = ['#2563eb', '#0891b2', '#059669', '#d97706', '#7c3aed', '#db2777', '#475569', '#dc2626'];

        return $tones[abs(crc32((string) $name)) % count($tones)];
    }

    /** @return array{key:string,label:string,icon:string,tone:string} */
    public static function fileKind(?string $ext): array
    {
        $ext = mb_strtolower((string) $ext);

        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)                    => ['key' => 'image', 'label' => 'Imagem', 'icon' => 'ri-image-2-line', 'tone' => '#0891b2'],
            $ext === 'pdf'                                                                 => ['key' => 'pdf', 'label' => 'PDF', 'icon' => 'ri-file-pdf-2-line', 'tone' => '#dc2626'],
            in_array($ext, ['dwg', 'dxf', 'dws', 'dwt', 'dgn', 'rvt', 'rfa', 'skp'], true) => ['key' => 'cad', 'label' => 'CAD', 'icon' => 'ri-shape-line', 'tone' => '#7c3aed'],
            in_array($ext, ['xls', 'xlsx', 'xlsm', 'ods', 'csv'], true)                    => ['key' => 'sheet', 'label' => 'Planilha', 'icon' => 'ri-file-excel-2-line', 'tone' => '#059669'],
            in_array($ext, ['doc', 'docx', 'odt', 'txt'], true)                            => ['key' => 'doc', 'label' => 'Documento', 'icon' => 'ri-file-word-2-line', 'tone' => '#2563eb'],
            default                                                                        => ['key' => 'other', 'label' => 'Outro', 'icon' => 'ri-file-line', 'tone' => '#64748b'],
        };
    }

    public static function bytes(?int $bytes): string
    {
        if (!$bytes) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = (int) floor(log($bytes, 1024));

        return number_format($bytes / (1024 ** $i), $i ? 1 : 0, ',', '.') . ' ' . $units[min($i, 3)];
    }

    /** Rótulo legível das colunas da Nota usadas em critérios e filtros. */
    public static function noteColumn(?string $column): string
    {
        $labels = [
            'note'         => 'Nota', 'numPedido' => 'Pedido', 'nstats' => 'Status da Nota', 'type_note' => 'Tipo da Nota', 'rubrica' => 'Rubrica', 'lexp' => 'Localização',
            'nexp'         => 'Município', 'material' => 'Material', 'client' => 'Cliente', 'group1' => 'Grupo 1', 'group2' => 'Grupo 2', 'group3' => 'Grupo 3', 'group4' => 'Grupo 4',
            'group5'       => 'Grupo 5', 'dt_status' => 'Data do status', 'dt_created' => 'Data de criação', 'days_left' => 'Dias restantes', 'days' => 'Dias', 'centerjob' => 'Centro de trabalho',
            'mesalization' => 'Mesalização', 'pep' => 'PEP', 'status' => 'Situação', 'mmgd' => 'MMGD', 'txpriority' => 'Prioridade', 'pze' => 'Prazo', 'doe' => 'Depende de órgão externo',
            'postes'       => 'Postes', 'value' => 'Valor', 'user' => 'Usuário SAP',
        ];

        return $labels[$column] ?? ucwords(trim(str_replace('_', ' ', preg_replace('/(?<!^)(?=[A-Z])/', ' ', (string) $column))));
    }
}
