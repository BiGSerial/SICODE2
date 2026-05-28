<?php

namespace App\Exports\ProjectReview\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StyledArraySheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $headings,
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 1);
                $columnsCount = max(1, count($this->headings));
                $lastColumn = $sheet->getCellByColumnAndRow($columnsCount, 1)->getColumn();
                $dataStartRow = 3;
                $lastRow = max($dataStartRow, count($this->rows) + 2);

                $titleRange = "A1:{$lastColumn}1";
                $headerRange = "A2:{$lastColumn}2";
                $dataRange = "A{$dataStartRow}:{$lastColumn}{$lastRow}";
                $allRange = "A1:{$lastColumn}{$lastRow}";

                $sheet->setCellValue('A1', 'SICODE - ' . mb_strtoupper($this->sheetTitle));
                $sheet->mergeCells($titleRange);

                $sheet->freezePane('A3');
                $sheet->setAutoFilter($headerRange);
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(24);

                $sheet->getStyle($titleRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F766E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                $sheet->getStyle($allRange)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                for ($row = $dataStartRow; $row <= $lastRow; $row++) {
                    if ($row % 2 !== 0) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8FAFC'],
                            ],
                        ]);
                    }
                }

                // Ajustes de largura mínima para não "quebrar" colunas curtas demais.
                for ($col = 1; $col <= $columnsCount; $col++) {
                    $columnLetter = $sheet->getCellByColumnAndRow($col, 1)->getColumn();
                    $currentWidth = (float) $sheet->getColumnDimension($columnLetter)->getWidth();
                    if ($currentWidth < 14) {
                        $sheet->getColumnDimension($columnLetter)->setWidth(14);
                    }
                }

                // Alinhamento e formatos automáticos por nome do cabeçalho.
                foreach ($this->headings as $index => $heading) {
                    $column = $sheet->getCellByColumnAndRow($index + 1, 2)->getColumn();
                    $normalized = mb_strtolower(trim((string) $heading));
                    $columnRange = "{$column}{$dataStartRow}:{$column}{$lastRow}";

                    if (
                        str_contains($normalized, 'custo') ||
                        str_contains($normalized, 'valor') ||
                        str_contains($normalized, 'economia') ||
                        str_contains($normalized, 'saldo') ||
                        str_contains($normalized, 'acréscimo') ||
                        str_contains($normalized, 'aumento') ||
                        str_contains($normalized, 'ganho')
                    ) {
                        $sheet->getStyle($columnRange)->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle($columnRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        continue;
                    }

                    if (str_contains($normalized, 'varia') || str_contains($normalized, '(%)') || str_contains($normalized, '%')) {
                        $sheet->getStyle($columnRange)->getNumberFormat()->setFormatCode('0.00');
                        $sheet->getStyle($columnRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        continue;
                    }

                    if (str_contains($normalized, 'data') || str_contains($normalized, 'quando')) {
                        $sheet->getStyle($columnRange)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);
                        $sheet->getStyle($columnRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        continue;
                    }

                    if (str_contains($normalized, 'coment')) {
                        $sheet->getStyle($columnRange)->getAlignment()->setWrapText(true);
                        $sheet->getColumnDimension($column)->setWidth(48);
                        continue;
                    }

                    if (
                        str_contains($normalized, 'nota') ||
                        str_contains($normalized, 'ordem') ||
                        str_contains($normalized, 'status') ||
                        str_contains($normalized, 'decisão') ||
                        str_contains($normalized, 'rodada')
                    ) {
                        $sheet->getStyle($columnRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }
            },
        ];
    }
}
