<?php

namespace App\Exports\Engineers;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use App\Exports\Reports\PostWorkProcessStrictSheet;
use App\Services\Engineers\InformDashboardAnalytics as Data;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel do "Painel de informes": as mesmas séries da tela (uma aba por bloco), os informes do período em "Registros" e a aba
 * "Controle Exportacao". Os dados vêm de InformDashboardAnalytics::report(); este arquivo só formata.
 * Abas: Resumo · Empreiteiras · Devoluções · Situação atual · Evolução · Registros · Regras · Controle Exportacao.
 */
class InformDashboardExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Painel de informes';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    /** @var array<string, string> descrição de cada aba (índice da aba Resumo) */
    private array $descriptions = [];

    /**
     * @param array<string, mixed>  $report  InformDashboardAnalytics::report() mais `records` (linhas já filtradas pela situação da ADS)
     * @param array<string, string> $control rótulo => valor da aba Controle Exportacao
     */
    public function __construct(private readonly array $report, private readonly string $requester, private readonly array $control)
    {
    }

    public function sheets(): array
    {
        $tables = $this->tables('Gerado em ' . $this->report['generated_at']->format('d/m/Y H:i'));
        $tabs   = array_map(fn ($sheet) => ['title' => $sheet->title(), 'description' => $this->descriptions[$sheet->title()] ?? ''], $tables);

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
            'title'          => 'Painel de informes',
            'description'    => 'Informes de conclusão: recebimento, devoluções, entrega da ADS e situação atual',
            'subject'        => 'Engenharia',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $s   = $this->report['summary'];
        $b   = $s['buckets'];
        $pct = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.') . '%';
        $f   = $this->report['filters'];

        return [
            'title'    => self::BRAND,
            'subtitle' => 'Informes de ' . $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y') . ', nos filtros aplicados.',
            'sections' => [
                ['Recebimento e devolução', [
                    ['Informes recebidos', $s['total'], $s['retries'] . ' reinforme(s)', DashboardTheme::BLUE],
                    ['Devolvidos', $s['returned'], $pct($s['pct_returned']) . ' dos informes', '8A6D00'],
                    ['Aguardando novo informe', $s['waiting_new'], 'devolvidos, sem reenvio', DashboardTheme::INK],
                    ['ADS tácitas', $s['tacit'], $s['tacit_pending'] . ' sem entrega', '8A6D00'],
                ]],
                ['Entrega da ADS', [
                    ['No prazo', $b['on_time'], $pct($s['pct_on_time']) . ' dos que já tinham prazo', DashboardTheme::GREEN],
                    ['Em atraso', $b['late'], 'atraso médio ' . Data::days($s['avg_late']) . ' · multa média ' . $pct($s['avg_penalty']), DashboardTheme::RED],
                    ['Vencidas sem entrega', $b['overdue'], 'maior atraso ' . $s['max_late'] . ' dia(s)', DashboardTheme::RED],
                    ['Em aberto, no prazo', $b['open'], $s['expiring'] . ' vencem em até 3 dias', DashboardTheme::TEAL],
                    ['Tempo médio de entrega', Data::days($s['avg_delivery']), 'do informe até a ADS', DashboardTheme::BLUE],
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

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => self::BRAND, 'description' => $description, 'generated' => $generated], $options));
        };

        $add(
            'Empreiteiras',
            'Informes por empreiteira: devolução e entrega da ADS no prazo.',
            ['Empreiteira', 'Informes', 'Devolvidos', '% devolvidos', 'ADS no prazo', 'ADS em atraso', 'Vencidas sem entrega', 'Em aberto', '% no prazo', 'Atraso médio (dias)'],
            array_map(fn (array $c) => [$c['name'], $c['total'], $c['returned'], $c['pct_returned'], $c['on_time'], $c['late'], $c['overdue'], $c['open'], $c['pct_on_time'], $c['avg_late']], $r['companies']),
            ['tab' => DashboardTheme::INK, 'center' => ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'], 'formats' => ['B' => $int, 'C' => $int, 'D' => '0.0"%"', 'E' => $int, 'F' => $int, 'G' => $int, 'H' => $int, 'I' => '0.0"%"', 'J' => '0.0'], 'dataBars' => ['B' => DashboardTheme::BLUE], 'widths' => ['A' => 38]],
        );

        $add(
            'Devoluções',
            'Motivos das devoluções dos informes do período (um informe pode ter mais de uma devolução).',
            ['Motivo', 'Devoluções', '% do total'],
            array_map(fn (array $x) => [$x['reason'], $x['total'], $x['pct']], $r['reasons']),
            ['tab' => DashboardTheme::AMBER, 'center' => ['B', 'C'], 'formats' => ['B' => $int, 'C' => '0.0"%"'], 'dataBars' => ['B' => DashboardTheme::AMBER], 'widths' => ['A' => 54, 'B' => 14, 'C' => 12]],
        );

        $add(
            'Situação atual',
            'Onde os informes do período estão hoje (situação do informe no fluxo de encerramento).',
            ['Situação', 'Informes', '% do total'],
            array_map(fn (array $x) => [$x['label'], $x['total'], $r['summary']['total'] ? round($x['total'] / $r['summary']['total'] * 100, 1) : 0], $r['statuses']),
            ['tab' => DashboardTheme::TEAL, 'center' => ['B', 'C'], 'formats' => ['B' => $int, 'C' => '0.0"%"'], 'dataBars' => ['B' => DashboardTheme::TEAL], 'widths' => ['A' => 44, 'B' => 14, 'C' => 12]],
        );

        $s    = $r['series'];
        $unit = $s['unit'] === 'day' ? 'Dia' : 'Mês';
        $add(
            'Evolução',
            'Informes ' . ($s['unit'] === 'day' ? 'por dia' : 'por mês') . ' do informe, com a situação da ADS de cada um.',
            [$unit, 'Recebidos', 'Devolvidos', 'ADS no prazo', 'ADS em atraso ou vencida', 'Em aberto, no prazo'],
            array_map(null, $s['labels'], $s['received'], $s['returned'], $s['on_time'], $s['late'], $s['open']),
            [
                'tab'   => DashboardTheme::BLUE, 'center' => ['B', 'C', 'D', 'E', 'F'], 'formats' => ['B' => $int, 'C' => $int, 'D' => $int, 'E' => $int, 'F' => $int], 'widths' => ['A' => 12, 'B' => 12, 'C' => 12, 'D' => 14, 'E' => 24, 'F' => 18],
                'chart' => ['type' => 'columns', 'title' => 'Informes recebidos', 'category' => 'A', 'series' => ['B'], 'colors' => [DashboardTheme::BLUE], 'rows' => count($s['labels']), 'from' => 'H4', 'to' => 'Q22'],
            ],
        );

        $add(
            'Registros',
            count($r['records']) . ' informe(s), do mais recente para o mais antigo (os mesmos da lista da tela).',
            ['Informe', 'Nota', 'Empreiteira', 'Situação atual', 'Situação da ADS', 'Prazo da ADS', 'ADS entregue em', 'Atraso (dias)', 'Multa prevista (%)', 'Entrega (dias)', 'Devoluções', 'Último motivo', 'Reinforme', 'ADS tácita'],
            array_map(fn (array $x) => [
                $this->date($x['informed_at']), $x['note'], $x['company'], $x['status_label'], Data::BUCKETS[$x['bucket']],
                $this->date($x['due_at']), $this->date($x['delivered_at']), $x['delay_days'] ?: null, $x['penalty'] ?: null,
                $x['delivery_days'] === null ? null : round($x['delivery_days'], 1), $x['returns'] ?: null, $x['last_return'] ?? '',
                $x['retry'] ? 'SIM' : 'NÃO', $x['tacit'] ? 'SIM' : 'NÃO',
            ], $r['records']),
            [
                'tab'     => DashboardTheme::GREEN,
                'center'  => ['H', 'I', 'J', 'K', 'M', 'N'],
                'formats' => ['A' => self::DATETIME, 'B' => '@', 'F' => self::DATETIME, 'G' => self::DATETIME, 'H' => $int, 'I' => '0.0', 'J' => '0.0', 'K' => $int],
                'widths'  => ['A' => 18, 'B' => 14, 'C' => 30, 'D' => 26, 'E' => 30, 'F' => 18, 'G' => 18, 'H' => 12, 'I' => 14, 'J' => 12, 'K' => 12, 'L' => 40, 'M' => 11, 'N' => 11],
            ],
        );

        $add(
            'Regras',
            'Como cada número deste arquivo é calculado.',
            ['Item', 'Regra'],
            [
                ['Universo', 'Informes de conclusão de obra com data do informe no período, sem os cancelados. O informe devolvido e ainda sem novo envio conta como recebido, mas não tem ADS a cobrar.'],
                ['Prazo da ADS', 'Contado a partir do informe, pela regra da região (dias úteis ou corridos, vence às 23:59 do último dia). Na ADS tácita vale o prazo próprio dela. A mesma regra da tela "Situação das ADS".'],
                ['Entregue no prazo / em atraso', 'ADS entregue até / depois do prazo. Na ADS tácita a entrega é a data em que a ADS definitiva foi entregue.'],
                ['Vencida sem entrega', 'Prazo passou e a ADS não foi entregue.'],
                ['Em aberto, no prazo', 'Sem ADS ainda, com prazo vigente (inclui os que vencem em até 3 dias).'],
                ['Passivo (legado)', 'Informe anterior a 01/02/2026 sem ADS: histórico, fora do índice de cumprimento.'],
                ['% no prazo', 'No prazo ÷ (no prazo + em atraso + vencidas). Só entra quem já tinha prazo a cobrar.'],
                ['Atraso e multa prevista', 'Dias de atraso pela política de ADS (dias úteis conforme a região); multa prevista em % conforme a faixa de atraso.'],
                ['Devolução', 'Registro de devolução do informe com motivo. "Devolvidos" é o informe com ao menos uma devolução; "reinforme" é o informe reenviado depois de devolvido.'],
                ['Situação atual', 'Etapa atual do informe no fluxo de encerramento (fiscalização, medição, D5 e finalização).'],
                ['Filtro de situação da ADS', 'Vale só para a lista e para a aba Registros; os indicadores usam o recorte completo.'],
            ],
            ['tab' => DashboardTheme::MUTED, 'wrap' => ['B'], 'widths' => ['A' => 34, 'B' => 120]],
        );

        return $sheets;
    }

    private function date(?string $value): ?float
    {
        return $value ? Date::dateTimeToExcel(Carbon::parse($value)) : null;
    }
}
