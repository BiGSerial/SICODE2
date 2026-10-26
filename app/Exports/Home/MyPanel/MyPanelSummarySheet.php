<?php

namespace App\Exports\Home\MyPanel;

use App\Exports\Closure\Dashboard\DashboardTheme;
use App\Services\Home\MyPanel\MyPanelAnalytics as Panel;
use Maatwebsite\Excel\Concerns\{FromArray, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{PageSetup, Worksheet};

/**
 * Aba "Resumo" do Excel do Meu painel: os indicadores em cartões (os mesmos números do topo da tela), os filtros aplicados
 * e o índice das abas com link. Mesmo desenho da aba Resumo do Painel analítico do Encerramento.
 */
class MyPanelSummarySheet implements FromArray, WithEvents, WithTitle
{
    public const TITLE = 'Resumo';

    private const COLUMNS = 10;

    /**
     * @param array<string, mixed>                                   $report  MyPanelAnalytics::report()
     * @param array<string, string>                                  $filters rótulo => valor dos filtros aplicados
     * @param array<int, array{title: string, description: string}> $tabs    abas do arquivo (índice)
     */
    public function __construct(private readonly array $report, private readonly string $userName, private readonly array $filters, private readonly array $tabs)
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
        $last = Coordinate::stringFromColumnIndex(self::COLUMNS);
        $dec  = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
        $pct  = fn (?float $v) => $v === null ? 'sem período anterior' : ($v > 0 ? '+' : '') . $dec($v) . '% vs. período anterior';

        $sheet->getTabColor()->setRGB(DashboardTheme::INK);
        $sheet->setShowGridlines(false);

        for ($i = 1; $i <= self::COLUMNS; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(17);
        }

        $sheet->mergeCells("A1:{$last}1");
        $sheet->setCellValue('A1', 'SICODE · Meu painel — ' . $this->userName);
        $sheet->getRowDimension(1)->setRowHeight(38);
        $sheet->getStyle("A1:{$last}1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::INK]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $sheet->mergeCells("A2:{$last}2");
        $sheet->setCellValue('A2', 'Gerado em ' . $r['generated_at']->format('d/m/Y H:i') . ' · Atividades abertas (situação de agora) e produtividade do período selecionado.');
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getStyle("A2:{$last}2")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => DashboardTheme::MUTED]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::SOFT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $row = 4;

        $row = $this->section($sheet, $row, 'Atividades abertas (agora)', [
            ['Abertas', $s['open'], 'em ' . $s['open_services'] . ' atividade(s)', DashboardTheme::BLUE],
            ['Em andamento', $s['running'], 'em execução', DashboardTheme::GREEN],
            ['Em pausa', $s['paused'], 'com motivo registrado', '8A6D00'],
            ['Mais antiga', $s['oldest'] === null ? '—' : $s['oldest'] . ' d', ($r['next']['service'] ?? '—') . ($r['next'] ? ' · ' . $r['next']['note'] : ''), DashboardTheme::RED],
            ['Idade média', $s['average'] === null ? '—' : $dec($s['average']) . ' d', $s['ri'] . ' RI entre as abertas', DashboardTheme::INK],
        ]);

        $perDay = $r['filters']['days'] > 0 ? $s['done'] / $r['filters']['days'] : 0;

        $row = $this->section($sheet, $row, 'Produção do período', [
            ['Concluídas', $s['done'], $pct($s['done_delta']), DashboardTheme::GREEN],
            ['Postes/ativos', $s['done_postes'], $pct($s['postes_delta']), DashboardTheme::TEAL],
            ['Concluídas hoje', $s['today'], $s['today_postes'] . ' postes/ativos', DashboardTheme::BLUE],
            ['Ritmo', $dec($perDay), 'notas por dia corrido', DashboardTheme::INK],
            ['Tempo médio', Panel::duration($r['stages'][3]['seconds']), 'do início até concluir', DashboardTheme::TEAL],
        ]);

        $row = $this->keyValues($sheet, $row, 'Filtros aplicados', $this->filters);
        $this->index($sheet, $row);

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
    }

    private function heading(Worksheet $sheet, int $row, string $title): void
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
    }

    /**
     * Seção com título e uma fila de cartões (cada cartão ocupa 2 colunas × 3 linhas).
     *
     * @param array<int, array{0: string, 1: mixed, 2: string, 3: string}> $tiles
     */
    private function section(Worksheet $sheet, int $row, string $title, array $tiles): int
    {
        $this->heading($sheet, $row, $title);
        $top = $row + 1;

        $sheet->getRowDimension($top)->setRowHeight(20);
        $sheet->getRowDimension($top + 1)->setRowHeight(36);
        $sheet->getRowDimension($top + 2)->setRowHeight(30);

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
        $this->heading($sheet, $row, $title);
        $row++;

        foreach ($pairs as $key => $value) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->mergeCells("D{$row}:J{$row}");
            $sheet->setCellValue("A{$row}", $key);
            $sheet->setCellValue("D{$row}", $value);
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'font'    => ['size' => 10, 'color' => ['rgb' => DashboardTheme::INK]],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => DashboardTheme::LINE]]],
            ]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:J{$row}")->getAlignment()->setIndent(1);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $row++;
        }

        return $row + 1;
    }

    private function index(Worksheet $sheet, int $row): void
    {
        $last = Coordinate::stringFromColumnIndex(self::COLUMNS);

        $this->heading($sheet, $row, 'Abas desta planilha (clique para ir)');
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
