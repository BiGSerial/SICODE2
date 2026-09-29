<?php

namespace App\Exports\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Aba "Processo": uma linha por informe (Final, Parcial ou D5).
 * Layout: linha 1 título, linha 2 faixa dos grupos, linha 3 cabeçalhos, dados a partir da linha 4.
 */
class PostWorkProcessDetailSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents, WithStrictNullComparison
{
    private const DATE_FORMAT = 'dd/mm/yyyy hh:mm';

    private const HEADER_ROW = 3;
    private const FIRST_DATA_ROW = 4;

    /** @var array<int, array{group: string, color: string, title: string, width: int, kind: string}> */
    private array $columns;

    private const TYPE_STYLE = [
        'final'   => ['fill' => 'DBEAFE', 'font' => '1E40AF'],
        'partial' => ['fill' => 'EDE9FE', 'font' => '5B21B6'],
        'd5'      => ['fill' => 'CCFBF1', 'font' => '115E59'],
    ];

    private const STATUS_STYLE = [
        'on_time'   => ['fill' => 'DCFCE7', 'font' => '166534', 'bold' => true, 'italic' => false],
        'late'      => ['fill' => 'FEE2E2', 'font' => '991B1B', 'bold' => true, 'italic' => false],
        'open_late' => ['fill' => 'FEF3C7', 'font' => '92400E', 'bold' => true, 'italic' => true],
        'open'      => ['fill' => 'DBEAFE', 'font' => '1E40AF', 'bold' => false, 'italic' => true],
    ];

    private const OVERALL_LABEL = ['on_time' => 'No prazo', 'late' => 'Estourado', 'open' => 'Em andamento'];

    /** @param Collection<int, array<string, mixed>> $rows */
    /**
     * @param array<string, int> $limits
     * @param array<string, string> $labels
     */
    public function __construct(private readonly Collection $rows, array $limits, array $labels)
    {
        $c = fn (string $group, string $color, string $title, int $width, string $kind = 'text') => compact('group', 'color', 'title', 'width', 'kind');

        $inf = ['Informe', '1E293B'];
        $fis = ['Fiscalização', '0F766E'];
        $pag = [$labels['measurement'], '1D4ED8'];
        $prz = ['Prazos (dias úteis)', 'B45309'];
        $mar = ['Marcos do informe', '475569'];

        $this->columns = [
            $c(...$inf, ...['title' => 'Tipo de informe', 'width' => 16, 'kind' => 'type']),
            $c(...$inf, ...['title' => 'Nº do informe', 'width' => 12, 'kind' => 'id']),
            $c(...$inf, ...['title' => 'Escopo', 'width' => 12, 'kind' => 'center']),
            $c(...$inf, ...['title' => 'Nota', 'width' => 14, 'kind' => 'center']),
            $c(...$inf, ...['title' => 'Nota D5', 'width' => 13, 'kind' => 'center']),
            $c(...$inf, ...['title' => 'Ordens', 'width' => 26, 'kind' => 'wrap']),
            $c(...$inf, ...['title' => 'Empresa do informe', 'width' => 30]),
            $c(...$inf, ...['title' => 'Informante', 'width' => 22]),
            $c(...$inf, ...['title' => 'Data do informe', 'width' => 17, 'kind' => 'date']),

            $c(...$fis, ...['title' => 'Despacho', 'width' => 17, 'kind' => 'date']),
            $c(...$fis, ...['title' => 'Atribuição', 'width' => 17, 'kind' => 'date']),
            $c(...$fis, ...['title' => 'Conclusão', 'width' => 17, 'kind' => 'date']),
            $c(...$fis, ...['title' => 'Usuário', 'width' => 22]),
            $c(...$fis, ...['title' => 'Empresa do usuário', 'width' => 26]),

            $c(...$pag, ...['title' => 'Despacho', 'width' => 17, 'kind' => 'date']),
            $c(...$pag, ...['title' => 'Atribuição', 'width' => 17, 'kind' => 'date']),
            $c(...$pag, ...['title' => 'Conclusão', 'width' => 17, 'kind' => 'date']),
            $c(...$pag, ...['title' => 'Usuário', 'width' => 22]),
            $c(...$pag, ...['title' => 'Empresa do usuário', 'width' => 26]),

            $c(...$prz, ...['title' => "{$labels['fiscal_dispatch']}\n(prazo {$limits['fiscal_dispatch']})", 'width' => 14, 'kind' => 'days:fiscal_dispatch']),
            $c(...$prz, ...['title' => "{$labels['fiscalization']}\n(prazo {$limits['fiscalization']})", 'width' => 14, 'kind' => 'days:fiscalization']),
            $c(...$prz, ...['title' => "{$labels['measurement']}\n(prazo {$limits['measurement']})", 'width' => 17, 'kind' => 'days:measurement']),
            $c(...$prz, ...['title' => "Total\n(prazo {$limits['total']})", 'width' => 12, 'kind' => 'days:total']),
            $c(...$prz, ...['title' => 'Situação geral', 'width' => 15, 'kind' => 'overall']),
            $c(...$prz, ...['title' => 'Etapas estouradas', 'width' => 30, 'kind' => 'wrap']),

            $c(...$mar, ...['title' => 'Aprovação do engenheiro', 'width' => 17, 'kind' => 'date']),
            $c(...$mar, ...['title' => 'Engenheiro', 'width' => 22]),
            $c(...$mar, ...['title' => 'Supervisionado em', 'width' => 17, 'kind' => 'date']),
            $c(...$mar, ...['title' => 'Supervisor', 'width' => 22]),
            $c(...$mar, ...['title' => 'Pago/medido em', 'width' => 17, 'kind' => 'date']),
            $c(...$mar, ...['title' => 'Pago/medido por', 'width' => 22]),
            $c(...$mar, ...['title' => 'ADS criado em', 'width' => 17, 'kind' => 'date']),
            $c(...$mar, ...['title' => 'ADS valor (R$)', 'width' => 16, 'kind' => 'money']),
        ];
    }

