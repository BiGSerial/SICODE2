<?php

namespace App\Exports\Engineers;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel de "Informes sem saldo no PEP": uma aba por bloco da tela (Pendentes, Tratados, Por região, Por empresa, Fluxo diário), no
 * mesmo visual dos demais painéis. Os dados vêm de PepAlertsAnalytics::report(); este arquivo só formata.
 */
class PepAlertsExport implements WithMultipleSheets, WithProperties
{
    private const BRAND = 'SICODE · Informes sem saldo no PEP';

    private const DATETIME = 'dd/mm/yyyy hh:mm';

    private const MONEY = '"R$" #,##0.00;-"R$" #,##0.00;"–"';

    /** @var array<string, string> */
    private array $descriptions = [];

    /**
     * @param array<string, mixed> $report PepAlertsAnalytics::report()
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
            'title'          => 'Informes sem saldo no PEP',
            'description'    => 'Pilha de pendentes, histórico de tratados, ranking por região e empresa e fluxo diário',
            'subject'        => 'Engenharia',
        ];
    }

    /** @return array<string, string> */
    private function filterLabels(): array
    {
        $f = $this->report['filters'];

        return [
            'Solicitado por'   => $this->requester,
            'Empreiteira'      => $this->companyLabel ?? 'Todas',
            'Região'           => $f['region'] ?: 'Todas',
            'Período'          => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y'),
            'Pendentes'        => 'Situação de agora (não depende do período)',
            'Valor'            => 'ADS do informe (parcial: valor da parcial; final: ADS vinculada)',
            'Saída automática' => 'Saldo cobriu o pedido por mais de 48h sem ação do engenheiro',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $s   = $this->report['summary'];
        $dec = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.');

        return [
            'title'    => self::BRAND,
            'subtitle' => 'Informes que entraram sem saldo disponível no PEP (ou com ADS acima do saldo).',
            'sections' => [
                ['Pilha de agora', [
                    ['Pendentes', $s['open'], $s['no_balance'] . ' sem saldo · ' . $s['ads_exceeds'] . ' ADS acima', DashboardTheme::RED],
                    ['Valor em espera', 'R$ ' . number_format($s['open_value'], 2, ',', '.'), $s['with_value'] . ' com ADS', DashboardTheme::BLUE],
                    ['Mais antiga', $s['oldest'] === null ? '—' : $s['oldest'] . ' d', 'dias na lista', DashboardTheme::RED],
                    ['Idade média', $s['average'] === null ? '—' : $dec($s['average']) . ' d', 'dias na lista', DashboardTheme::INK],
                    ['Saldo já cobre', $s['covered'], 'saem sozinhos após 48h', DashboardTheme::GREEN],
                ]],
                ['Fluxo do período', [
                    ['Entraram', $s['entered'], 'no período', DashboardTheme::BLUE],
                    ['Tratados', $s['handled'], $s['handled_eng'] . ' pelo engenheiro · ' . $s['handled_auto'] . ' automáticos', DashboardTheme::GREEN],
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

        $this->openSheet($add);
        $this->historySheet($add);
        $this->rankingSheet($add, 'Por região', 'by_region', 'Região');
        $this->rankingSheet($add, 'Por empresa', 'by_company', 'Empreiteira');
        $this->flowSheet($add);

        return $sheets;
    }

    private function openSheet(callable $add): void
    {
        $open = $this->report['open'];

        $add(
            'Pendentes',
            count($open) . ' informe(s) aguardando tratamento, do mais antigo para o mais recente.',
            ['Obra', 'Informe', 'Escopo', 'Região', 'Empreiteira', 'Valor (ADS)', 'Status do informe', 'Motivo', 'Entrada', 'Dias na lista', 'Saldo cobre desde'],
            array_map(fn (array $r) => [
                $r['note'], $r['type_label'], $r['scope_label'], $r['region'] ?? '—', $r['company'] ?? '—', $r['value'], $r['status'], $r['reason_label'],
                $this->date($r['created_at']), $r['age_days'], $r['covered_since'] ? $this->date($r['covered_since']) : null,
            ], $open),
            [
                'tab'      => DashboardTheme::RED,
                'center'   => ['A', 'B', 'C', 'D', 'I', 'J', 'K'],
                'formats'  => ['F' => self::MONEY, 'I' => self::DATETIME, 'J' => DashboardTableSheet::INT, 'K' => self::DATETIME],
                'dataBars' => ['J' => DashboardTheme::RED],
                'widths'   => ['A' => 14, 'B' => 11, 'C' => 11, 'D' => 14, 'E' => 30, 'F' => 16, 'G' => 24, 'H' => 20, 'I' => 17, 'J' => 14, 'K' => 18],
            ],
        );
    }

    private function historySheet(callable $add): void
    {
        $history = $this->report['history'];

        $add(
            'Tratados',
            count($history) . ' informe(s) tratado(s) no período, do mais recente para o mais antigo.',
            ['Obra', 'Informe', 'Escopo', 'Região', 'Empreiteira', 'Valor (ADS)', 'Motivo', 'Entrada', 'Tratado em', 'Dias até tratar', 'Tratado por', 'Observação'],
            array_map(fn (array $r) => [
                $r['note'], $r['type_label'], $r['scope_label'], $r['region'] ?? '—', $r['company'] ?? '—', $r['value'], $r['reason_label'],
                $this->date($r['created_at']), $this->date($r['handled_at']), $r['age_days'], $r['handler'] ?? '—', $r['notes'],
            ], $history),
            [
                'tab'      => DashboardTheme::GREEN,
                'center'   => ['A', 'B', 'C', 'D', 'H', 'I', 'J'],
                'formats'  => ['F' => self::MONEY, 'H' => self::DATETIME, 'I' => self::DATETIME, 'J' => DashboardTableSheet::INT],
                'dataBars' => ['J' => DashboardTheme::BLUE],
                'wrap'     => ['L'],
                'widths'   => ['A' => 14, 'B' => 11, 'C' => 11, 'D' => 14, 'E' => 30, 'F' => 16, 'G' => 20, 'H' => 17, 'I' => 17, 'J' => 16, 'K' => 26, 'L' => 44],
            ],
        );
    }

    private function rankingSheet(callable $add, string $title, string $key, string $heading): void
    {
        $rows = $this->report[$key];

        $add(
            $title,
            count($rows) . ' linha(s): pendentes de agora por ' . mb_strtolower($heading) . ', com valor de ADS e o item mais antigo.',
            [$heading, 'Pendentes', 'Valor (ADS)', 'Mais antigo (dias)'],
            array_map(fn (array $r) => [$r['label'], $r['total'], $r['value'], $r['oldest']], $rows),
            [
                'tab'      => DashboardTheme::BLUE,
                'center'   => ['B', 'D'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => self::MONEY, 'D' => DashboardTableSheet::INT],
                'dataBars' => ['B' => DashboardTheme::RED],
                'widths'   => ['A' => 34, 'B' => 14, 'C' => 18, 'D' => 20],
            ],
        );
    }

    private function flowSheet(callable $add): void
    {
        $d    = $this->report['daily'];
        $rows = [];

        foreach ($d['dates'] as $i => $date) {
            $rows[] = [Date::dateTimeToExcel(Carbon::parse($date)->startOfDay()), $d['entered'][$i], $d['handled'][$i]];
        }

        $count = count($rows);

        $add(
            'Fluxo diário',
            'Informes que entraram na lista e tratados (inclui saída automática), por dia do período.',
            ['Dia', 'Entraram', 'Tratados'],
            $rows,
            [
                'tab'     => DashboardTheme::TEAL,
                'center'  => ['A', 'B', 'C'],
                'formats' => ['A' => 'dd/mm/yyyy', 'B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT],
                'widths'  => ['A' => 14, 'B' => 14, 'C' => 14],
                'chart'   => [
                    'type'     => 'line',
                    'title'    => 'Entraram x Tratados por dia',
                    'category' => 'A',
                    'series'   => ['B', 'C'],
                    'colors'   => [DashboardTheme::RED, DashboardTheme::GREEN],
                    'rows'     => $count,
                    'from'     => 'E4',
                    'to'       => 'N22',
                ],
            ],
        );
    }

    private function date(\DateTimeInterface $date): float
    {
        return Date::dateTimeToExcel($date);
    }
}
