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

class PostWorkProcessReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    private const STATUS = [
        'pending'   => 'Sem dados',
        'open'      => 'Em andamento',
        'open_late' => 'Em aberto - estourado',
        'on_time'   => 'No prazo',
        'late'      => 'Estourado',
    ];

    /**
     * @param Collection<int, array<string, mixed>> $rows
     */
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tipo', 'Nota', 'Nota D5', 'Empresa', 'Informante', 'Data do informe',
            'Fiscalização - Despacho', 'Fiscalização - Atribuição', 'Fiscalização - Conclusão', 'Fiscalização - Usuário', 'Fiscalização - Empresa',
            'Pagamento/Medição - Despacho', 'Pagamento/Medição - Atribuição', 'Pagamento/Medição - Conclusão', 'Pagamento/Medição - Usuário', 'Pagamento/Medição - Empresa',
            'Despacho Fiscal (dias úteis / prazo 2)', 'Despacho Fiscal - Situação',
            'Fiscalização (dias úteis / prazo 3)', 'Fiscalização - Situação',
            'Medição/Pagamento (dias úteis / prazo 3)', 'Medição/Pagamento - Situação',
            'Total (dias úteis / prazo 8)', 'Total - Situação',
            'Aprovação eng. em', 'Engenheiro', 'Supervisionado em', 'Supervisor', 'Pago/medido em', 'Pago/medido por',
            'ADS em', 'ADS valor',
        ];
    }

    /** @param array<string, mixed> $row */
    public function map($row): array
    {
        $fiscal = $row['fiscal'] ?? [];
        $payment = $row['payment'] ?? [];
        $s = $row['stages'];

        return [
            $row['type_label'],
            $row['note'] ?? '---',
            $row['note_d5'] ?? '---',
            $row['company'] ?? '---',
            $row['informer'] ?? '---',
            $this->date($row['start_at']),
            $this->date($fiscal['dispatch_at'] ?? null),
            $this->date($fiscal['att_at'] ?? null),
            $this->date($fiscal['completed_at'] ?? null),
            $fiscal['user'] ?? '---',
            $fiscal['company'] ?? '---',
            $this->date($payment['dispatch_at'] ?? null),
            $this->date($payment['att_at'] ?? null),
            $this->date($payment['completed_at'] ?? null),
            $payment['user'] ?? '---',
            $payment['company'] ?? '---',
            $s['fiscal_dispatch']['days'] ?? '---', self::STATUS[$s['fiscal_dispatch']['status']],
            $s['fiscalization']['days'] ?? '---', self::STATUS[$s['fiscalization']['status']],
            $s['measurement']['days'] ?? '---', self::STATUS[$s['measurement']['status']],
            $s['total']['days'] ?? '---', self::STATUS[$s['total']['status']],
            $this->date($row['decision_at'] ?? null),
            $row['engineer'] ?? '---',
            $this->date($row['supervision_at'] ?? null),
            $row['supervisor'] ?? '---',
            $this->date($row['payment_at'] ?? null),
            $row['payer'] ?? '---',
            $this->date($row['ads_at'] ?? null),
            isset($row['ads_amount']) ? (float) $row['ads_amount'] : '---',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getCellByColumnAndRow(count($this->headings()), 1)->getColumn();
                $lastRow = max(1, $sheet->getHighestRow());

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}1");
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);

                // Colunas de situação (R, T, V, X): vermelho quando estourado.
                foreach (['R', 'T', 'V', 'X'] as $col) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $value = (string) $sheet->getCell("{$col}{$row}")->getValue();
                        if (str_contains($value, 'estourado') || $value === 'Estourado') {
                            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                            ]);
                        } elseif ($value === 'No prazo') {
                            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                            ]);
                        }
                    }
                }
            },
        ];
    }

    private function date($value): string
    {
        return $value ? $value->format('d/m/Y H:i') : '---';
    }
}
