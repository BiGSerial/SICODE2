<?php

namespace App\Exports\Closure\Dashboard;

use Maatwebsite\Excel\Concerns\{FromArray, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Aba "Resumo" do Excel do Painel analítico: os indicadores em cartões (mesmos números do topo da tela, mais a sinalização por cor),
 * os filtros aplicados e o índice das abas com link para cada uma.
 */
class DashboardSummarySheet implements FromArray, WithEvents, WithTitle
{
    public const TITLE = 'Resumo';

    private const COLUMNS = 10;

    /**
     * @param array<string, mixed>                         $report ClosureDashboardReport::build()
     * @param array<int, array{title: string, description: string}> $tabs abas do arquivo (para o índice)
     */
    public function __construct(private readonly array $report, private readonly string $generatedBy, private readonly array $tabs)
    {
    }

    public function title(): string
    {
        return self::TITLE;
    }

    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->draw($event->sheet->getDelegate())];
    }

    private function draw(Worksheet $sheet): void
    {
        $r    = $this->report;
        $s    = $r['summary'];
        $g    = $r['signals'];
        $last = Coordinate::stringFromColumnIndex(self::COLUMNS);

        $sheet->getTabColor()->setRGB(DashboardTheme::INK);
        $sheet->setShowGridlines(false);

        for ($i = 1; $i <= self::COLUMNS; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(17);
        }

        // Faixa de título
        $sheet->mergeCells("A1:{$last}1");
        $sheet->setCellValue('A1', 'SICODE · Painel analítico do Encerramento');
        $sheet->getRowDimension(1)->setRowHeight(38);
        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::INK]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $sheet->mergeCells("A2:{$last}2");
        $sheet->setCellValue('A2', 'Gerado em ' . $r['generated_at']->format('d/m/Y H:i') . ' por ' . $this->generatedBy . ' · Estoque ativo do Encerramento (Ordens encerradas e canceladas ficam de fora).');
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getStyle("A2:{$last}2")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => DashboardTheme::MUTED]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::SOFT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $row = 4;

        $row = $this->section($sheet, $row, 'Estoque ativo', [
            ['Total ativo', $s['total'], 'Ordens em tratativa', DashboardTheme::INK],
            ['Passivo (P)', $s['types']['P'], 'meta vencida', DashboardTheme::RED],
            ['Meta atual (M)', $s['types']['M'], 'meta do mês', DashboardTheme::GREEN],
            ['Meta futura (F)', $s['types']['F'], 'meta do próximo mês', DashboardTheme::BLUE],
            ['Meta concluída', $s['meta_percent'] . '%', $s['meta_closed'] . ' de ' . $s['meta_total'] . ' Ordens M do ciclo', DashboardTheme::TEAL],
        ]);

        $row = $this->section($sheet, $row, 'Atividades por situação', [
            ['Aguardando parceira', $s['waiting_partner'], 'pendência com a parceira', '8A6D00'],
            ['Pausadas', $s['paused'], 'com motivo registrado', DashboardTheme::INK],
            ['Finalizadas', $s['finished'], 'aguardam o SAP', DashboardTheme::GREEN],
            ['Aguardando a meta', $s['waiting'], 'finalizadas antes do mês da meta', DashboardTheme::BLUE],
            ['Prioridades pendentes', $r['priorities_count'], 'pedidos sem decisão', DashboardTheme::RED],
        ]);

        $row = $this->section($sheet, $row, 'Sinalização das Ordens (mesma regra e cores da tela)', [
            ['Sem baixa no SAP', $g['overdue'], 'finalizadas há +24h, SAP aberto', DashboardTheme::RED],
            ['Concluídas', $g['done'], 'finalizada, aguardando a meta ou encerrada', DashboardTheme::GREEN],
            ['Atribuídas', $g['assigned'], 'com responsável e em tratativa', DashboardTheme::BLUE],
            ['Na pilha, sem atribuição', $g['pile'], 'de alguém e sem responsável', '8A6D00'],
            ['Obras críticas', $r['critical_count'], 'passivo ou pendência parada', DashboardTheme::RED],
        ]);

        $row = $this->keyValues($sheet, $row, 'Filtros aplicados', $r['filters']);
        $this->index($sheet, $row);

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
    }

    /**
     * Seção com um título e uma fila de cartões (cada cartão ocupa 2 colunas × 3 linhas).
     *
     * @param array<int, array{0: string, 1: mixed, 2: string, 3: string}> $tiles
     */
    private function section(Worksheet $sheet, int $row, string $title, array $tiles): int
    {
        $last = Coordinate::stringFromColumnIndex(self::COLUMNS);

        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", mb_strtoupper($title));
        $sheet->getRowDimension($row)->setRowHeight(24);
        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => DashboardTheme::TEAL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => DashboardTheme::TEAL]]],
        ]);

        $top = $row + 1;

        $sheet->getRowDimension($top)->setRowHeight(20);
        $sheet->getRowDimension($top + 1)->setRowHeight(36);
        $sheet->getRowDimension($top + 2)->setRowHeight(20);

        foreach ($tiles as $index => [$label, $value, $note, $color]) {
            $from = Coordinate::stringFromColumnIndex($index * 2 + 1);
            $to   = Coordinate::stringFromColumnIndex($index * 2 + 2);

            foreach ([[$top, $label, 9, false, DashboardTheme::MUTED], [$top + 1, $value, 22, true, $color], [$top + 2, $note, 8, false, DashboardTheme::MUTED]] as [$r, $text, $size, $bold, $rgb]) {
                $sheet->mergeCells("{$from}{$r}:{$to}{$r}");
                $sheet->setCellValue("{$from}{$r}", $text);
                $sheet->getStyle("{$from}{$r}:{$to}{$r}")->applyFromArray([
                    'font'      => ['bold' => $bold || $r === $top, 'size' => $size, 'color' => ['rgb' => $rgb]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
            }

            // O cartão: fundo claro, filete de cor no topo e moldura suave.
            $sheet->getStyle("{$from}{$top}:{$to}" . ($top + 2))->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders' => [
                    'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => DashboardTheme::LINE]],
                    'top'     => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => $color]],
                ],
            ]);
        }

        return $top + 4;
    }

    /** @param array<string, string> $pairs */
    private function keyValues(Worksheet $sheet, int $row, string $title, array $pairs): int
    {
        $last = Coordinate::stringFromColumnIndex(self::COLUMNS);

        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", mb_strtoupper($title));
        $sheet->getRowDimension($row)->setRowHeight(24);
        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => DashboardTheme::TEAL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => DashboardTheme::TEAL]]],
        ]);

        $row++;

        foreach ($pairs as $key => $value) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->mergeCells("D{$row}:H{$row}");
            $sheet->setCellValue("A{$row}", $key);
            $sheet->setCellValue("D{$row}", $value);
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'font'    => ['size' => 10, 'color' => ['rgb' => DashboardTheme::INK]],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => DashboardTheme::LINE]]],
            ]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:H{$row}")->getAlignment()->setIndent(1);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $row++;
        }

        return $row + 1;
    }

    private function index(Worksheet $sheet, int $row): void
    {
        $last = Coordinate::stringFromColumnIndex(self::COLUMNS);

        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", 'ABAS DESTA PLANILHA (CLIQUE PARA IR)');
        $sheet->getRowDimension($row)->setRowHeight(24);
        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => DashboardTheme::TEAL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM, 'indent' => 1],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => DashboardTheme::TEAL]]],
        ]);

        $row++;

        foreach ($this->tabs as $tab) {
            $quoted = "'" . str_replace("'", "''", $tab['title']) . "'";

            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->mergeCells("D{$row}:{$last}{$row}");
            $sheet->setCellValue("A{$row}", $tab['title']);
            $sheet->getCell("A{$row}")->getHyperlink()->setUrl("sheet://{$quoted}!A1");
            $sheet->setCellValue("D{$row}", $tab['description']);
            $sheet->getStyle("A{$row}")->applyFromArray(['font' => ['bold' => true, 'underline' => 'single', 'color' => ['rgb' => DashboardTheme::BLUE]]]);
            $sheet->getStyle("D{$row}")->applyFromArray(['font' => ['size' => 10, 'color' => ['rgb' => DashboardTheme::MUTED]]]);
            $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray(['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => DashboardTheme::LINE]]]]);
            $sheet->getStyle("A{$row}:{$last}{$row}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

            if (mb_strlen($tab['description']) > 100) {
                $sheet->getRowDimension($row)->setRowHeight(34);
            }

            $row++;
        }
    }
}
