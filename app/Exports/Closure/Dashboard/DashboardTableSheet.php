<?php

namespace App\Exports\Closure\Dashboard;

use Maatwebsite\Excel\Concerns\{FromArray, WithCharts, WithEvents, WithStrictNullComparison, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\{Chart, DataSeries, DataSeriesValues, Legend, PlotArea, Title};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Conditional, Fill};
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\{ConditionalDataBar, ConditionalFormatValueObject};

/**
 * Aba de tabela do Painel analítico, no visual do módulo: faixa de título, descrição, cabeçalho escuro, zebra, filtro, painel congelado,
 * cor da aba, barras de dados nas colunas de volume, linhas coloridas pelo sinal (como na tela) e gráfico nativo opcional.
 *
 * Opções (todas opcionais):
 *  - brand: prefixo do título (padrão "SICODE · Encerramento"); - description: texto sob o título;  - generated: "Gerado em …" (vai na linha da descrição);
 *  - tab: RGB da aba;                           - formats: [coluna => código de formato] (ex.: 'F' => '#,##0');
 *  - center: [colunas centralizadas];           - wrap: [colunas com quebra de linha];  - widths: [coluna => largura];
 *  - totalRows: [índices (0-base, dentro de $rows) de linhas de total];
 *  - rowFills: [índice => RGB do tom da linha]; - rowEdges: [índice => RGB da borda lateral];
 *  - dataBars: [coluna => RGB da barra];        - chart: [type: stackedBar|columns|line|doughnut, title, category: coluna, series: [colunas], colors: [RGB],
 *    rows: quantidade de linhas do gráfico, from/to: âncoras (ex. 'J4' / 'S22')].
 */
class DashboardTableSheet implements FromArray, WithCharts, WithEvents, WithStrictNullComparison, WithTitle
{
    /** Inteiro com milhar; o zero aparece como traço (mais limpo para análise) e continua sendo o valor 0 na célula. */
    public const INT = '#,##0;-#,##0;"–"';

    public const HEADER_ROW = 4;

    public const FIRST_DATA_ROW = 5;

