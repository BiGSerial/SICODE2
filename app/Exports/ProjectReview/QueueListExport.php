<?php

namespace App\Exports\ProjectReview;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QueueListExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithProperties, WithEvents
{
    /**
     * @param array<int, array<int, mixed>> $rows
     */
    public function __construct(
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Desenhista',
            'Empresa',
            'Ordens',
            'Custo total',
            'Custo empresa',
            'Custo cliente',
            'Status',
            'Tipo da análise',
            'Quando foi enviado',
            'Analista',
            'Laudo técnico',
        ];
    }

    public function title(): string
    {
        return 'Lista Analise';
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Exportação - Lista para Analisar',
            'description' => 'Lista da fila da Análise de Projeto',
            'subject' => 'Análise de Projeto',
            'company' => config('app.name', 'SICODE'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnsCount = max(1, count($this->headings()));
                $lastColumn = $sheet->getCellByColumnAndRow($columnsCount, 1)->getColumn();
                $lastRow = max(1, count($this->rows) + 1);

                $headerRange = "A1:{$lastColumn}1";
                $allRange = "A1:{$lastColumn}{$lastRow}";
                $statusColumnIndex = array_search('Status', $this->headings(), true);
                $statusColumn = $statusColumnIndex === false
                    ? null
                    : $sheet->getCellByColumnAndRow($statusColumnIndex + 1, 1)->getColumn();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($headerRange);
                $sheet->getRowDimension(1)->setRowHeight(24);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F4C81'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
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

                for ($row = 2; $row <= $lastRow; $row++) {
                    $rowRange = "A{$row}:{$lastColumn}{$row}";
                    $statusText = '';
                    $statusCell = null;
                    if ($statusColumn) {
                        $statusCell = "{$statusColumn}{$row}";
                        $statusText = mb_strtoupper(trim((string) $sheet->getCell($statusCell)->getValue()));
                    }

                    if ($row % 2 === 0) {
                        $sheet->getStyle($rowRange)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8FAFC');
                    }

                    if (str_contains($statusText, 'APROV')) {
                        $sheet->getStyle($rowRange)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('ECFDF3');
                        if ($statusCell) {
                            $sheet->getStyle($statusCell)->getFont()->setBold(true)
                                ->getColor()->setRGB('166534');
                        }
                    } elseif (str_contains($statusText, 'REPROV')) {
                        $sheet->getStyle($rowRange)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FEF2F2');
                        if ($statusCell) {
                            $sheet->getStyle($statusCell)->getFont()->setBold(true)
                                ->getColor()->setRGB('B91C1C');
                        }
                    } elseif ($statusText !== '') {
                        if ($statusCell) {
                            $sheet->getStyle($statusCell)->getFont()->setBold(true)
                                ->getColor()->setRGB('1D4ED8');
                        }
                    }
                }
            },
        ];
    }
}
