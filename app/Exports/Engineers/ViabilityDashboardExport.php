<?php

namespace App\Exports\Engineers;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel do painel de Viabilidade: uma aba por bloco da tela (Em viabilidade, Por empresa, Motivos de rejeição, Fluxo diário), no mesmo
 * visual dos demais painéis. Os dados vêm de ViabilityDashboardAnalytics::report(); este arquivo só formata.
 */
class ViabilityDashboardExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Painel de viabilidade';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    private const MONEY = '"R$" #,##0.00;-"R$" #,##0.00;"–"';

    private const PCT = '0.0"%"';

    private const DAYS = '#,##0.0;-#,##0.0;"–"';

    /** @var array<string, string> */
    private array $descriptions = [];

    /** @param array<string, mixed> $report ViabilityDashboardAnalytics::report() */
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
            'title'          => 'Painel de viabilidade',
            'description'    => 'Fila de viabilidades em aberto, produtividade por empresa, motivos de rejeição e fluxo diário',
            'subject'        => 'Engenharia',
        ];
    }

    /** @return array<string, string> */
    private function filterLabels(): array
    {
        $f = $this->report['filters'];

        return [
            'Solicitado por' => $this->requester,
            'Empreiteira'    => $this->companyLabel ?? 'Todas',
            'Período'        => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y'),
            'Em viabilidade' => 'Situação de agora (não depende do período); fora do prazo = mais de 7 dias desde o envio',
            'Fechamento'     => 'Data de retorno (ou de conclusão) da viabilidade concluída',
            'Realizada'      => 'Não tácita, ou tácita com justificativa concedida',
            'Não realizada'  => 'Tácita sem justificativa ou com justificativa indeferida; multa estimada de 1% do valor',
            'Retorno médio'  => 'Dias entre o envio e o retorno das concluídas não tácitas',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $s   = $this->report['summary'];
        $dec = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');
        $brl = fn (float $v) => 'R$ ' . number_format($v, 2, ',', '.');

        return [
            'title'    => self::BRAND,
            'subtitle' => 'O que está com a Engenharia agora e como o período fechou.',
            'sections' => [
                ['Em viabilidade (agora)', [
                    ['Em aberto', $s['open'], $s['open_late'] . ' fora do prazo', DashboardTheme::BLUE],
                    ['Valor em aberto', $brl($s['open_value']), 'valor das viabilidades abertas', DashboardTheme::INK],
                    ['Mais antiga', $s['oldest'] === null ? '—' : $s['oldest'] . ' d', 'dias desde o envio', DashboardTheme::RED],
                    ['Idade média', $s['average'] === null ? '—' : $dec($s['average']) . ' d', 'dias desde o envio', DashboardTheme::INK],
                ]],
                ['Período', [
                    ['Entradas', $s['entered'], 'viabilidades enviadas', DashboardTheme::BLUE],
                    ['Realizadas', $s['realized'], $brl($s['realized_value']), DashboardTheme::GREEN],
                    ['Não realizadas', $s['not_realized'], 'multa estimada ' . $brl($s['penalty']), DashboardTheme::RED],
                    ['Rejeitadas', $s['rejected'], 'formulários rejeitados', DashboardTheme::TEAL],
                    ['Retorno médio', $s['sla_days'] === null ? '—' : $dec($s['sla_days']) . ' d', $s['sla_count'] . ' no cálculo', DashboardTheme::INK],
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

        $this->pileSheet($add);
        $this->companySheet($add);
        $this->reasonSheet($add);
        $this->flowSheet($add);

        return $sheets;
    }

    private function pileSheet(callable $add): void
    {
        $pile = $this->report['pile'];

        $add(
            'Em viabilidade',
            count($pile) . ' viabilidade(s) com a Engenharia, da mais antiga para a mais recente.',
            ['Nota', 'Município', 'Rubrica', 'Empreiteira', 'Engenheiro', 'Enviada em', 'Dias em aberto', 'Fora do prazo', 'Valor'],
            array_map(fn (array $r) => [
                $r['note'], $r['city'] ?? '—', $r['rubrica'] ?? '—', $r['company'] ?? '—', $r['engineer'] ?? '—',
                $r['sended_at'] ? Date::dateTimeToExcel($r['sended_at']) : null, $r['age_days'], $r['late'] ? 'Sim' : 'Não', $r['value'],
            ], $pile),
            [
                'tab'      => DashboardTheme::BLUE,
                'center'   => ['A', 'C', 'F', 'G', 'H'],
                'formats'  => ['F' => self::DATETIME, 'G' => DashboardTableSheet::INT, 'I' => self::MONEY],
                'dataBars' => ['G' => DashboardTheme::RED],
                'widths'   => ['A' => 14, 'B' => 22, 'C' => 14, 'D' => 30, 'E' => 26, 'F' => 17, 'G' => 15, 'H' => 14, 'I' => 16],
            ],
        );
    }

    private function companySheet(callable $add): void
    {
        $rows = $this->report['by_company'];

        $add(
            'Por empresa',
            count($rows) . ' empresa(s): entradas e rejeições do período, fechamentos pela data de retorno e a fila de agora.',
            ['Empreiteira', 'Entradas', 'Realizadas', 'Não realizadas', 'Rejeitadas', 'Em viabilidade', '% realização', 'Retorno médio (dias)', 'Valor realizado'],
            array_map(fn (array $r) => [$r['label'], $r['entered'], $r['realized'], $r['not_realized'], $r['rejected'], $r['in_progress'], $r['rate'], $r['sla_days'], $r['realized_value']], $rows),
            [
                'tab'      => DashboardTheme::GREEN,
                'center'   => ['B', 'C', 'D', 'E', 'F', 'G', 'H'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => self::PCT, 'H' => self::DAYS, 'I' => self::MONEY],
                'dataBars' => ['B' => DashboardTheme::BLUE, 'C' => DashboardTheme::GREEN],
                'widths'   => ['A' => 34, 'B' => 12, 'C' => 13, 'D' => 15, 'E' => 13, 'F' => 15, 'G' => 14, 'H' => 20, 'I' => 18],
            ],
        );
    }

    private function reasonSheet(callable $add): void
    {
        $rows  = $this->report['reasons'];
        $total = max(1, (int) $this->report['summary']['rejected']);

        $add(
            'Motivos de rejeição',
            $this->report['summary']['rejected'] . ' rejeição(ões) no período, por motivo.',
            ['Motivo', 'Rejeições', '% do total'],
            array_map(fn (array $r) => [$r['label'], $r['total'], round($r['total'] / $total * 100, 1)], $rows),
            [
                'tab'      => DashboardTheme::AMBER,
                'center'   => ['B', 'C'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => self::PCT],
                'dataBars' => ['B' => DashboardTheme::RED],
                'widths'   => ['A' => 50, 'B' => 13, 'C' => 13],
            ],
        );
    }

    private function flowSheet(callable $add): void
    {
        $d    = $this->report['daily'];
        $rows = [];

        foreach ($d['dates'] as $i => $date) {
            $rows[] = [Date::dateTimeToExcel(Carbon::parse($date)->startOfDay()), $d['entered'][$i], $d['closed'][$i]];
        }

        $add(
            'Fluxo diário',
            'Viabilidades que entraram (envio) e que foram fechadas (retorno/conclusão), por dia do período.',
            ['Dia', 'Entradas', 'Fechadas'],
            $rows,
            [
                'tab'     => DashboardTheme::TEAL,
                'center'  => ['A', 'B', 'C'],
                'formats' => ['A' => 'dd/mm/yyyy', 'B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT],
                'widths'  => ['A' => 14, 'B' => 14, 'C' => 14],
                'chart'   => [
                    'type'     => 'line',
                    'title'    => 'Entradas x Fechadas por dia',
                    'category' => 'A',
                    'series'   => ['B', 'C'],
                    'colors'   => [DashboardTheme::BLUE, DashboardTheme::GREEN],
                    'rows'     => count($rows),
                    'from'     => 'E4',
                    'to'       => 'N22',
                ],
            ],
        );
    }
}
