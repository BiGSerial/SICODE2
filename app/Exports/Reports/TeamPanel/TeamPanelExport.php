<?php

namespace App\Exports\Reports\TeamPanel;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use App\Services\Home\MyPanel\MyPanelAnalytics as Panel;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel do "Painel da equipe": uma aba por bloco da tela (mais as listas completas) no mesmo visual do Excel do Meu painel e do Encerramento.
 * Abas: Resumo · Pessoas · Empresas · Produtividade · Abertas · Abertas por pessoa · Abertas por atividade · Por situação · Etapas · Diário · Mensal · Pessoa × Atividade · Concluídas.
 * Dados vêm de TeamPanelAnalytics::report(); este arquivo só formata. Datas são datas de verdade e tempos vão em dias.
 */
class TeamPanelExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Painel da equipe';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    private const DAYS = '#,##0.0;-#,##0.0;"–"';

    private const DAYS_INT = '#,##0;-#,##0;"–"';

    private const DAYS2 = '#,##0.00;-#,##0.00;"–"';

    private const PCT = '+0.0"%";-0.0"%";"–"';

    /** @var array<string, string> descrição de cada aba (índice da aba Resumo) */
    private array $descriptions = [];

    /**
     * @param array<string, mixed>  $report  TeamPanelAnalytics::report()
     * @param array<string, string> $labels  nomes dos filtros escolhidos (empresa, atividade, pessoa)
     */
    public function __construct(private readonly array $report, private readonly string $requester, private readonly array $labels = [])
    {
    }

    public function sheets(): array
    {
        $generated = 'Gerado em ' . $this->report['generated_at']->format('d/m/Y H:i');
        $tables    = $this->tables($generated);
        $tabs      = array_map(fn (DashboardTableSheet $sheet) => ['title' => $sheet->title(), 'description' => $this->descriptions[$sheet->title()] ?? ''], $tables);

        return array_merge([new MyPanelSummarySheet($this->report, $this->requester, $this->filterLabels(), $tabs, $this->summaryLayout())], $tables);
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->requester,
            'title'          => 'Painel da equipe',
            'description'    => 'Atividades abertas por pessoa e tempo, produtividade por pessoa, empresa e atividade, série diária e mensal e lista de concluídas',
            'subject'        => 'Painel da gerência',
        ];
    }

    /** @return array<string, string> */
    public function filterLabels(): array
    {
        $f = $this->report['filters'];

        return [
            'Solicitado por'       => $this->requester,
            'Empresa'              => $this->labels['company'] ?? 'Todas as empresas',
            'Atividade'            => $this->labels['service'] ?? 'Todas as atividades',
            'Pessoa'               => $this->labels['user'] ?? 'Toda a equipe',
            'Período (concluídas)' => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y') . ' (' . $f['days'] . ' dias)',
            'Período anterior'     => $f['prev_from']->format('d/m/Y') . ' a ' . $f['prev_to']->format('d/m/Y'),
            'Incluir RI'           => $f['include_ri'] ? 'Sim' : 'Não',
            'Tempo das abertas'    => 'Desde a ATT; Fiscalização, Medição e Publicação contam desde o informe da Nota',
            'Média da equipe'      => 'Notas por pessoa em cada dia trabalhado, dentro dos filtros acima',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $r      = $this->report;
        $s      = $r['summary'];
        $dec    = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
        $pct    = fn (?float $v) => $v === null ? 'sem período anterior' : ($v > 0 ? '+' : '') . $dec($v) . '% vs. período anterior';
        $oldest = $r['next'] ? $r['next']['service'] . ' · ' . $r['next']['note'] . ' · ' . $r['next']['user'] : '—';

        return [
            'title'    => self::BRAND . ' — ' . ($this->labels['company'] ?? 'todas as empresas'),
            'subtitle' => 'Atividades abertas (situação de agora) e produtividade da equipe no período selecionado.',
            'sections' => [
                ['Atividades abertas (agora)', [
                    ['Abertas', $s['open'], 'com ' . $s['open_people'] . ' pessoa(s)', DashboardTheme::BLUE],
                    ['Sem responsável', $s['unassigned'], 'ainda sem dono', '8A6D00'],
                    ['Mais antiga', $s['oldest'] === null ? '—' : $s['oldest'] . ' d', $oldest, DashboardTheme::RED],
                    ['Mais de 30 dias', $s['over_30'], 'idade média ' . ($s['average'] === null ? '—' : $dec($s['average']) . ' d'), DashboardTheme::RED],
                    ['RI abertas', $s['ri'], $s['running'] . ' em andamento · ' . $s['paused'] . ' em pausa', DashboardTheme::INK],
                ]],
                ['Produção do período', [
                    ['Concluídas', $s['done'], $pct($s['done_delta']), DashboardTheme::GREEN],
                    ['Postes/ativos', $s['done_postes'], $pct($s['postes_delta']), DashboardTheme::TEAL],
                    ['Pessoas que produziram', $s['people'], $s['idle_people'] . ' sem conclusões no período', DashboardTheme::BLUE],
                    ['Ritmo da equipe', $dec($s['per_person']), 'notas por pessoa/dia', DashboardTheme::INK],
                    ['Tempo médio', Panel::duration($r['stages'][3]['seconds']), 'do início até concluir', DashboardTheme::TEAL],
                ]],
            ],
        ];
    }

    /** @return array<int, DashboardTableSheet> */
    private function tables(string $generated): array
    {
        $sheets = [];

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => self::BRAND, 'description' => $description, 'generated' => $generated], $options));
        };

        $this->peopleSheets($add);
        $this->productivitySheets($add);
        $this->openSheets($add);
        $this->seriesSheets($add);
        $this->matrixAndCompleted($add);

        return $sheets;
    }

    private function peopleSheets(callable $add): void
    {
        $users = $this->report['users'];
        $s     = $this->report['summary'];

        $add(
            'Pessoas',
            count($users) . ' pessoa(s): produção do período, comparação com a média da equipe e o que ainda têm aberto. Quem não concluiu nada aparece com 0 (produziu ou tinha abertas nos últimos 6 meses).',
            ['Pessoa', 'Empresa', 'Concluídas', '% das notas', 'Postes/ativos', 'Dias trabalhados', 'Notas/dia', 'Média equipe (notas/dia)', 'vs. equipe (%)', 'Tempo médio total (dias)', 'Parada média (dias)', 'Última conclusão', 'Abertas', 'Das quais RI', 'Mais antiga (dias)', 'Idade média (dias)', 'Situação'],
            array_merge(
                array_map(fn (array $u) => [
                    $u['name'], $u['company'], $u['notas'], $u['share'], $u['postes'], $u['dias'], $u['per_day'], $u['team_per_day'], $u['delta'],
                    $this->days($u['avg_total']), $this->days($u['avg_stopped']), $this->date($u['last_done']), $u['open'], $u['open_ri'], $u['open_oldest'], $u['open_average'],
                    $u['idle'] ? 'Sem conclusões no período' : '',
                ], $users),
                [['TOTAL', '', $s['done'], $s['done'] > 0 ? 100.0 : 0, $s['done_postes'], '', $s['per_person'], '', '', '', '', '', $s['open'], $s['ri'], $s['oldest'], $s['average'], '']],
            ),
            [
                'tab'      => DashboardTheme::GREEN, 'totalRows' => [count($users)], 'center' => ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'L', 'M', 'N', 'O', 'P'],
                'formats'  => ['C' => DashboardTableSheet::INT, 'D' => '0.0"%"', 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => '0.0', 'H' => '0.0', 'I' => self::PCT, 'J' => self::DAYS2, 'K' => self::DAYS2, 'L' => self::DATETIME, 'M' => DashboardTableSheet::INT, 'N' => DashboardTableSheet::INT, 'O' => self::DAYS_INT, 'P' => self::DAYS],
                'dataBars' => ['C' => DashboardTheme::GREEN, 'M' => DashboardTheme::BLUE, 'O' => DashboardTheme::RED],
                'widths'   => ['A' => 28, 'B' => 24, 'J' => 14, 'L' => 17, 'Q' => 26],
                'chart'    => ['type' => 'columns', 'title' => 'Concluídas × abertas por pessoa', 'category' => 'A', 'series' => ['C', 'M'], 'colors' => [DashboardTheme::GREEN, '94A3B8'], 'rows' => min(15, count($users)), 'from' => 'A' . (DashboardTableSheet::FIRST_DATA_ROW + count($users) + 3), 'to' => 'I' . (DashboardTableSheet::FIRST_DATA_ROW + count($users) + 22)],
            ],
        );

        $companies = $this->report['companies'];

        $add(
            'Empresas',
            'Produção do período e carga em aberto por empresa; "vs. equipe" compara as notas por pessoa/dia da empresa com a média do recorte.',
            ['Empresa', 'Pessoas que produziram', 'Concluídas', '% das notas', 'Postes/ativos', 'Notas/pessoa/dia', 'vs. equipe (%)', 'Tempo médio total (dias)', 'Abertas', 'Mais antiga (dias)', 'Idade média (dias)'],
            array_map(fn (array $c) => [
                $c['name'], $c['pessoas'], $c['notas'], $c['share'], $c['postes'], $c['per_day'], $c['delta'], $this->days($c['avg_total']), $c['open'], $c['open_oldest'], $c['open_average'],
            ], $companies),
            [
                'tab'      => DashboardTheme::TEAL, 'center' => ['B', 'C', 'D', 'E', 'F', 'G', 'I', 'J', 'K'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => '0.0"%"', 'E' => DashboardTableSheet::INT, 'F' => '0.0', 'G' => self::PCT, 'H' => self::DAYS2, 'I' => DashboardTableSheet::INT, 'J' => self::DAYS_INT, 'K' => self::DAYS],
                'dataBars' => ['C' => DashboardTheme::TEAL, 'I' => DashboardTheme::BLUE],
                'widths'   => ['A' => 32, 'H' => 14],
                'chart'    => ['type' => 'columns', 'title' => 'Concluídas × abertas por empresa', 'category' => 'A', 'series' => ['C', 'I'], 'colors' => [DashboardTheme::TEAL, '94A3B8'], 'rows' => min(15, count($companies)), 'from' => 'M4', 'to' => 'V22'],
            ],
        );
    }

    private function productivitySheets(callable $add): void
    {
        $r    = $this->report;
        $acts = $r['activities'];
        $sum  = $r['summary'];

        $add(
            'Produtividade',
            'Concluídas no período por atividade, comparadas com a média da equipe (notas por pessoa em cada dia trabalhado).',
            ['Atividade', 'Grupo', 'Notas', '% das notas', 'Postes/ativos', 'Pessoas', 'Notas/pessoa/dia', 'Média equipe (notas/pessoa/dia)', 'vs. equipe (%)', 'Tempo médio total (dias)', 'Fila média (dias)', 'Execução média (dias)', 'Parada média (dias)'],
            array_merge(
                array_map(fn (array $a) => [
                    $a['service'], Panel::GROUP_LABELS[$a['group']], $a['notas'], $a['share'], $a['postes'], $a['pessoas'], $a['per_day'], $a['team_per_day'], $a['delta'],
                    $this->days($a['avg_total']), $this->days($a['avg_queue']), $this->days($a['avg_exec']), $this->days($a['avg_stopped']),
                ], $acts),
                [['TOTAL', '', $sum['done'], $sum['done'] > 0 ? 100.0 : 0, $sum['done_postes'], $sum['people'], $sum['per_person'], '', '', '', '', '', '']],
            ),
            [
                'tab'      => DashboardTheme::GREEN, 'totalRows' => [count($acts)], 'center' => ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
                'formats'  => ['C' => DashboardTableSheet::INT, 'D' => '0.0"%"', 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => '0.0', 'H' => '0.0', 'I' => self::PCT, 'J' => self::DAYS2, 'K' => self::DAYS2, 'L' => self::DAYS2, 'M' => self::DAYS2],
                'dataBars' => ['C' => DashboardTheme::GREEN],
                'widths'   => ['A' => 26, 'B' => 13, 'H' => 16, 'J' => 14],
                'chart'    => ['type' => 'columns', 'title' => 'Notas por pessoa/dia: atividade × equipe', 'category' => 'A', 'series' => ['G', 'H'], 'colors' => [DashboardTheme::BLUE, '94A3B8'], 'rows' => count($acts), 'from' => 'A' . (DashboardTableSheet::FIRST_DATA_ROW + count($acts) + 3), 'to' => 'H' . (DashboardTableSheet::FIRST_DATA_ROW + count($acts) + 21)],
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

    private function openSheets(callable $add): void
    {
        $r    = $this->report;
        $open = $r['open'];

        $add(
            'Abertas',
            count($open) . ' atividade(s) aberta(s), da mais antiga para a mais recente. O tempo conta desde a ATT; pós-obra (Fiscalização, Medição, Publicação) desde o informe.',
            ['#', 'Atividade', 'Grupo', 'Nota', 'Responsável', 'Empresa', 'Situação', 'RI', 'ATT', 'Informe', 'Contado desde', 'Início da contagem', 'Dias em aberto'],
            array_map(fn (array $o, int $i) => [
                $i + 1, $o['service'], Panel::GROUP_LABELS[$o['group']], $o['note'], $o['user'], $o['company'], $o['status'], $o['ri'] ? 'RI' : '',
                $this->date($o['att_at']), $this->date($o['informed_at']), $o['basis'] ?? '', $this->date($o['since']), $o['age_days'],
            ], $open, array_keys($open)),
            [
                'tab'      => DashboardTheme::RED, 'center' => ['A', 'D', 'H', 'I', 'J', 'K', 'L', 'M'],
                'formats'  => ['I' => self::DATETIME, 'J' => self::DATETIME, 'L' => self::DATETIME, 'M' => self::DAYS_INT],
                'dataBars' => ['M' => DashboardTheme::RED],
                'widths'   => ['A' => 6, 'B' => 22, 'C' => 13, 'D' => 16, 'E' => 26, 'F' => 24, 'G' => 20, 'H' => 6, 'I' => 17, 'J' => 17, 'K' => 14, 'L' => 17, 'M' => 14],
            ],
        );

        $byUser = $r['open_by_user'];

        $add(
            'Abertas por pessoa',
            'Carga de cada pessoa agora: quantidade, mais antiga e idade média, com as faixas de idade (dias corridos).',
            ['Pessoa', 'Abertas', 'Das quais RI', 'Em andamento', 'Em pausa', '0-15 dias', '16-30 dias', '31+ dias', 'Mais antiga (dias)', 'Idade média (dias)'],
            array_map(fn (array $u) => [$u['name'], $u['total'], $u['ri'], $u['running'], $u['paused'], $u['b_new'], $u['b_mid'], $u['b_old'], $u['oldest'], $u['average']], $byUser),
            [
                'tab'      => DashboardTheme::BLUE, 'center' => ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => DashboardTableSheet::INT, 'H' => DashboardTableSheet::INT, 'I' => self::DAYS_INT, 'J' => self::DAYS],
                'dataBars' => ['B' => DashboardTheme::BLUE, 'I' => DashboardTheme::RED],
                'widths'   => ['A' => 30],
                'chart'    => ['type' => 'stackedBar', 'title' => 'Carga em aberto por pessoa e idade', 'category' => 'A', 'series' => ['F', 'G', 'H'], 'colors' => [DashboardTheme::BLUE, 'F7D200', DashboardTheme::RED], 'rows' => min(20, count($byUser)), 'from' => 'L4', 'to' => 'U24'],
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

    private function seriesSheets(callable $add): void
    {
        $r       = $this->report;
        $d       = $r['daily'];
        $balance = 0;
        $rows    = [];

        foreach ($d['dates'] as $i => $date) {
            $balance += $d['received'][$i] - $d['completed'][$i];
            $rows[] = [Date::dateTimeToExcel(Carbon::parse($date)), $d['received'][$i], $d['completed'][$i], $d['postes'][$i], $d['people'][$i], $balance];
        }

        $add(
            'Diário',
            'Recebidas (ATT) e concluídas por dia no período, pessoas que produziram e o saldo acumulado (recebidas − concluídas).',
            ['Dia', 'Recebidas (ATT)', 'Concluídas', 'Postes/ativos', 'Pessoas que produziram', 'Saldo acumulado'],
            $rows,
            [
                'tab'      => DashboardTheme::BLUE, 'center' => ['A', 'B', 'C', 'D', 'E'],
                'formats'  => ['A' => 'ddd dd/mm/yyyy', 'B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => '+#,##0;-#,##0;"–"'],
                'dataBars' => ['C' => DashboardTheme::GREEN],
                'widths'   => ['A' => 20, 'E' => 14],
                'chart'    => ['type' => 'line', 'title' => 'Recebidas × concluídas por dia', 'category' => 'A', 'series' => ['B', 'C'], 'colors' => [DashboardTheme::BLUE, DashboardTheme::TEAL], 'rows' => count($rows), 'from' => 'H4', 'to' => 'R22'],
            ],
        );

        $m     = $r['monthly'];
        $mrows = [];

        foreach ($m['months'] as $i => $month) {
            $parts   = array_map(fn (string $g) => $m['series'][$g][$i], array_keys(Panel::GROUP_LABELS));
            $mrows[] = array_merge([Carbon::parse($month . '-01')->translatedFormat('M/Y')], $parts, [array_sum($parts), $m['postes'][$i], $m['people'][$i]]);
        }

        $add(
            'Mensal',
            'Notas concluídas por mês e grupo de atividade, nos 12 meses até o fim do período.',
            array_merge(['Mês'], array_values(Panel::GROUP_LABELS), ['Total de notas', 'Postes/ativos', 'Pessoas que produziram']),
            $mrows,
            [
                'tab'      => DashboardTheme::TEAL, 'center' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => DashboardTableSheet::INT, 'H' => DashboardTableSheet::INT],
                'dataBars' => ['F' => DashboardTheme::TEAL],
                'widths'   => ['H' => 14],
                'chart'    => ['type' => 'stackedBar', 'title' => 'Notas por mês e grupo', 'category' => 'A', 'series' => ['B', 'C', 'D', 'E'], 'colors' => [DashboardTheme::BLUE, '6577DC', 'A8B1E9', 'CBD5E1'], 'rows' => count($mrows), 'from' => 'J4', 'to' => 'T22'],
            ],
        );
    }

    private function matrixAndCompleted(callable $add): void
    {
        $r        = $this->report;
        $matrix   = $r['matrix'];
        $services = $matrix['services'];
        $formats  = [];
        $center   = [];

        foreach (array_keys($services) as $i) {
            $letter           = Coordinate::stringFromColumnIndex($i + 3);
            $formats[$letter] = DashboardTableSheet::INT;
            $center[]         = $letter;
        }

        $add(
            'Pessoa × Atividade',
            'Notas concluídas no período por pessoa e atividade: quem faz o quê.',
            array_merge(['Pessoa', 'Total'], $services),
            array_map(fn (array $row) => array_merge([$row['person'], $row['total']], array_map(fn (string $service) => $row['cells'][$service] ?? 0, $services)), $matrix['rows']),
            [
                'tab'      => DashboardTheme::GREEN, 'center' => array_merge(['B'], $center), 'formats' => ['B' => DashboardTableSheet::INT] + $formats,
                'dataBars' => ['B' => DashboardTheme::GREEN],
                'widths'   => ['A' => 30],
            ],
        );

        $completed = $r['completed'];
        $cut       = count($completed) >= $r['completed_limit'] ? ' Lista cortada nas ' . number_format($r['completed_limit'], 0, ',', '.') . ' primeiras: reduza o período ou use os filtros.' : '';

        $add(
            'Concluídas',
            count($completed) . ' atividade(s) concluída(s) no período, da mais antiga para a mais recente conclusão (a tela só resume).' . $cut,
            ['Nota', 'Atividade', 'Grupo', 'Responsável', 'Empresa', 'RI', 'Despacho', 'ATT', 'Informe', 'Conclusão', 'Tempo total (dias)', 'Tempo parado (dias)', 'Postes/ativos', 'Situação', 'Conclusão da análise'],
            array_map(fn (array $c) => [
                $c['note'], $c['service'], Panel::GROUP_LABELS[$c['group']], $c['user'], $c['company'], $c['ri'] ? 'RI' : '',
                $this->date($c['dispatch_at']), $this->date($c['att_at']), $this->date($c['informed_at']), $this->date($c['completed_at']),
                $this->days($c['total_seconds']), $this->days($c['stopped']), $c['postes'], $c['status'], $c['conclusion'] ?? '',
            ], $completed),
            [
                'tab'      => DashboardTheme::TEAL, 'center' => ['A', 'F', 'G', 'H', 'I', 'J', 'M'],
                'formats'  => ['G' => self::DATETIME, 'H' => self::DATETIME, 'I' => self::DATETIME, 'J' => self::DATETIME, 'K' => self::DAYS2, 'L' => self::DAYS2, 'M' => DashboardTableSheet::INT],
                'dataBars' => ['K' => DashboardTheme::BLUE, 'M' => DashboardTheme::GREEN],
                'widths'   => ['A' => 16, 'B' => 22, 'C' => 13, 'D' => 26, 'E' => 24, 'F' => 6, 'G' => 17, 'H' => 17, 'I' => 17, 'J' => 17, 'K' => 14, 'L' => 14, 'M' => 13, 'N' => 20, 'O' => 34],
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
