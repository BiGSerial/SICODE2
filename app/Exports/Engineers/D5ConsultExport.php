<?php

namespace App\Exports\Engineers;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use App\Services\D5\D5StageResolver;
use App\Services\Engineers\D5ConsultAnalytics as Analytics;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/**
 * Excel da "Consulta geral D5": resumo, uma aba por bloco da tela (etapas, empreiteiras, motivos, faixas de idade e fluxo
 * mensal), a lista de D5 e a aba "Controle Exportacao". Os números vêm de D5ConsultAnalytics (os mesmos da tela); este
 * arquivo só formata.
 */
class D5ConsultExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Consulta geral D5';

    /** @var array<string, string> */
    private array $descriptions = [];

    /**
     * @param array<string, mixed>   $snapshot    D5ConsultAnalytics::snapshot() (todas as etapas, sem o filtro de etapa da lista)
     * @param array<string, string>  $filters     filtros em texto (resumo)
     * @param array<string, string>  $audit       aba "Controle Exportacao" (campo => valor)
     */
    public function __construct(
        private readonly array $snapshot,
        private readonly D5ConsultListSheet $list,
        private readonly string $requester,
        private readonly array $filters,
        private readonly array $audit,
        private readonly Carbon $generatedAt,
        private readonly bool $withFinalized = true,
    ) {
    }

    public function sheets(): array
    {
        $tables = $this->tables('Gerado em ' . $this->generatedAt->format('d/m/Y H:i'));
        $tabs   = array_map(fn (DashboardTableSheet $sheet) => ['title' => $sheet->title(), 'description' => $this->descriptions[$sheet->title()] ?? ''], $tables);

        $tabs[] = ['title' => 'Lista D5', 'description' => 'A lista da consulta, na etapa e nos filtros escolhidos na tela.'];
        $tabs[] = ['title' => 'Controle Exportacao', 'description' => 'Quem exportou, quando e com quais filtros.'];

        $rows = [];

        foreach ($this->audit as $field => $value) {
            $rows[] = [$field, $value];
        }

        return array_merge(
            [new MyPanelSummarySheet(['generated_at' => $this->generatedAt], $this->requester, $this->filters, $tabs, $this->summaryLayout())],
            $tables,
            [$this->list, new StyledArraySheetExport('Controle Exportacao', ['Campo', 'Valor'], $rows)],
        );
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->requester,
            'title'          => 'Consulta geral D5',
            'description'    => 'D5 por etapa, tempo parado, empreiteiras, motivos, fluxo mensal e a lista',
            'subject'        => 'Engenharia',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $s   = $this->snapshot['summary'];
        $st  = $this->snapshot['stages'];
        $dec = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
        $pct = $s['open'] > 0 ? number_format($s['late'] / $s['open'] * 100, 0) . '%' : '0%';

        return [
            'title'    => self::BRAND,
            'subtitle' => 'Onde cada D5 está, há quantos dias espera e quem a está segurando. A etapa da lista não altera estes números.',
            'sections' => [
                ['D5 em andamento (situação de agora)', [
                    ['Em andamento', $s['open'], 'fornecedor, fiscalização e medição', DashboardTheme::BLUE],
                    ['Atrasadas', $s['late'], $pct . ' · mais de ' . Analytics::LATE_DAYS . ' dias na etapa', DashboardTheme::RED],
                    ['Em atenção', $s['warn'], 'de ' . (Analytics::WARN_DAYS + 1) . ' a ' . Analytics::LATE_DAYS . ' dias na etapa', '8A6D00'],
                    ['Mais antiga', $s['oldest'] === null ? '—' : $s['oldest'] . ' d', 'dias parada na etapa', DashboardTheme::RED],
                    ['Média parada', $s['average'] === null ? '—' : $dec($s['average']) . ' d', 'dias na etapa atual', DashboardTheme::INK],
                ]],
                ['Por etapa', [
                    ['Fornecedor', $st[D5StageResolver::SUPPLIER]['total'], $st[D5StageResolver::SUPPLIER]['late'] . ' atrasada(s)', DashboardTheme::RED],
                    ['Fiscalização', $st[D5StageResolver::FISCALIZATION]['total'], $st[D5StageResolver::FISCALIZATION]['late'] . ' atrasada(s)', '8A6D00'],
                    ['Medição', $st[D5StageResolver::PAYMENT]['total'], $st[D5StageResolver::PAYMENT]['late'] . ' atrasada(s)', DashboardTheme::BLUE],
                    ...($this->withFinalized ? [['Finalizadas', $s['finalized'], 'D5 concluídas', DashboardTheme::GREEN]] : []),
                    ['Retorno médio', $s['return_days'] === null ? '—' : $dec($s['return_days']) . ' d', 'despacho até o retorno da empreiteira', DashboardTheme::TEAL],
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

        $this->stageSheet($add);
        $this->rankingSheet($add, 'Por empreiteira', 'by_company', 'Empreiteira');
        $this->rankingSheet($add, 'Por motivo', 'by_reason', 'Motivo');
        $this->ageSheet($add);
        $this->flowSheet($add);

        return $sheets;
    }

    private function stageSheet(callable $add): void
    {
        $rows = [];

        foreach ($this->snapshot['stages'] as $x) {
            $rows[] = [$x['label'], $x['total'], $x['warn'], $x['late'], $x['total'] > 0 ? $x['late'] / $x['total'] : 0, $x['oldest'], $x['average']];
        }

        if ($this->withFinalized) {
            $rows[] = ['Finalizado', $this->snapshot['summary']['finalized'], null, null, null, null, null];
        }

        $add(
            'Por etapa',
            'D5 por etapa nos filtros atuais, com as em atenção, as atrasadas (mais de ' . Analytics::LATE_DAYS . ' dias na etapa), a mais antiga e a média.',
            ['Etapa', 'D5', 'Em atenção', 'Atrasadas', '% atrasadas', 'Mais antiga (dias)', 'Média (dias)'],
            $rows,
            [
                'tab'      => DashboardTheme::RED,
                'center'   => ['B', 'C', 'D', 'E', 'F', 'G'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => '0%', 'F' => DashboardTableSheet::INT, 'G' => '#,##0.0'],
                'dataBars' => ['B' => DashboardTheme::BLUE, 'D' => DashboardTheme::RED],
                'widths'   => ['A' => 22, 'B' => 12, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 20, 'G' => 16],
            ],
        );
    }

    private function rankingSheet(callable $add, string $title, string $key, string $heading): void
    {
        $rows = $this->snapshot[$key];

        $add(
            $title,
            count($rows) . ' linha(s): D5 em andamento por ' . mb_strtolower($heading) . ', com as atrasadas e a mais antiga.',
            [$heading, 'D5 em andamento', 'Atrasadas', '% atrasadas', 'Mais antiga (dias)'],
            array_map(fn (array $r) => [$r['label'], $r['total'], $r['late'], $r['total'] > 0 ? $r['late'] / $r['total'] : 0, $r['oldest']], $rows),
            [
                'tab'      => DashboardTheme::BLUE,
                'center'   => ['B', 'C', 'D', 'E'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => '0%', 'E' => DashboardTableSheet::INT],
                'dataBars' => ['B' => DashboardTheme::BLUE, 'C' => DashboardTheme::RED],
                'widths'   => ['A' => 38, 'B' => 18, 'C' => 14, 'D' => 14, 'E' => 20],
            ],
        );
    }

    private function ageSheet(callable $add): void
    {
        $b    = $this->snapshot['age_buckets'];
        $rows = [];

        foreach ($b['labels'] as $i => $label) {
            $rows[] = [$label, $b['values'][$i]];
        }

        $add(
            'Faixas de idade',
            'D5 em andamento por faixa de dias parada na etapa atual. A partir de ' . (Analytics::LATE_DAYS + 1) . ' dias a D5 conta como atrasada.',
            ['Dias na etapa', 'D5 em andamento'],
            $rows,
            [
                'tab'     => DashboardTheme::AMBER,
                'center'  => ['A', 'B'],
                'formats' => ['B' => DashboardTableSheet::INT],
                'widths'  => ['A' => 20, 'B' => 18],
                'chart'   => [
                    'type'     => 'columns',
                    'title'    => 'D5 em andamento por faixa de dias na etapa',
                    'category' => 'A',
                    'series'   => ['B'],
                    'colors'   => [DashboardTheme::RED],
                    'rows'     => count($rows),
                    'from'     => 'D4',
                    'to'       => 'M22',
                ],
            ],
        );
    }

    private function flowSheet(callable $add): void
    {
        $f    = $this->snapshot['flow'];
        $rows = [];

        foreach ($f['labels'] as $i => $label) {
            $rows[] = [$label, $f['sent'][$i], $f['returned'][$i]];
        }

        $add(
            'Fluxo mensal',
            'D5 despachadas e retornadas pela empreiteira por mês, nos últimos ' . Analytics::FLOW_MONTHS . ' meses, nos filtros atuais.',
            ['Mês', 'Despachadas', 'Retornadas pela empreiteira'],
            $rows,
            [
                'tab'     => DashboardTheme::TEAL,
                'center'  => ['A', 'B', 'C'],
                'formats' => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT],
                'widths'  => ['A' => 14, 'B' => 16, 'C' => 28],
                'chart'   => [
                    'type'     => 'line',
                    'title'    => 'Despachadas x retornadas por mês',
                    'category' => 'A',
                    'series'   => ['B', 'C'],
                    'colors'   => [DashboardTheme::BLUE, DashboardTheme::TEAL],
                    'rows'     => count($rows),
                    'from'     => 'E4',
                    'to'       => 'N22',
                ],
            ],
        );
    }
}