    public function title(): string
    {
        return 'Processo';
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->columns as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 1)] = $col['width'];
        }

        return $widths;
    }

    public function array(): array
    {
        $n = count($this->columns);
        $blank = array_fill(0, $n, null);

        $out = [
            array_replace($blank, [0 => 'SICODE - PROCESSO DE MEDIÇÃO PÓS OBRA']),
            $blank,
            array_map(fn ($col) => $col['title'], $this->columns),
        ];

        foreach ($this->rows as $row) {
            $out[] = $this->dataRow($row);
        }

        return $out;
    }

    /** @param array<string, mixed> $r */
    private function dataRow(array $r): array
    {
        $f = $r['fiscal'] ?? [];
        $p = $r['payment'] ?? [];
        $s = $r['stages'];

        return [
            $r['type_label'],
            $r['id'],
            $r['scopes'] ? collect($r['scopes'])->pluck('label')->implode(' / ') : ($r['type'] === 'final' ? 'Geral' : '—'),
            $r['note'] ?? null,
            $r['note_d5'] ?? null,
            $r['orders'] ? implode(', ', $r['orders']) : null,
            $r['company'] ?? null,
            $r['informer'] ?? null,
            $this->date($r['start_at']),

            $this->date($f['dispatch_at'] ?? null),
            $this->date($f['att_at'] ?? null),
            $this->date($f['completed_at'] ?? null),
            $f['user'] ?? null,
            $f['company'] ?? null,

            $this->date($p['dispatch_at'] ?? null),
            $this->date($p['att_at'] ?? null),
            $this->date($p['completed_at'] ?? null),
            $p['user'] ?? null,
            $p['company'] ?? null,

            $s['fiscal_dispatch']['days'],
            $s['fiscalization']['days'],
            $s['measurement']['days'],
            $s['total']['days'],
            self::OVERALL_LABEL[$r['overall']],
            $r['late_stages'] ? implode(', ', $r['late_stages']) : null,

            $this->date($r['decision_at'] ?? null),
            $r['engineer'] ?? null,
            $this->date($r['supervision_at'] ?? null),
            $r['supervisor'] ?? null,
            $this->date($r['payment_at'] ?? null),
            $r['payer'] ?? null,
            $this->date($r['ads_at'] ?? null),
            isset($r['ads_amount']) ? (float) $r['ads_amount'] : null,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $n = count($this->columns);
                $last = Coordinate::stringFromColumnIndex($n);
                $lastRow = max(self::HEADER_ROW, self::HEADER_ROW + $this->rows->count());
                $head = self::HEADER_ROW;
                $first = self::FIRST_DATA_ROW;

                // Título
                $sheet->mergeCells("A1:{$last}1");
                $sheet->getStyle("A1:{$last}1")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension($head)->setRowHeight(36);

                // Faixas de grupo (linha 2) e cabeçalhos (linha 3)
                $groupStart = 1;
                for ($i = 1; $i <= $n; $i++) {
                    $col = $this->columns[$i - 1];
                    $isEnd = $i === $n || $this->columns[$i]['group'] !== $col['group'];
                    if ($isEnd) {
                        $a = Coordinate::stringFromColumnIndex($groupStart);
                        $b = Coordinate::stringFromColumnIndex($i);
                        $sheet->setCellValue("{$a}2", mb_strtoupper($col['group']));
                        $sheet->mergeCells("{$a}2:{$b}2");
                        $sheet->getStyle("{$a}2:{$b}{$head}")->applyFromArray([
                            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $col['color']]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']]],
                        ]);
                        $groupStart = $i + 1;
                    }
                }
                $sheet->getStyle("A{$head}:{$last}{$head}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('FFFFFF');

                $sheet->freezePane('D' . $first);
                $sheet->setAutoFilter("A{$head}:{$last}{$lastRow}");

                if ($this->rows->isEmpty()) {
                    return;
                }

                // Corpo: bordas, zebra e alinhamento
                $body = "A{$first}:{$last}{$lastRow}";
                $sheet->getStyle($body)->applyFromArray([
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
                for ($row = $first; $row <= $lastRow; $row++) {
                    if (($row - $first) % 2 === 1) {
                        $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                    }
                }

                // Formato por coluna
                foreach ($this->columns as $i => $col) {
                    $letter = Coordinate::stringFromColumnIndex($i + 1);
                    $range = "{$letter}{$first}:{$letter}{$lastRow}";
                    $style = $sheet->getStyle($range);

                    switch (true) {
                        case $col['kind'] === 'date':
                            $style->getNumberFormat()->setFormatCode(self::DATE_FORMAT);
                            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            break;
                        case $col['kind'] === 'money':
                            $style->getNumberFormat()->setFormatCode('#,##0.00');
                            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            break;
                        case $col['kind'] === 'id':
                            $style->getNumberFormat()->setFormatCode('"#"0');
                            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $style->getFont()->setBold(true);
                            break;
                        case $col['kind'] === 'center' || $col['kind'] === 'overall' || str_starts_with($col['kind'], 'days:'):
                            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            break;
                        case $col['kind'] === 'wrap':
                            $style->getAlignment()->setWrapText(true);
                            break;
                    }

                    if (str_starts_with($col['kind'], 'days:')) {
                        $style->getNumberFormat()->setFormatCode('0');
                    }
                }

                // Cores por linha: tipo, prazos e situação geral
                $stageKeys = array_flip(['fiscal_dispatch', 'fiscalization', 'measurement', 'total']);
                foreach ($this->rows->values() as $offset => $r) {
                    $row = $first + $offset;

                    $t = self::TYPE_STYLE[$r['type']];
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $t['font']]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $t['fill']]],
                    ]);

                    foreach ($this->columns as $i => $col) {
                        if (!str_starts_with($col['kind'], 'days:')) {
                            continue;
                        }
                        $key = substr($col['kind'], 5);
                        $this->paintStatus($sheet, Coordinate::stringFromColumnIndex($i + 1) . $row, $r['stages'][$key]['status']);
                    }

                    $overallCol = Coordinate::stringFromColumnIndex(array_search('overall', array_column($this->columns, 'kind'), true) + 1);
                    $this->paintStatus($sheet, $overallCol . $row, match ($r['overall']) {
                        'late'    => 'late',
                        'on_time' => 'on_time',
                        default   => 'open',
                    });
                }

                // Impressão
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A3)->setFitToWidth(1)->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $head);
                $sheet->getSheetView()->setZoomScale(90);
                $sheet->getTabColor()->setRGB('0F766E');
            },
        ];
    }

    private function paintStatus($sheet, string $cell, string $status): void
    {
        $st = self::STATUS_STYLE[$status] ?? null;
        if (!$st) {
            return;
        }

        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => $st['bold'], 'italic' => $st['italic'], 'color' => ['rgb' => $st['font']]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $st['fill']]],
        ]);
    }

    private function date($value): ?float
    {
        return $value instanceof CarbonInterface ? Date::dateTimeToExcel($value) : null;
    }
}