    /**
     * @param array<int, string>             $headings
     * @param array<int, array<int, mixed>>  $rows
     * @param array<string, mixed>           $options
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $options = [],
    ) {
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function array(): array
    {
        $blank = array_fill(0, count($this->headings), null);

        return array_merge(
            [
                array_replace($blank, [0 => ($this->options['brand'] ?? 'SICODE · Encerramento') . ' · ' . $this->sheetTitle]),
                array_replace($blank, [0 => trim(($this->options['description'] ?? '') . (isset($this->options['generated']) ? '   ·   ' . $this->options['generated'] : ''))]),
                $blank,
                array_values($this->headings),
            ],
            array_map('array_values', $this->rows),
        );
    }

    public function charts(): array
    {
        $chart = $this->options['chart'] ?? null;

        if (!$chart || ($chart['rows'] ?? 0) < 1) {
            return [];
        }

        $first = self::FIRST_DATA_ROW;
        $last  = self::FIRST_DATA_ROW + $chart['rows'] - 1;
        $ref   = fn (string $col, int $from, int $to) => $this->sheetRef($col, $from, $to);
        $count = $chart['rows'];

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $ref($chart['category'], $first, $last), null, $count)];
        $names      = [];
        $values     = [];

        foreach ($chart['series'] as $index => $col) {
            $names[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $ref($col, self::HEADER_ROW, self::HEADER_ROW), null, 1);
            $series  = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $ref($col, $first, $last), null, $count);

            if ($chart['type'] === 'doughnut') {
                $series->setFillColor($chart['colors']);
            } else {
                $series->setFillColor($chart['colors'][$index] ?? DashboardTheme::TEAL);
            }

            $values[] = $series;
        }

        $order = range(0, count($values) - 1);

        $dataSeries = match ($chart['type']) {
            'stackedBar' => new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_STACKED, $order, $names, $categories, $values),
            'columns'    => new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, $order, $names, $categories, $values),
            'line'       => new DataSeries(DataSeries::TYPE_LINECHART, DataSeries::GROUPING_STANDARD, $order, $names, $categories, $values),
            default      => new DataSeries(DataSeries::TYPE_DONUTCHART, null, $order, $names, $categories, $values),
        };

        if ($chart['type'] === 'stackedBar') {
            $dataSeries->setPlotDirection(DataSeries::DIRECTION_BAR);
        } elseif ($chart['type'] === 'columns') {
            $dataSeries->setPlotDirection(DataSeries::DIRECTION_COL);
        }

        $object = new Chart(
            'chart_' . md5($this->sheetTitle),
            new Title($chart['title']),
            new Legend($chart['type'] === 'doughnut' ? Legend::POSITION_RIGHT : Legend::POSITION_BOTTOM, null, false),
            new PlotArea(null, [$dataSeries]),
        );

        $object->setTopLeftPosition($chart['from']);
        $object->setBottomRightPosition($chart['to']);

        return [$object];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->style($event->sheet->getDelegate())];
    }

    private function style(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $columns = max(1, count($this->headings));
        $lastCol = Coordinate::stringFromColumnIndex($columns);
        $first   = self::FIRST_DATA_ROW;
        $last    = max($first, $first + count($this->rows) - 1);
        $hasRows = $this->rows !== [];

        $sheet->getTabColor()->setRGB($this->options['tab'] ?? DashboardTheme::TEAL);
        $sheet->setShowGridlines(false);
        $sheet->getDefaultRowDimension()->setRowHeight(20);

        // Título e descrição
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getRowDimension(1)->setRowHeight(32);
        // Descrição longa quebra em duas linhas em vez de ser cortada na borda da tabela.
        $descriptionLength = mb_strlen((string) ($this->options['description'] ?? '') . (string) ($this->options['generated'] ?? ''));
        $sheet->getRowDimension(2)->setRowHeight($descriptionLength > 120 ? 36 : 22);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(3)->setRowHeight(8);
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(28);

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::TEAL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => DashboardTheme::MUTED]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::SOFT]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        // Cabeçalho
        $headerRange = 'A' . self::HEADER_ROW . ":{$lastCol}" . self::HEADER_ROW;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::INK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => DashboardTheme::TEAL]]],
        ]);

        $sheet->freezePane('A' . $first);

        if ($hasRows) {
            $sheet->setAutoFilter($headerRange);
            $this->styleRows($sheet, $lastCol, $first, $last);
            $this->styleColumns($sheet, $first, $last);
            $this->addDataBars($sheet, $first, $last);
        } else {
            $sheet->mergeCells("A{$first}:{$lastCol}{$first}");
            $sheet->setCellValue("A{$first}", 'Nenhum registro para os filtros aplicados.');
            $sheet->getStyle("A{$first}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => DashboardTheme::MUTED]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getRowDimension($first)->setRowHeight(34);
        }

        $this->sizeColumns($sheet, $columns);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getSheetView()->setZoomScale(100);
    }

    private function styleRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $lastCol, int $first, int $last): void
    {
        $fills  = $this->options['rowFills'] ?? [];
        $edges  = $this->options['rowEdges'] ?? [];
        $totals = array_flip($this->options['totalRows'] ?? []);

        $sheet->getStyle("A{$first}:{$lastCol}{$last}")->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['rgb' => DashboardTheme::INK]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => DashboardTheme::LINE]]],
        ]);

        foreach (array_keys($this->rows) as $index) {
            $row  = $first + $index;
            $line = "A{$row}:{$lastCol}{$row}";

            if (isset($totals[$index])) {
                $sheet->getStyle($line)->applyFromArray([
                    'font'    => ['bold' => true],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => DashboardTheme::INK]]],
                ]);

                continue;
            }

            if (isset($fills[$index])) {
                // Linha colorida pelo sinal: tom da linha + divisória mais escura + borda lateral (a mesma ideia da tela).
                $edge = $edges[$index] ?? DashboardTheme::LINE;

                $sheet->getStyle($line)->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fills[$index]]],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $edge]]],
                ]);
                $sheet->getStyle("A{$row}")->applyFromArray(['borders' => ['left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => $edge]]]]);
            } elseif ($index % 2 === 1) {
                $sheet->getStyle($line)->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]]);
            }
        }
    }

    private function styleColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $first, int $last): void
    {
        foreach ($this->options['formats'] ?? [] as $col => $format) {
            $sheet->getStyle("{$col}{$first}:{$col}{$last}")->getNumberFormat()->setFormatCode($format);
            $sheet->getStyle("{$col}{$first}:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach ($this->options['center'] ?? [] as $col) {
            $sheet->getStyle("{$col}{$first}:{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach ($this->options['wrap'] ?? [] as $col) {
            $sheet->getStyle("{$col}{$first}:{$col}{$last}")->getAlignment()->setWrapText(true);
        }
    }

    /** Barras de dados (estilo "volume de relance") nas colunas pedidas. */
    private function addDataBars(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $first, int $last): void
    {
        $totals = array_flip($this->options['totalRows'] ?? []);
        $end    = $last;

        // As barras não incluem a(s) linha(s) de total no fim (distorceriam a escala).
        while ($end > $first && isset($totals[$end - $first])) {
            $end--;
        }

        foreach ($this->options['dataBars'] ?? [] as $col => $rgb) {
            $bar = (new ConditionalDataBar())
                ->setMinimumConditionalFormatValueObject(new ConditionalFormatValueObject('num', 0))
                ->setMaximumConditionalFormatValueObject(new ConditionalFormatValueObject('max'))
                ->setColor('FF' . $rgb);

            $conditional = new Conditional();
            $conditional->setConditionType(Conditional::CONDITION_DATABAR);
            $conditional->setDataBar($bar);

            $sheet->getStyle("{$col}{$first}:{$col}{$end}")->setConditionalStyles([$conditional]);
        }
    }

    private function sizeColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $columns): void
    {
        $widths = $this->options['widths'] ?? [];

        for ($i = 1; $i <= $columns; $i++) {
            $col     = Coordinate::stringFromColumnIndex($i);
            $longest = mb_strlen((string) ($this->headings[$i - 1] ?? ''));

            foreach (array_slice($this->rows, 0, 300) as $row) {
                $longest = max($longest, mb_strlen((string) ($row[$i - 1] ?? '')));
            }

            $sheet->getColumnDimension($col)->setWidth($widths[$col] ?? min(46, max(13, $longest * 1.1 + 3)));
        }
    }

    private function sheetRef(string $col, int $from, int $to): string
    {
        $title = "'" . str_replace("'", "''", $this->title()) . "'";

        return $from === $to ? "{$title}!\${$col}\${$from}" : "{$title}!\${$col}\${$from}:\${$col}\${$to}";
    }
}
