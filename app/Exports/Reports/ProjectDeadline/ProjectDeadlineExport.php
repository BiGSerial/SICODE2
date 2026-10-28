<?php

namespace App\Exports\Reports\ProjectDeadline;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use App\Models\OvPhaseDuration;
use App\Services\Indicators\ProjectDeadlineAnalytics as Analytics;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel do "Prazo de Projetos": uma aba por bloco da tela (Levantamento, Desenho, Por empresa) mais a lista completa, no mesmo visual
 * do Excel do Meu painel e do Painel da equipe. Dados vêm de ProjectDeadlineAnalytics::report(); este arquivo só formata.
 * Datas são datas de verdade e os tempos vão em dias.
 */
class ProjectDeadlineExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Prazo de projetos';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    private const MONTH = 'mmm/yy';

    private const DAYS2 = '#,##0.00;-#,##0.00;"–"';

    private const PCT = '0.0"%"';

    private const BAND_COLORS = [DashboardTheme::GREEN, '9CA3AF', DashboardTheme::TEAL, DashboardTheme::RED];

    /** @var array<string,string> descrição de cada aba (índice da aba Resumo) */
    private array $descriptions = [];

    /**
     * @param  array<string,mixed>  $report  ProjectDeadlineAnalytics::report()
     */
    public function __construct(private readonly array $report, private readonly string $requester, private readonly ?string $companyLabel = null)
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
            'title'          => 'Prazo de projetos',
            'description'    => 'Aderência de prazo do levantamento (status 27) e do desenho (status 28), por mês, por empresa e lista completa',
            'subject'        => 'Indicadores de projeto',
        ];
    }

    /** @return array<string,string> */
    public function filterLabels(): array
    {
        $f = $this->report['filters'];

        return [
            'Solicitado por'    => $this->requester,
            'Empresa executora' => $this->companyLabel ?? 'Todas as empresas',
            'Término da fase'   => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y'),
            'Levantamento'      => 'Status 27 até o evento seguinte; prazo de 8 dias',
            'Desenho'           => 'Status 28 até o evento seguinte; prazo de 6 dias',
            'Cálculo'           => 'Última passagem da OV pelo status; dias corridos; o mês é o do término da fase',
            'Expurgo'           => $this->exclusionLabel(),
            'Lista completa'    => $this->report['truncated'] ? 'Cortada em ' . number_format(Analytics::DETAIL_LIMIT, 0, ',', '.') . ' linhas (use um período menor)' : 'Completa',
        ];
    }

    private function exclusionLabel(): string
    {
        $x     = $this->report['filters']['exclude'] ?? [];
        $parts = array_filter([
            !empty($x['rubrics']) ? 'Rubricas: ' . implode(', ', $x['rubrics']) : null,
            !empty($x['prefixes']) ? 'OVs que começam com: ' . implode(', ', $x['prefixes']) : null,
            !empty($x['next_stats']) ? 'Fases seguidas do status: ' . implode(', ', $x['next_stats']) : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Nenhum';
    }

    /** @return array<string,mixed> */
    private function summaryLayout(): array
    {
        $dec   = fn (float $v) => number_format($v, 1, ',', '.');
        $tiles = function (int $phase, string $color) use ($dec) {
            $p = $this->report['phases'][$phase];
            $s = $p['summary'];

            return [
                ['OVs concluídas', $s['total'], 'fases terminadas no período', $color],
                ['Aderência', $dec($s['adherence']) . '%', 'até ' . $p['limit'] . ' dias', $s['adherence'] >= 80 ? DashboardTheme::GREEN : DashboardTheme::RED],
                ['Média de dias', number_format($s['avg_days'], 2, ',', '.'), 'meta de referência: ' . $p['limit'] . ' dias', DashboardTheme::INK],
                ['Dentro do prazo', $s['bands'][0], 'de ' . $s['total'] . ' OVs', DashboardTheme::GREEN],
                ['Acima de 30 dias', $s['bands'][3], 'fora da faixa máxima', DashboardTheme::RED],
            ];
        };

        return [
            'title'    => self::BRAND . ' — ' . ($this->companyLabel ?? 'todas as empresas'),
            'subtitle' => 'Aderência de prazo do levantamento e do desenho no período selecionado.',
            'sections' => [
                ['Levantamento (status 27)', $tiles(OvPhaseDuration::SURVEY, DashboardTheme::BLUE)],
                ['Desenho (status 28)', $tiles(OvPhaseDuration::DESIGN, DashboardTheme::TEAL)],
            ],
        ];
    }

    /** @return array<int,DashboardTableSheet> */
    private function tables(string $generated): array
    {
        $sheets = [];

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => self::BRAND, 'description' => $description, 'generated' => $generated], $options));
        };

        foreach ($this->report['phases'] as $phase) {
            $this->phaseSheet($add, $phase);
        }

        $this->companySheet($add);
        $this->detailSheet($add);

        return $sheets;
    }

    private function phaseSheet(callable $add, array $phase): void
    {
        $months = $phase['months'];
        $s      = $phase['summary'];
        $count  = count($months);

        $rows = array_map(fn (array $m) => array_merge(
            [$this->month($m['month']), $m['total']],
            $m['bands'],
            [$m['adherence'], $m['avg_days']],
        ), $months);

        $rows[] = array_merge(['TOTAL', $s['total']], $s['bands'], [$s['adherence'], $s['avg_days']]);

        $add(
            $phase['label'],
            "{$phase['label']}: fases concluídas por mês de término, por faixa de dias; aderência = % concluída em até {$phase['limit']} dias.",
            array_merge(['Mês', 'Total de OVs'], $phase['labels'], ["% aderência (até {$phase['limit']} dias)", 'Média de dias']),
            $rows,
            [
                'tab'       => $phase['phase'] === OvPhaseDuration::SURVEY ? DashboardTheme::BLUE : DashboardTheme::TEAL,
                'totalRows' => [$count],
                'center'    => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
                'formats'   => ['A' => self::MONTH, 'B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => self::PCT, 'H' => self::DAYS2],
                'dataBars'  => ['B' => DashboardTheme::BLUE, 'G' => DashboardTheme::GREEN],
                'widths'    => ['A' => 12, 'C' => 16, 'D' => 16, 'E' => 16, 'F' => 18, 'G' => 22, 'H' => 16],
                'chart'     => [
                    'type'     => 'stackedBar',
                    'title'    => "{$phase['label']}: OVs por faixa de dias",
                    'category' => 'A',
                    'series'   => ['C', 'D', 'E', 'F'],
                    'colors'   => self::BAND_COLORS,
                    'rows'     => $count,
                    'from'     => 'A' . (DashboardTableSheet::FIRST_DATA_ROW + $count + 3),
                    'to'       => 'H' . (DashboardTableSheet::FIRST_DATA_ROW + $count + 21),
                ],
            ],
        );
    }

    private function companySheet(callable $add): void
    {
        $companies = $this->report['companies'];
        $survey    = OvPhaseDuration::SURVEY;
        $design    = OvPhaseDuration::DESIGN;

        $add(
            'Por empresa',
            count($companies) . ' empresa(s) executora(s): volume, aderência e média de dias de cada fase. "Sem empresa" são fases sem produção concluída vinculada.',
            ['Empresa', 'Levantamento: OVs', 'Levantamento: % aderência', 'Levantamento: média (dias)', 'Desenho: OVs', 'Desenho: % aderência', 'Desenho: média (dias)'],
            array_map(fn (array $c) => [
                $c['name'],
                $c[$survey]['total'] ?? null, $c[$survey]['adherence'] ?? null, $c[$survey]['avg_days'] ?? null,
                $c[$design]['total'] ?? null, $c[$design]['adherence'] ?? null, $c[$design]['avg_days'] ?? null,
            ], $companies),
            [
                'tab'      => DashboardTheme::GREEN,
                'center'   => ['B', 'C', 'D', 'E', 'F', 'G'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => self::PCT, 'D' => self::DAYS2, 'E' => DashboardTableSheet::INT, 'F' => self::PCT, 'G' => self::DAYS2],
                'dataBars' => ['B' => DashboardTheme::BLUE, 'E' => DashboardTheme::TEAL],
                'widths'   => ['A' => 34, 'B' => 18, 'C' => 24, 'D' => 24, 'E' => 16, 'F' => 22, 'G' => 22],
            ],
        );
    }

    private function detailSheet(callable $add): void
    {
        $detail = $this->report['detail'];

        $add(
            'Fases concluídas',
            count($detail) . ' fase(s) concluída(s) no período, da mais recente para a mais antiga.' . ($this->report['truncated'] ? ' Lista cortada no limite; use um período menor.' : ''),
            ['Nota (OV)', 'Fase', 'Empresa executora', 'Início', 'Término', 'Dias', 'Faixa', 'Dentro do prazo'],
            array_map(fn (array $d) => [
                $d['note'], Analytics::PHASE_LABELS[$d['phase']], $d['company'] ?? 'Sem empresa',
                $this->date($d['started_at']), $this->date($d['ended_at']), $d['days'], $d['band'], $d['on_time'] ? 'Sim' : 'Não',
            ], $detail),
            [
                'tab'      => DashboardTheme::INK,
                'center'   => ['A', 'B', 'D', 'E', 'F', 'G', 'H'],
                'formats'  => ['D' => self::DATETIME, 'E' => self::DATETIME, 'F' => self::DAYS2],
                'dataBars' => ['F' => DashboardTheme::RED],
                'widths'   => ['A' => 14, 'B' => 14, 'C' => 32, 'D' => 18, 'E' => 18, 'G' => 20, 'H' => 16],
            ],
        );
    }

    private function month(string $ym): float
    {
        return Date::dateTimeToExcel(Carbon::createFromFormat('Y-m-d', $ym . '-01')->startOfDay());
    }

    private function date(Carbon $date): float
    {
        return Date::dateTimeToExcel($date);
    }
}
