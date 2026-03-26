<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FiveNoteReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(
        private readonly array $rows
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return [
            'Nota D5',
            'Nota/OV',
            'Empresa parceira',
            'Despachada em',
            'Concluida pelo parceiro em',
            'Fiscalizada em',
            'Paga em',
            'Fiscalizada por',
            'Paga por',
            'Passivo',
            'Local instalacao',
            'Conjunto',
            'PEP',
            'E-PEP',
            'Codificacao',
            'Sintomas',
            'Motivo',
            'Descricao',
            'Responsavel registro',
            'Criado em',
            'Atualizado em',
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public function map($row): array
    {
        return [
            $row['nota_d5'] ?? '---',
            $row['nota_ov'] ?? '---',
            $row['empresa_parceira'] ?? '---',
            $row['dispatch_at'] ?? '---',
            $row['completed_at'] ?? '---',
            $row['supervisioned_at'] ?? '---',
            $row['payed_at'] ?? '---',
            $row['fiscalizado_por'] ?? '---',
            $row['pago_por'] ?? '---',
            $row['passivo'] ?? 'NAO',
            $row['local_instalacao'] ?? '---',
            $row['conjunto'] ?? '---',
            $row['pep'] ?? '---',
            $row['e_pep'] ?? '---',
            $row['codificacao'] ?? '---',
            $row['sintomas'] ?? '---',
            $row['motivo'] ?? '---',
            $row['descricao'] ?? '---',
            $row['responsavel_registro'] ?? '---',
            $row['criado_em'] ?? '---',
            $row['atualizado_em'] ?? '---',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnsCount = count($this->headings());
                $lastColumn = $sheet->getCellByColumnAndRow($columnsCount, 1)->getColumn();
                $lastRow = max(1, $sheet->getHighestRow());

                $headerRange = "A1:{$lastColumn}1";
                $allRange = "A1:{$lastColumn}{$lastRow}";
                $dataRange = $lastRow >= 2 ? "A2:{$lastColumn}{$lastRow}" : null;

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(26);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E78'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
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

                if ($dataRange) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC'],
                                ],
                            ]);
                        }

                        $passiveValue = strtoupper((string) $sheet->getCell("J{$row}")->getValue());
                        if ($passiveValue === 'SIM') {
                            $sheet->getStyle("J{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FEF3C7'],
                                ],
                            ]);
                        } else {
                            $sheet->getStyle("J{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'DCFCE7'],
                                ],
                            ]);
                        }
                    }
                }
            },
        ];
    }
}

