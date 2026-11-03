<?php

namespace App\Exports\Dispatchs\Dashboard;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Exports\Home\MyPanel\MyPanelSummarySheet;
use App\Exports\Reports\PostWorkProcessStrictSheet;
use App\Services\Dispatch\Dashboard\ActivityDashboardAnalytics as Data;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/**
 * Excel do Dashboard de atividade: uma aba por bloco da tela (mesmos números, vindos de `ActivityDashboardAnalytics::report()`), a pilha
 * crítica COMPLETA e a aba "Controle Exportacao". Este arquivo só formata. Datas são datas de verdade e prazos vão em dias.
 * Abas: Resumo · Tempo no status · Prazo real · Por colaborador · Pilha crítica · Aderência SATEL · Aderência própria · Controle Exportacao.
 */
class ActivityDashboardExport implements WithMultipleSheets, WithProperties
{
    private const DATE = 'dd/mm/yyyy';

    /** @var array<string, string> */
    private array $descriptions = [];

    /**
     * @param array<string, mixed>  $report  ActivityDashboardAnalytics::report()
     * @param array<string, string> $control rótulo => valor da aba Controle Exportacao
     */
    public function __construct(private readonly array $report, private readonly string $requester, private readonly array $control)
    {
    }

    private function brand(): string
    {
        return 'SICODE · Dashboard de ' . $this->report['service']->service;
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
            'title'          => 'Dashboard de ' . $this->report['service']->service,
            'description'    => 'Pilha por tempo e prazo, atribuição por colaborador, pilha crítica e aderência',
            'subject'        => 'Despacho',
        ];
    }

    /** @return array<string, mixed> */
    private function summaryLayout(): array
    {
        $s = $this->report['summary'];
        $f = $this->report['filters'];

        return [
            'title'    => $this->brand(),
            'subtitle' => 'Pilha de agora (' . $this->report['profile']['pile'] . ' ' . ucfirst($this->report['profile']['unit']) . '); aderência de ' . $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y') . ', nos filtros aplicados.',
            'sections' => [
                ['Pilha', [
                    ['Total na pilha', $s['total'], 'mais antiga há ' . $s['oldest_status_days'] . ' dia(s) no status', DashboardTheme::BLUE],
                    ['Em atribuição', $s['assigned'], 'com atividade aberta na atividade', DashboardTheme::GREEN],
                    ['Sem atribuição', $s['unassigned'], 'disponível para despacho', DashboardTheme::INK],
                    ['Prazo vencido', $s['overdue'], 'média no status: ' . number_format($s['avg_status_days'], 1, ',', '.') . ' dia(s)', DashboardTheme::RED],
                ]],
            ],
        ];
    }

    /** @return array<int, \Maatwebsite\Excel\Concerns\WithTitle> */
    private function tables(string $generated): array
    {
        $r      = $this->report;
        $sheets = [];
        $int    = DashboardTableSheet::INT;

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => $this->brand(), 'description' => $description, 'generated' => $generated], $options));
        };

        $profile = $r['profile'];
        $a       = $r['status_age'];
        $add(
            mb_substr($profile['time']['title'], 0, 31),
            ucfirst($profile['unit']) . ' da pilha por dias (' . mb_strtolower($profile['time']['basis']) . '), em atribuição e na pilha.',
            [$profile['time']['axis'], 'Em atribuição', 'Na pilha', 'Total'],
            array_map(fn ($l, $x, $y) => [$l, $x, $y, $x + $y], $a['labels'], $a['assigned'], $a['unassigned']),
            ['tab' => DashboardTheme::BLUE, 'center' => ['A', 'B', 'C', 'D'], 'formats' => ['B' => $int, 'C' => $int, 'D' => $int], 'dataBars' => ['D' => DashboardTheme::BLUE], 'widths' => ['A' => 16, 'B' => 16, 'C' => 14, 'D' => 12]],
        );

        $d    = $r['deadline'];
        $cols = array_keys($d['series']);
        $add(
            mb_substr($profile['deadline']['title'], 0, 31),
            ucfirst($profile['unit']) . ' da pilha por prazo e por quem está com elas. ' . $profile['deadline']['rule'],
            array_merge([$profile['deadline']['axis']], $cols, ['Total']),
            array_map(function ($label, $i) use ($d, $cols) {
                $vals = array_map(fn ($c) => $d['series'][$c][$i] ?? 0, $cols);

                return array_merge([$label], $vals, [array_sum($vals)]);
            }, $d['labels'], array_keys($d['labels'])),
            ['tab' => DashboardTheme::TEAL, 'center' => ['A', 'B', 'C', 'D', 'E', 'F'], 'formats' => ['B' => $int, 'C' => $int, 'D' => $int, 'E' => $int, 'F' => $int], 'widths' => ['A' => 18, 'B' => 12, 'C' => 12, 'D' => 16, 'E' => 18, 'F' => 12]],
        );

        $u = $r['users'];
        $add(
            'Por colaborador',
            'Atribuições abertas por colaborador, por tempo com o colaborador (os ' . Data::USER_LIMIT . ' com mais atribuições).',
            array_merge(['Colaborador'], Data::AGE_BUCKETS, ['Total']),
            array_map(function ($name, $i) use ($u) {
                $vals = array_map(fn ($b) => $u['buckets'][$b][$i] ?? 0, Data::AGE_BUCKETS);

                return array_merge([$name], $vals, [array_sum($vals)]);
            }, $u['labels'], array_keys($u['labels'])),
            ['tab' => DashboardTheme::GREEN, 'center' => ['B', 'C', 'D', 'E'], 'formats' => ['B' => $int, 'C' => $int, 'D' => $int, 'E' => $int], 'dataBars' => ['E' => DashboardTheme::GREEN], 'widths' => ['A' => 34, 'B' => 14, 'C' => 14, 'D' => 18, 'E' => 12]],
        );

        $add(
            'Pilha crítica',
            count($r['critical_all']) . ' nota(s) da pilha, do prazo mais consumido para o menos (a tela mostra as ' . Data::CRITICAL_LIMIT . ' primeiras).',
            ['Nota/OV', 'Tipo', 'Rubrica', 'Município', $profile['key'] === 'status' ? 'Data do status' : 'Data do informe', $profile['time']['column'] . ' (dias)', 'Prazo', 'Dias restantes', 'Situação', 'Responsável'],
            array_map(fn (array $x) => [$x['note'], $x['kind'], $x['rubrica'], $x['city'], $x['status_at'] ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($x['status_at']) : null, $x['status_days'], $x['deadline_label'], $x['days_left'], $x['assigned'] ? 'Em atribuição' : 'Na pilha', $x['assigned_user']], $r['critical_all']),
            ['tab' => DashboardTheme::RED, 'center' => ['B', 'E', 'F', 'G', 'H', 'I'], 'formats' => ['E' => self::DATE, 'F' => $int, 'H' => $int], 'widths' => ['A' => 16, 'B' => 8, 'C' => 22, 'D' => 24, 'E' => 14, 'F' => 14, 'G' => 20, 'H' => 14, 'I' => 16, 'J' => 28]],
        );

        $bands = $r['bands'];

        foreach (['satel' => 'Aderência SATEL', 'own' => 'Aderência própria'] as $key => $title) {
            $g = $bands['groups'][$key];
            $add(
                $title,
                'OVs concluídas por faixa de dias entre a data do status e a conclusão, por mês de conclusão. Primeira faixa: até ' . $bands['limit'] . ' dias.',
                array_merge(['Mês'], $bands['band_labels'], ['Total', '% na 1ª faixa']),
                array_map(fn ($m, $i) => array_merge([$m['label']], array_map(fn ($b) => $g['series'][$b][$i] ?? 0, range(0, 3)), [$m['total'], $m['pct']]), $g['months'], array_keys($g['months'])),
                ['tab' => DashboardTheme::TEAL, 'center' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'], 'formats' => ['B' => $int, 'C' => $int, 'D' => $int, 'E' => $int, 'F' => $int], 'widths' => ['A' => 12, 'B' => 16, 'C' => 16, 'D' => 16, 'E' => 16, 'F' => 10, 'G' => 16]],
            );
        }

        return $sheets;
    }
}
