<?php

namespace App\Exports\Home\MyPanel;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Services\Home\MyPanel\MyPanelAnalytics as Panel;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel do "Meu painel": uma aba por bloco da tela (mais as listas completas) no mesmo visual do Excel do Encerramento.
 * Abas: Resumo · Abertas · Abertas por atividade · Produtividade · Etapas · Diário · Mensal · Concluídas.
 * Dados vêm de Panel::report(); este arquivo só formata. Datas são datas de verdade (ordenam/filtram no Excel) e tempos vão em dias.
 */
class MyPanelExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Meu painel';

    private const DATE = 'dd/mm/yyyy';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    /** Dias com 1 casa; zero vira traço. */
    private const DAYS = '#,##0.0;-#,##0.0;"–"';

    private const DAYS_INT = '#,##0;-#,##0;"–"';

    /** Tempos de etapa podem ser de poucas horas (0,03 dia): duas casas para não virarem traço. */
    private const DAYS2 = '#,##0.00;-#,##0.00;"–"';

    /** @var array<string, string> descrição de cada aba (índice da aba Resumo) */
    private array $descriptions = [];

    /** @param array<string, mixed> $report MyPanelAnalytics::report() */
    public function __construct(private readonly array $report, private readonly string $userName, private readonly ?string $serviceLabel = null)
    {
    }

    public function sheets(): array
    {
        $generated = 'Gerado em ' . $this->report['generated_at']->format('d/m/Y H:i');
        $tables    = $this->tables($generated);
        $tabs      = array_map(fn (DashboardTableSheet $sheet) => ['title' => $sheet->title(), 'description' => $this->descriptions[$sheet->title()] ?? ''], $tables);

        return array_merge([new MyPanelSummarySheet($this->report, $this->userName, $this->filterLabels(), $tabs)], $tables);
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->userName,
            'title'          => 'Meu painel',
            'description'    => 'Atividades abertas por tempo, produtividade por atividade, etapas, série diária e mensal e lista de concluídas',
            'subject'        => 'Painel pessoal',
        ];
    }

    /** @return array<string, string> */
    public function filterLabels(): array
    {
        $f = $this->report['filters'];

        return [
            'Usuário'              => $this->userName,
            'Atividade'            => $f['service'] ? ($this->serviceLabel ?? 'Atividade selecionada') : 'Todas as atividades',
            'Período (concluídas)' => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y') . ' (' . $f['days'] . ' dias)',
            'Período anterior'     => $f['prev_from']->format('d/m/Y') . ' a ' . $f['prev_to']->format('d/m/Y'),
            'Incluir RI'           => $f['include_ri'] ? 'Sim' : 'Não',
            'Tempo das abertas'    => 'Desde a ATT; Fiscalização, Medição e Publicação contam desde o informe da Nota',
        ];
    }

    /** @return array<int, DashboardTableSheet> */
    private function tables(string $generated): array
    {
        $r      = $this->report;
        $sheets = [];

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => self::BRAND, 'description' => $description, 'generated' => $generated], $options));
        };

        $this->openSheets($add, $r);
        $this->productivitySheets($add, $r);
        $this->seriesSheets($add, $r);

        $completed = $r['completed'];
        $add(
            'Concluídas',
            count($completed) . ' atividade(s) concluída(s) no período, da mais antiga para a mais recente conclusão (a tela só resume).',
            ['Nota', 'Atividade', 'Grupo', 'RI', 'Despacho', 'ATT', 'Informe', 'Conclusão', 'Tempo total (dias)', 'Tempo parado (dias)', 'Postes/ativos', 'Situação', 'Conclusão da análise'],
            array_map(fn (array $c) => [
                $c['note'], $c['service'], Panel::GROUP_LABELS[$c['group']], $c['ri'] ? 'RI' : '',
                $this->date($c['dispatch_at']), $this->date($c['att_at']), $this->date($c['informed_at']), $this->date($c['completed_at']),
                $this->days($c['total_seconds']), $this->days($c['stopped']), $c['postes'], $c['status'], $c['conclusion'] ?? '',
            ], $completed),
            [
                'tab'      => DashboardTheme::TEAL, 'center' => ['A', 'D', 'E', 'F', 'G', 'H', 'K'],
                'formats'  => ['E' => self::DATETIME, 'F' => self::DATETIME, 'G' => self::DATETIME, 'H' => self::DATETIME, 'I' => self::DAYS2, 'J' => self::DAYS2, 'K' => DashboardTableSheet::INT],
                'dataBars' => ['I' => DashboardTheme::BLUE, 'K' => DashboardTheme::GREEN],
                'widths'   => ['A' => 16, 'B' => 22, 'C' => 13, 'D' => 6, 'E' => 17, 'F' => 17, 'G' => 17, 'H' => 17, 'I' => 14, 'J' => 14, 'K' => 13, 'L' => 20, 'M' => 34],
            ],
        );

        return $sheets;
    }

    /** @param array<string, mixed> $r */
    private function openSheets(callable $add, array $r): void
    {
        $open = $r['open'];

        $add(
            'Abertas',
            count($open) . ' atividade(s) aberta(s), da mais antiga para a mais recente. O tempo conta desde a ATT; pós-obra (Fiscalização, Medição, Publicação) desde o informe.',
            ['#', 'Atividade', 'Grupo', 'Nota', 'Situação', 'RI', 'ATT', 'Informe', 'Contado desde', 'Início da contagem', 'Dias em aberto'],
            array_map(fn (array $o, int $i) => [
                $i + 1, $o['service'], Panel::GROUP_LABELS[$o['group']], $o['note'], $o['status'], $o['ri'] ? 'RI' : '',
                $this->date($o['att_at']), $this->date($o['informed_at']), $o['basis'] ?? '', $this->date($o['since']), $o['age_days'],
            ], $open, array_keys($open)),
            [
                'tab'      => DashboardTheme::RED, 'center' => ['A', 'D', 'F', 'G', 'H', 'I', 'J', 'K'],
                'formats'  => ['G' => self::DATETIME, 'H' => self::DATETIME, 'J' => self::DATETIME, 'K' => self::DAYS_INT],
                'dataBars' => ['K' => DashboardTheme::RED],
                'widths'   => ['A' => 6, 'B' => 22, 'C' => 13, 'D' => 16, 'E' => 20, 'F' => 6, 'G' => 17, 'H' => 17, 'I' => 14, 'J' => 17, 'K' => 14],
            ],
        );

        $byActivity = $r['open_by'];
        $add(
            'Abertas por atividade',
            'Onde está o trabalho e há quanto tempo: quantidade, mais antiga e idade média por atividade (dias corridos).',
            ['Atividade', 'Grupo', 'Abertas', 'Das quais RI', 'Mais antiga (dias)', 'Idade média (dias)'],
            array_map(fn (array $a) => [$a['service'], Panel::GROUP_LABELS[$a['group']], $a['total'], $a['ri'], $a['oldest'], $a['average']], $byActivity),
            [
                'tab'      => DashboardTheme::BLUE, 'center' => ['B', 'C', 'D', 'E', 'F'],
                'formats'  => ['C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => self::DAYS_INT, 'F' => self::DAYS],
                'dataBars' => ['C' => DashboardTheme::BLUE, 'E' => DashboardTheme::RED],
                'widths'   => ['A' => 26],
                'chart'    => ['type' => 'stackedBar', 'title' => 'Atividades abertas por atividade', 'category' => 'A', 'series' => ['C'], 'colors' => [DashboardTheme::BLUE], 'rows' => count($byActivity), 'from' => 'H4', 'to' => 'Q22'],
            ],
        );

        $status = $r['status'];
        $add(
            'Por situação',
            'Distribuição das atividades abertas pela situação atual.',
            ['Situação', 'Abertas', '% das abertas'],
            array_map(fn (array $s) => [$s['label'], $s['total'], $r['summary']['open'] > 0 ? round($s['total'] / $r['summary']['open'] * 100, 1) : 0], $status),
            [
                'tab'      => DashboardTheme::AMBER, 'center' => ['B'], 'formats' => ['B' => DashboardTableSheet::INT, 'C' => '0.0"%"'],
                'dataBars' => ['B' => DashboardTheme::AMBER],
                'chart'    => ['type' => 'doughnut', 'title' => 'Abertas por situação', 'category' => 'A', 'series' => ['B'], 'colors' => ['263CC8', 'F7D200', 'E32C2C', '0F8A77', '94A3B8', '155F67'], 'rows' => count($status), 'from' => 'E4', 'to' => 'L20'],
            ],
        );
    }

    /** @param array<string, mixed> $r */
    private function productivitySheets(callable $add, array $r): void
    {
        $acts  = $r['activities'];
        $sum   = $r['summary'];
        $total = [
            'TOTAL', '', $sum['done'], $sum['done'] > 0 ? 100.0 : 0, $sum['done_postes'],
        ];

        $add(
            'Produtividade',
            'Concluídas no período por atividade: volume, ritmo (notas por dia trabalhado) e tempos médios (dias corridos).',
            ['Atividade', 'Grupo', 'Notas', '% das notas', 'Postes/ativos', 'Dias trabalhados', 'Notas/dia', 'Tempo médio total (dias)', 'Fila média (dias)', 'Execução média (dias)', 'Parada média (dias)'],
            array_merge(
                array_map(fn (array $a) => [
                    $a['service'], Panel::GROUP_LABELS[$a['group']], $a['notas'], $a['share'], $a['postes'], $a['dias'], $a['per_day'],
                    $this->days($a['avg_total']), $this->days($a['avg_queue']), $this->days($a['avg_exec']), $this->days($a['avg_stopped']),
                ], $acts),
                [array_pad($total, 11, '')],
            ),
            [
                'tab'      => DashboardTheme::GREEN, 'totalRows' => [count($acts)], 'center' => ['B', 'C', 'D', 'E', 'F', 'G'],
                'formats'  => ['C' => DashboardTableSheet::INT, 'D' => '0.0"%"', 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => '0.0', 'H' => self::DAYS2, 'I' => self::DAYS2, 'J' => self::DAYS2, 'K' => self::DAYS2],
                'dataBars' => ['C' => DashboardTheme::GREEN],
                'widths'   => ['A' => 26, 'B' => 13, 'H' => 14],
                'chart'    => ['type' => 'columns', 'title' => 'Notas concluídas por atividade', 'category' => 'A', 'series' => ['C'], 'colors' => [DashboardTheme::GREEN], 'rows' => count($acts), 'from' => 'A' . (DashboardTableSheet::FIRST_DATA_ROW + count($acts) + 3), 'to' => 'H' . (DashboardTableSheet::FIRST_DATA_ROW + count($acts) + 21)],
            ],
        );

        $stages = $r['stages'];
        $add(
            'Etapas',
            'Quanto tempo cada etapa leva, em média, nas concluídas do período (dias corridos). Fila = despacho até a ATT; execução já desconta as pausas.',
            ['Etapa', 'Tempo médio (dias)', 'Leitura'],
            array_map(fn (array $s) => [$s['label'], $this->days($s['seconds']), Panel::duration($s['seconds'])], $stages),
            [
                'tab'    => DashboardTheme::TEAL, 'center' => ['B', 'C'], 'formats' => ['B' => self::DAYS2], 'dataBars' => ['B' => DashboardTheme::TEAL],
                'widths' => ['A' => 38, 'B' => 18, 'C' => 14],
                'chart'  => ['type' => 'stackedBar', 'title' => 'Tempo médio por etapa (dias)', 'category' => 'A', 'series' => ['B'], 'colors' => [DashboardTheme::TEAL], 'rows' => count($stages), 'from' => 'E4', 'to' => 'N18'],
            ],
        );
    }

    /** @param array<string, mixed> $r */
    private function seriesSheets(callable $add, array $r): void
    {
        $d       = $r['daily'];
        $balance = 0;
        $rows    = [];

        foreach ($d['dates'] as $i => $date) {
            $balance += $d['received'][$i] - $d['completed'][$i];
            $rows[] = [Date::dateTimeToExcel(Carbon::parse($date)), $d['received'][$i], $d['completed'][$i], $d['postes'][$i], $balance];
        }

        $add(
            'Diário',
            'Recebidas (ATT) e concluídas por dia no período, com o saldo acumulado (recebidas − concluídas).',
            ['Dia', 'Recebidas (ATT)', 'Concluídas', 'Postes/ativos', 'Saldo acumulado'],
            $rows,
            [
                'tab'      => DashboardTheme::BLUE, 'center' => ['A', 'B', 'C', 'D'],
                'formats'  => ['A' => 'ddd dd/mm/yyyy', 'B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => '+#,##0;-#,##0;"–"'],
                'dataBars' => ['C' => DashboardTheme::GREEN],
                'widths'   => ['A' => 20],
                'chart'    => ['type' => 'line', 'title' => 'Recebidas × concluídas por dia', 'category' => 'A', 'series' => ['B', 'C'], 'colors' => [DashboardTheme::BLUE, DashboardTheme::TEAL], 'rows' => count($rows), 'from' => 'G4', 'to' => 'Q22'],
            ],
        );

        $m     = $r['monthly'];
        $mrows = [];

        foreach ($m['months'] as $i => $month) {
            $parts   = array_map(fn (string $g) => $m['series'][$g][$i], array_keys(Panel::GROUP_LABELS));
            $mrows[] = array_merge([Carbon::parse($month . '-01')->translatedFormat('M/Y')], $parts, [array_sum($parts), $m['postes'][$i]]);
        }

        $add(
            'Mensal',
            'Notas concluídas por mês e grupo de atividade, nos 12 meses até o fim do período.',
            array_merge(['Mês'], array_values(Panel::GROUP_LABELS), ['Total de notas', 'Postes/ativos']),
            $mrows,
            [
                'tab'      => DashboardTheme::TEAL, 'center' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => DashboardTableSheet::INT],
                'dataBars' => ['F' => DashboardTheme::TEAL],
                'chart'    => ['type' => 'stackedBar', 'title' => 'Notas por mês e grupo', 'category' => 'A', 'series' => ['B', 'C', 'D', 'E'], 'colors' => [DashboardTheme::BLUE, '6577DC', 'A8B1E9', 'CBD5E1'], 'rows' => count($mrows), 'from' => 'I4', 'to' => 'S22'],
            ],
        );
    }

    private function date(?Carbon $date): ?float
    {
        return $date ? Date::dateTimeToExcel($date) : null;
    }

    private function days(?float $seconds): ?float
    {
        return $seconds === null ? null : round($seconds / 86400, 2);
    }
}
