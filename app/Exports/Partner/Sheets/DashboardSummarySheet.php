<?php

namespace App\Exports\Partner\Sheets;

use Maatwebsite\Excel\Concerns\{FromArray, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

/**
 * Aba "Resumo" do dashboard da parceira: cabeçalho com contexto e seções (título + tabela) empilhadas.
 *
 * Cada seção: ['title' => string, 'headings' => string[], 'rows' => array[], 'money' => int[] (índices de coluna em R$),
 * 'status' => int|null (índice da coluna de situação, colorida por texto)].
 */
class DashboardSummarySheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    private const COLUMNS = 5;

    /** @var array<int, array{type:string, from:int, to:int, section:array}> */
    private array $layout = [];

    private array $rows = [];

    public function __construct(
        private readonly string $headline,
        private readonly array $context,
        private readonly array $sections,
        private readonly string $footnote
    ) {
        $this->build();
    }

    private function build(): void
    {
        $this->push([$this->headline]);
        $this->layout[] = ['type' => 'headline', 'from' => 1, 'to' => 1];

        foreach ($this->context as $label => $value) {
            $this->push([$label, $value]);
            $this->layout[] = ['type' => 'context', 'from' => count($this->rows), 'to' => count($this->rows)];
        }

        foreach ($this->sections as $section) {
            $this->push(['']);
            $this->push([$section['title']]);
            $this->layout[] = ['type' => 'section', 'from' => count($this->rows), 'to' => count($this->rows)];

            $this->push($section['headings']);
            $this->layout[] = ['type' => 'header', 'from' => count($this->rows), 'to' => count($this->rows)];

            $first = count($this->rows) + 1;

            foreach ($section['rows'] as $row) {
                $this->push($row);
            }
            $this->layout[] = ['type' => 'body', 'from' => $first, 'to' => count($this->rows), 'section' => $section];
        }

        $this->push(['']);
        $this->push([$this->footnote]);
        $this->layout[] = ['type' => 'footnote', 'from' => count($this->rows), 'to' => count($this->rows)];
    }

    private function push(array $row): void
    {
        $this->rows[] = $row;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Resumo';
    }

    public function columnWidths(): array
    {
        return ['A' => 46, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 18];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = 'E';

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
                $sheet->setShowGridlines(false);

                foreach ($this->layout as $block) {
                    $range = "A{$block['from']}:{$last}{$block['to']}";

                    switch ($block['type']) {
                        case 'headline':
                            $sheet->mergeCells($range);
                            $sheet->getRowDimension($block['from'])->setRowHeight(30);
                            $sheet->getStyle($range)->applyFromArray([
                                'font'      => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                            ]);

                            break;

                        case 'context':
                            $sheet->getStyle("A{$block['from']}")->getFont()->setBold(true)->getColor()->setRGB('475569');
                            $sheet->mergeCells("B{$block['from']}:{$last}{$block['to']}");
                            $sheet->getStyle("B{$block['from']}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                            break;

                        case 'section':
                            $sheet->mergeCells($range);
                            $sheet->getRowDimension($block['from'])->setRowHeight(22);
                            $sheet->getStyle($range)->applyFromArray([
                                'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0F766E']],
                                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0F766E']]],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                            ]);

                            break;

                        case 'header':
                            $count = count($this->rows[$block['from'] - 1]);
                            $hdr   = "A{$block['from']}:" . chr(64 + max(1, $count)) . $block['from'];
                            $sheet->getStyle($hdr)->applyFromArray([
                                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                            ]);
                            $sheet->getStyle("A{$block['from']}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                            break;

                        case 'body':
                            $this->styleBody($sheet, $block);

                            break;

                        case 'footnote':
                            $sheet->mergeCells($range);
                            $sheet->getRowDimension($block['from'])->setRowHeight(34);
                            $sheet->getStyle($range)->applyFromArray([
                                'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
                                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
                            ]);

                            break;
                    }
                }
            },
        ];
    }

    private function styleBody($sheet, array $block): void
    {
        $section = $block['section'];
        $count   = count($section['headings']);
        $lastCol = chr(64 + max(1, $count));
        $range   = "A{$block['from']}:{$lastCol}{$block['to']}";

        $sheet->getStyle($range)->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($row = $block['from']; $row <= $block['to']; $row++) {
            if (($row - $block['from']) % 2 === 1) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
        }

        for ($index = 1; $index < $count; $index++) {
            $col = chr(65 + $index);
            $sheet->getStyle("{$col}{$block['from']}:{$col}{$block['to']}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach ($section['money'] ?? [] as $index) {
            $col = chr(65 + $index);
            $sheet->getStyle("{$col}{$block['from']}:{$col}{$block['to']}")->getNumberFormat()->setFormatCode('"R$" #,##0.00');
        }

        if (($section['status'] ?? null) !== null) {
            $col = chr(65 + $section['status']);

            for ($row = $block['from']; $row <= $block['to']; $row++) {
                $text  = (string) $sheet->getCell("{$col}{$row}")->getValue();
                $color = match (true) {
                    str_starts_with($text, 'Em dia')  => ['fill' => 'DCFCE7', 'font' => '166534'],
                    str_starts_with($text, 'Atenção') => ['fill' => 'FEF3C7', 'font' => '92400E'],
                    str_starts_with($text, 'Crítico') => ['fill' => 'FEE2E2', 'font' => '991B1B'],
                    default                           => null,
                };

                if ($color) {
                    $style = $sheet->getStyle("{$col}{$row}");
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color['fill']);
                    $style->getFont()->setBold(true)->getColor()->setRGB($color['font']);
                    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }
    }
}
