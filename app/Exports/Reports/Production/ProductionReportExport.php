<?php

namespace App\Exports\Reports\Production;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use App\Exports\Reports\{PostWorkProcessStrictSheet, ProductionsExportList};
use App\Services\Reports\Production\ProductionReportAnalytics as Data;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/**
 * Excel do "Relatório de Produção": as mesmas séries da tela (uma aba por bloco), a lista completa em "Registros", a mesma lista no layout anterior em "LayoutAntigo" e a aba
 * "Controle Exportacao". Os dados vêm de ProductionReportAnalytics::report(); este arquivo só formata.
 * Abas: Resumo · Conclusões · Atividades · Pessoas · Empresas · Evolução · Registros · LayoutAntigo · Regras · Controle Exportacao.
 */
class ProductionReportExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Relatório de Produção';

    /** @var array<string, string> descrição de cada aba (índice da aba Resumo) */
    private array $descriptions = [];

    /**
     * @param array<string, mixed>  $report  ProductionReportAnalytics::report()
     * @param array<string, string> $control rótulo => valor da aba Controle Exportacao (usuário, e-mail, data/hora, tipo, filtros)
     */
    public function __construct(private readonly array $report, private readonly Builder $records, private readonly string $requester, private readonly array $control, private readonly ?Builder $legacyRecords = null, private readonly int $legacyEstimate = 0)
    {
    }

    public function sheets(): array
    {
        $generated = 'Gerado em ' . $this->report['generated_at']->format('d/m/Y H:i');
        $tables    = $this->tables($generated);
        $tabs      = array_map(fn ($sheet) => ['title' => $sheet->title(), 'description' => $this->descriptions[$sheet->title()] ?? ''], $tables);

        return array_merge(
            [new MyPanelSummarySheet($this->report, $this->requester, $this->control, $tabs, $this->summaryLayout())],
            $tables,
            [new PostWorkProcessStrictSheet('Controle Exportacao', ['Campo', 'Valor'], array_map(fn ($k, $v) => [$k, $v], array_keys($this->control), $this->control))],
        );
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->requester,
            'title'          => 'Relatório de Produção',
            'description'    => 'Produção por conclusão, atividade, pessoa e empresa; dependência de órgão externo e meio ambiente; lista completa',
            'subject'        => 'Relatórios',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $s   = $this->report['summary'];
        $pct = fn (?float $v) => number_format((float) $v, 1, ',', '.') . '%';

        return [
            'title'    => self::BRAND,
            'subtitle' => 'Produção do período selecionado, nos filtros aplicados.',
            'sections' => [
                ['Registros do filtro', [
                    ['Registros', $s['total'], $s['ri'] . ' RI', DashboardTheme::BLUE],
                    ['Concluídos', $s['done'], $pct($s['pct_counted']) . ' contabilizados', DashboardTheme::GREEN],
                    ['Em aberto', $s['open'], 'incluídos pelo filtro', DashboardTheme::INK],
                    ['Postes/ativos', $s['postes'], $s['people'] . ' pessoa(s)', DashboardTheme::TEAL],
                    ['Tempo médio', Data::duration($s['avg_total']), 'início até o fim, concluídos', DashboardTheme::RED],
                ]],
                ['Dependências', [
                    ['Órgão externo', $s['doe'], $pct($s['pct_doe']) . ' dos registros', '8A6D00'],
                    ['Meio ambiente', $s['ma'], $pct($s['pct_ma']) . ' dos registros', DashboardTheme::GREEN],
                    ['Só órgão externo', $this->report['flags']['doe_only'], 'sem meio ambiente', '8A6D00'],
                    ['Só meio ambiente', $this->report['flags']['ma_only'], 'sem órgão externo', DashboardTheme::GREEN],
                    ['Os dois', $this->report['flags']['both'], 'órgão externo e meio ambiente', DashboardTheme::BLUE],
                ]],
            ],
        ];
    }

    /** @return array<int, \Maatwebsite\Excel\Concerns\WithTitle> */
    private function tables(string $generated): array
    {
        $sheets = [];
        $r      = $this->report;
        $int    = DashboardTableSheet::INT;
        $days   = fn (?float $seconds) => $seconds === null ? null : round($seconds / 86400, 2);

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => self::BRAND, 'description' => $description, 'generated' => $generated], $options));
        };

        $add(
            'Conclusões',
            count($r['conclusions']) . ' conclusão(ões) da análise nos filtros, da mais frequente para a menos frequente.',
            ['Conclusão', 'Registros', '% do total', 'Meio ambiente'],
            array_map(fn (array $c) => [$c['conclusion'], $c['total'], $c['pct'], $c['ma']], $r['conclusions']),
            ['tab' => DashboardTheme::BLUE, 'center' => ['B', 'C', 'D'], 'formats' => ['B' => $int, 'C' => '0.0"%"', 'D' => $int], 'dataBars' => ['B' => DashboardTheme::BLUE], 'widths' => ['A' => 46, 'B' => 14, 'C' => 12, 'D' => 16]],
        );

        $breakdownHeadings = fn (string $first) => [$first, 'Registros', 'Concluídos', 'Em aberto', 'Postes/ativos', 'Meio ambiente'];
        $breakdownFormats  = ['B' => $int, 'C' => $int, 'D' => $int, 'E' => $int, 'F' => $int];
        $breakdownRow      = fn (string $label, array $x) => [$label, $x['total'], $x['done'], $x['open'], $x['postes'], $x['ma']];

        foreach ([
            ['Atividades', 'services', 'service', 'Atividade', DashboardTheme::TEAL, 'Registros por atividade (serviço); a dependência de órgão externo está no Resumo e na Evolução.'],
            ['Pessoas', 'users', 'name', 'Pessoa', DashboardTheme::GREEN, 'Registros por pessoa: todas as que aparecem no filtro.'],
            ['Empresas', 'companies', 'name', 'Empresa', DashboardTheme::INK, 'Registros por empresa.'],
        ] as [$title, $key, $labelKey, $first, $tab, $description]) {
            $add(
                $title,
                $description,
                $breakdownHeadings($first),
                array_map(fn (array $x) => $breakdownRow($x[$labelKey], $x), $r[$key]),
                ['tab' => $tab, 'center' => ['B', 'C', 'D', 'E', 'F'], 'formats' => $breakdownFormats, 'dataBars' => ['B' => $tab], 'widths' => ['A' => 38]],
            );
        }

        $unit = $r['series']['unit'] === 'day' ? 'Dia' : 'Mês';
        $add(
            'Evolução',
            'Concluídas ' . ($r['series']['unit'] === 'day' ? 'por dia' : 'por mês') . ', com a parcela que depende de órgão externo e de meio ambiente.',
            [$unit, 'Concluídas', 'Dependem de órgão externo', 'Meio ambiente'],
            array_map(null, $r['series']['labels'], $r['series']['done'], $r['series']['doe'], $r['series']['ma']),
            [
                'tab'   => DashboardTheme::AMBER, 'center' => ['B', 'C', 'D'], 'formats' => ['B' => $int, 'C' => $int, 'D' => $int], 'widths' => ['A' => 14, 'B' => 14, 'C' => 22, 'D' => 16],
                'chart' => ['type' => 'columns', 'title' => 'Concluídas', 'category' => 'A', 'series' => ['B'], 'colors' => [DashboardTheme::TEAL], 'rows' => count($r['series']['labels']), 'from' => 'G4', 'to' => 'P22'],
            ],
        );

        $sheets[]                        = new ProductionRecordsSheet($this->records);
        $this->descriptions['Registros'] = 'Uma linha por produção, da mais recente para a mais antiga (a mesma lista da tela).';

        if ($this->legacyRecords) {
            $sheets[]                           = new ProductionsExportList($this->legacyRecords, $this->legacyEstimate, 'LayoutAntigo');
            $this->descriptions['LayoutAntigo'] = 'Mesma lista no layout anterior (59 colunas, mesma ordem e nomes), da conclusão mais antiga para a mais nova, para as planilhas de controle.';
        }

        $add(
            'Regras',
            'Como cada número deste arquivo é calculado.',
            ['Item', 'Regra'],
            [
                ['Registro', 'Uma produção (atividade de um usuário numa Nota). Retornos internos (RI) só entram se o filtro "Incluir RI" estiver ligado; produções rejeitadas nunca entram.'],
                ['Período', 'Vale para a data de conclusão (Fim). Com "Incluir em aberto", as atividades ainda abertas entram sempre, em qualquer período.'],
                ['Depende de órgão externo (DOE)', 'Marca da Nota (campo DOE), definida na análise do levantamento.'],
                ['Meio ambiente (MA)', 'Marca da produção (campo MA), definida ao concluir a análise.'],
                ['Conclusão', 'Conclusão registrada na análise da produção; sem análise aparece como "Sem conclusão".'],
                ['Postes/ativos', 'Postes × (EO + projeto inicial); sem EO/projeto, só os postes.'],
                ['Tempo médio', 'Média de (fim − início) das concluídas com data de início. Parada média considera só quem teve parada.'],
                ['Ordem da lista', 'Da conclusão mais recente para a mais antiga; abertas por último, pela data de início.'],
            ],
            ['tab' => DashboardTheme::MUTED, 'wrap' => ['B'], 'widths' => ['A' => 36, 'B' => 110]],
        );

        return $sheets;
    }
}
