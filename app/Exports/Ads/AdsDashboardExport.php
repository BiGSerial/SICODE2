<?php

namespace App\Exports\Ads;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Services\Ads\{AdsConnectionProbe, AdsDashboardAnalytics};
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel do painel de ADS: uma aba por bloco da tela (Resumo, Fluxo, Fila atual, Histórico) e `Controle Exportacao`. Os dados vêm de
 * AdsDashboardAnalytics::report(), a mesma fonte da tela; este arquivo só formata. Parte sem conexão sai com o aviso, sem derrubar o arquivo.
 */
class AdsDashboardExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · ADS solicitadas';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    /**
     * @param array<string, mixed> $report AdsDashboardAnalytics::report()
     * @param array<int, array{0: string, 1: string}> $audit
     */
    public function __construct(private readonly array $report, private readonly array $audit)
    {
    }

    public function sheets(): array
    {
        $generated = 'Gerado em ' . $this->report['generated_at']->format('d/m/Y H:i');
        $sheets    = [];

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $sheets[] = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => self::BRAND, 'description' => $description, 'generated' => $generated], $options));
        };

        $this->summarySheet($add);
        $this->flowSheet($add);
        $this->queueSheet($add);
        $this->historySheet($add);

        $sheets[] = new DashboardTableSheet('Controle Exportacao', ['Campo', 'Valor'], $this->audit, [
            'brand' => self::BRAND, 'description' => 'Quem gerou, quando e com quais filtros.', 'generated' => $generated,
            'tab'   => DashboardTheme::TEAL, 'widths' => ['A' => 28, 'B' => 60],
        ]);

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => 'ADS solicitadas',
            'description'    => 'Resumo, fluxo diário, fila atual e histórico de ADS',
            'subject'        => 'Engenharia',
        ];
    }

    private function summarySheet(callable $add): void
    {
        $section = $this->report['summary'];
        $s       = $section['data'] ?? null;

        $add(
            'Resumo',
            $s ? 'Números do período e da fila, nos filtros do painel.' : 'Indisponível: ' . $section['reason'],
            ['Indicador', 'Valor', 'Observação'],
            $s ? [
                ['Abertas no período', $s['opened_count'], 'Solicitações criadas no período'],
                ['Média de aberturas por dia', $s['opened_daily_avg'], ''],
                ['Média de entregas por dia', $s['delivered_daily_avg'], ''],
                ['Tempo médio de entrega', $s['delivered_avg_label'], 'Da solicitação à entrega, nas concluídas do período'],
                ['Em execução agora', $s['in_progress_now_count'], ''],
                ['Período (dias)', $s['period_days'], $s['period_start'] . ' a ' . $s['period_end']],
            ] : [['Resumo indisponível', null, $section['reason']]],
            ['tab' => DashboardTheme::BLUE, 'widths' => ['A' => 32, 'B' => 18, 'C' => 56], 'center' => ['B']],
        );
    }

    private function flowSheet(callable $add): void
    {
        $section = $this->report['flow'];
        $d       = $section['data'] ?? null;
        $rows    = [];

        foreach (($d['date_keys'] ?? []) as $i => $key) {
            $rows[] = [Date::dateTimeToExcel(Carbon::parse($key)->startOfDay()), $d['requested'][$i], $d['delivered'][$i], $d['open_backlog'][$i], $d['overdue_backlog'][$i]];
        }

        $add(
            'Fluxo',
            $d ? 'Solicitadas, concluídas e acumulado em aberto/atrasadas por ' . (($d['bucket_label'] ?? '') === 'mensal' ? 'mês' : 'dia') . '.' : 'Indisponível: ' . $section['reason'],
            ['Período', 'Solicitadas', 'Concluídas', 'Acumulado em aberto', 'Atrasadas (>24h)'],
            $rows ?: [[null, null, null, null, $d ? null : $section['reason']]],
            [
                'tab'     => DashboardTheme::GREEN,
                'center'  => ['A', 'B', 'C', 'D', 'E'],
                'formats' => ['A' => 'dd/mm/yyyy', 'B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT],
                'widths'  => ['A' => 14, 'B' => 14, 'C' => 14, 'D' => 20, 'E' => 18],
            ],
        );
    }

    private function queueSheet(callable $add): void
    {
        $up   = $this->report['health'][AdsConnectionProbe::LIVE]['up'];
        $rows = $this->report['queue_rows'];

        $add(
            'Fila atual',
            $up ? count($rows) . ' requisição(ões) pendente(s), da mais recente para a mais antiga.' : 'Indisponível: ' . $this->report['queue']['reason'],
            ['ID', 'Nota', 'Empreiteira', 'Status', 'Solicitado em', 'Prazo alvo', 'Situação do prazo', 'Tempo decorrido', 'Link'],
            $up ? array_map(fn (array $r) => [
                $r['id'], $r['note_number'], $r['company_name'], $r['status_label'], $this->date($r['requested_at']), $this->date($r['deadline_at']),
                $r['deadline_label'], $r['elapsed_label'], $r['url'],
            ], $rows) : [[null, null, null, null, null, null, $this->report['queue']['reason'], null, null]],
            [
                'tab'     => DashboardTheme::AMBER,
                'center'  => ['A', 'B', 'D', 'E', 'F'],
                'formats' => ['E' => self::DATETIME, 'F' => self::DATETIME],
                'widths'  => ['A' => 9, 'B' => 14, 'C' => 32, 'D' => 14, 'E' => 17, 'F' => 17, 'G' => 30, 'H' => 16, 'I' => 40],
            ],
        );
    }

    private function historySheet(callable $add): void
    {
        $rows = $this->report['history_rows'];

        $add(
            'Histórico',
            count($rows) . ' ADS nos filtros do painel, da mais recente para a mais antiga.',
            ['ID', 'Nota', 'Empreiteira', 'Destinatário', 'Foi tácita?', 'Status', 'Quando pediu', 'Quando entregou', 'Prazo alvo', 'Situação do prazo', 'Tempo', 'Observação', 'Link'],
            array_map(fn (array $r) => [
                $r['id'], $r['note_number'], $r['company_name'], $r['recipient_name'], $r['is_tacit'] ? 'Sim' : 'Não', $r['status_label'],
                $this->date($r['requested_at']), $this->date($r['delivered_at']), $this->date($r['deadline_at']), $r['deadline_label'], $r['elapsed_label'], $r['description'], $r['url'],
            ], $rows),
            [
                'tab'     => DashboardTheme::TEAL,
                'center'  => ['A', 'B', 'E', 'F', 'G', 'H', 'I'],
                'formats' => ['G' => self::DATETIME, 'H' => self::DATETIME, 'I' => self::DATETIME],
                'wrap'    => ['L'],
                'widths'  => ['A' => 9, 'B' => 14, 'C' => 30, 'D' => 24, 'E' => 11, 'F' => 14, 'G' => 17, 'H' => 17, 'I' => 17, 'J' => 30, 'K' => 14, 'L' => 44, 'M' => 40],
            ],
        );
    }

    private function date(mixed $value): ?float
    {
        return $value ? Date::dateTimeToExcel(Carbon::parse($value)) : null;
    }
}
