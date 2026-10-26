<?php

namespace App\Exports\Closure;

use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Aba padrão SICODE (título, cabeçalho escuro, zebra, filtro, congelamento) que preserva o valor 0 e mostra Nota e
 * Ordem como número inteiro completo (12 dígitos viram notação científica no formato Geral do Excel).
 */
class ClosureStrictSheet extends StyledArraySheetExport implements WithStrictNullComparison
{
    /** @var array<int, string> */
    private array $columnHeadings;

    private int $rowCount;

    public function __construct(string $sheetTitle, array $headings, array $rows)
    {
        parent::__construct($sheetTitle, $headings, $rows);

        $this->columnHeadings = array_values($headings);
        $this->rowCount       = count($rows);
    }

    public function registerEvents(): array
    {
        $events = parent::registerEvents();
        $styled = $events[AfterSheet::class];

        $events[AfterSheet::class] = function (AfterSheet $event) use ($styled) {
            $styled($event);

            $sheet = $event->sheet->getDelegate();
            $last  = max(3, $this->rowCount + 2);

            foreach ($this->columnHeadings as $index => $heading) {
                if (in_array($heading, ['Nota', 'Ordem'], true)) {
                    $column = $sheet->getCellByColumnAndRow($index + 1, 2)->getColumn();
                    $sheet->getStyle("{$column}3:{$column}{$last}")->getNumberFormat()->setFormatCode('0');
                }
            }
        };

        return $events;
    }
}
