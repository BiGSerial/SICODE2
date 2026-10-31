<?php

namespace App\Exports\Engineers;

use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use Maatwebsite\Excel\Concerns\{FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMultipleSheets, WithProperties, WithTitle};
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Excel da "Lista de informes" (Engenharia): uma linha por informe nos filtros da tela e a aba `Controle Exportacao`.
 * As linhas vêm de WorkedListExportJob (mesma consulta da tela); este arquivo só formata.
 */
class WorkedListExport implements WithMultipleSheets, WithProperties
{
    public const HEADINGS = [
        'Nota', 'Ordens', 'Rubrica', 'Município', 'Empreiteira', 'Situação do informe', 'Escopo', 'Alteração no projeto', 'Equipamentos',
        'Equipe WPA', 'Responsável', 'Execução', 'Informado em', 'Prazo da ADS', 'ADS', 'ADS chegou em', 'Rejeições', 'Observação da empreiteira',
    ];

    /** Colunas (A=1) com data real. */
    private const DATE_COLUMNS = ['L', 'M', 'N', 'P'];

    /**
     * @param array<int, array<int, mixed>> $rows
     * @param array<int, array{0: string, 1: string}> $audit
     */
    public function __construct(private readonly array $rows, private readonly array $audit)
    {
    }

    public function sheets(): array
    {
        $dateColumns = self::DATE_COLUMNS;

        return [
            new class ($this->rows, $dateColumns) implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithTitle {
                public function __construct(private array $rows, private array $dateColumns)
                {
                }

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return WorkedListExport::HEADINGS;
                }

                public function title(): string
                {
                    return 'Informes';
                }

                public function columnFormats(): array
                {
                    return array_fill_keys($this->dateColumns, NumberFormat::FORMAT_DATE_DDMMYYYY);
                }
            },
            new StyledArraySheetExport('Controle Exportacao', ['Campo', 'Valor'], $this->audit),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => 'Lista de informes',
            'description'    => 'Informes finais de obra nos filtros da tela',
            'subject'        => 'Engenharia',
        ];
    }
}
