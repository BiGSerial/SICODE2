<?php

namespace App\Exports\Dispatchs;

use App\Exports\Closure\Dashboard\{DashboardTableSheet, DashboardTheme};
use App\Services\Reclaims\{ReclaimRowPresenter, ReturnD5Query};
use App\Support\Ops\RowState;
use Illuminate\Support\{Carbon, Collection};
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Excel da lista de Retorno Interno (RI) do Despacho: uma aba por bloco (Resumo, Retornos, Por origem, Por responsável) e `Controle Exportacao`,
 * no mesmo visual dos demais painéis. Os retornos vêm da mesma consulta da tela (ReturnD5Query) e cada linha é lida pelo ReclaimRowPresenter,
 * a fonte única da leitura de um retorno; este arquivo só agrega e formata. A cor da linha segue a situação da tela (azul = com usuário,
 * âmbar = pilha da empresa, sem cor = sem atribuição).
 */
class ReturnD5Export implements WithMultipleSheets, WithProperties
{
    private const DATETIME = 'dd/mm/yyyy hh:mm';

    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /**
     * @param Collection<int, \App\Models\Reclaim> $reclaims com as relações de ReclaimRowPresenter::RELATIONS
     * @param array<int, array{0: string, 1: string}> $audit
     */
    public function __construct(
        Collection $reclaims,
        private readonly string $serviceName,
        private readonly string $requester,
        private readonly array $audit,
        private readonly ?Carbon $generatedAt = null,
    ) {
        $now        = $this->generatedAt ?? now();
        $this->rows = $reclaims->map(fn ($reclaim) => ReclaimRowPresenter::row($reclaim) + ReclaimRowPresenter::listRow($reclaim, $now) + [
            'days' => (int) max(0, $reclaim->created_at?->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) ?? 0),
        ])->all();
    }

    public function sheets(): array
    {
        $now       = $this->generatedAt ?? now();
        $generated = 'Gerado em ' . $now->format('d/m/Y H:i');
        $brand     = 'SICODE · Retorno interno de ' . $this->serviceName;
        $sheets    = [];

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated, $brand) {
            $sheets[] = new DashboardTableSheet($title, $headings, $rows, array_merge(['brand' => $brand, 'description' => $description, 'generated' => $generated], $options));
        };

        $this->summarySheet($add);
        $this->detailSheet($add);
        $this->originSheet($add);
        $this->responsibleSheet($add);

        $sheets[] = new DashboardTableSheet('Controle Exportacao', ['Campo', 'Valor'], $this->audit, [
            'brand' => $brand, 'description' => 'Quem gerou, quando e com quais filtros.', 'generated' => $generated,
            'tab'   => DashboardTheme::TEAL, 'widths' => ['A' => 30, 'B' => 70],
        ]);

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->requester,
            'title'          => 'Retorno interno de ' . $this->serviceName,
            'description'    => 'Retornos internos (RI) em aberto, com origem, situação e responsável',
            'subject'        => 'Despacho',
        ];
    }

    /** Resumo: as três primeiras linhas são a situação (alimentam o gráfico de rosca); depois o total e os alertas. */
    private function summarySheet(callable $add): void
    {
        $rows  = collect($this->rows);
        $total = $rows->count();
        $pct   = fn (int $n) => $total > 0 ? round($n / $total * 100, 1) : 0;
        $state = fn (string $s) => $rows->where('state', $s)->count();

        $free    = $state(RowState::FREE);
        $user    = $state(RowState::ASSIGNED);
        $stack   = $state(RowState::STACK);
        $stale   = $rows->where('staleTone', 'red')->count();
        $express = $rows->where('express', true)->count();
        $oldest  = $rows->max('days');

        $add(
            'Resumo',
            $total . ' retorno(s) interno(s) em aberto nos filtros do painel.',
            ['Indicador', 'Retornos', '% do total', 'Como ler'],
            [
                ['Sem atribuição', $free, $pct($free), 'Ainda sem atividade: podem ser atribuídos agora'],
                ['Com usuário', $user, $pct($user), 'Atividade atribuída a um usuário e em andamento'],
                ['Na pilha da empresa', $stack, $pct($stack), 'Atividade na pilha da empresa, ainda sem usuário'],
                ['Total de retornos', $total, $total > 0 ? 100 : 0, 'Retornos em aberto nos filtros'],
                ['Sem andamento há mais de 24 h', $stale, $pct($stale), 'Retorno parado: a atividade não se moveu nas últimas 24 horas'],
                ['Notas expressas', $express, $pct($express), 'Nota com prazo de execução de 25 dias'],
                ['Retorno mais antigo (dias)', $oldest, null, 'Dias desde o envio do retorno mais antigo'],
            ],
            [
                'tab'       => DashboardTheme::BLUE,
                'center'    => ['B', 'C'],
                'formats'   => ['B' => DashboardTableSheet::INT, 'C' => '0.0"%"'],
                'totalRows' => [3],
                'dataBars'  => ['B' => DashboardTheme::BLUE],
                'widths'    => ['A' => 34, 'B' => 14, 'C' => 12, 'D' => 62],
                'chart'     => ['type' => 'doughnut', 'title' => 'Retornos por situação', 'category' => 'A', 'series' => ['B'], 'colors' => ['94A3B8', DashboardTheme::BLUE, DashboardTheme::AMBER], 'rows' => 3, 'from' => 'F4', 'to' => 'M20'],
            ],
        );
    }

    /** Detalhe: uma linha por retorno, do mais antigo para o mais recente (a mesma ordem da tela). */
    private function detailSheet(callable $add): void
    {
        $fills = [];
        $edges = [];

        foreach ($this->rows as $index => $row) {
            $signal = match ($row['state']) {
                RowState::ASSIGNED => 'assigned',
                RowState::STACK    => 'pile',
                default            => null,
            };

            if ($signal) {
                $fills[$index] = DashboardTheme::SIGNAL_ROW[$signal];
                $edges[$index] = DashboardTheme::SIGNAL_EDGE[$signal];
            }
        }

        $add(
            'Retornos',
            count($this->rows) . ' retorno(s) em aberto, do mais antigo para o mais recente. Linha azul = com usuário; amarela = na pilha da empresa; sem cor = sem atribuição.',
            ['Origem', 'Nota', 'Expressa', 'Rubrica', 'Município', 'Grupo 5', 'Material', 'Solicitante', 'Empresa solicitante', 'Categoria', 'Texto do retorno', 'Enviado em', 'Dias em aberto', 'Sem andamento +24 h', 'Status', 'Responsável', 'Empresa responsável', 'Atribuído em'],
            array_map(fn (array $r) => [
                $r['origin'], $r['note'], $r['express'] ? 'Sim' : 'Não', $r['rubrica'], $r['municipality'], $r['group5'], $r['material'], $r['requester'] ?: '—', $r['requester_company'] ?: '—',
                $r['category'], $r['text'], $this->date($r['received_at']), $r['days'], $r['staleTone'] === 'red' ? 'Sim' : 'Não', $r['status'],
                $r['responsible'] ?: '—', $r['responsible_company'] ?: '—', $this->date($r['att_at']),
            ], $this->rows),
            [
                'tab'      => DashboardTheme::TEAL,
                'center'   => ['A', 'B', 'C', 'L', 'M', 'N', 'O', 'R'],
                'formats'  => ['L' => self::DATETIME, 'M' => DashboardTableSheet::INT, 'R' => self::DATETIME],
                'dataBars' => ['M' => DashboardTheme::RED],
                'wrap'     => ['K'],
                'rowFills' => $fills,
                'rowEdges' => $edges,
                'widths'   => ['A' => 20, 'B' => 14, 'C' => 10, 'D' => 14, 'E' => 22, 'F' => 14, 'G' => 28, 'H' => 24, 'I' => 24, 'J' => 28, 'K' => 50, 'L' => 17, 'M' => 12, 'N' => 14, 'O' => 18, 'P' => 28, 'Q' => 26, 'R' => 17],
            ],
        );
    }

    /** Por origem: as quatro origens sempre aparecem (mesmo com zero), para a rosca e a leitura não mudarem de forma. */
    private function originSheet(callable $add): void
    {
        $rows  = collect($this->rows);
        $table = [];

        foreach (ReturnD5Query::ORIGINS as $label) {
            $group   = $rows->where('origin', $label);
            $table[] = [$label, $group->count(), $group->where('state', RowState::FREE)->count(), $group->where('staleTone', 'red')->count(), $group->max('days') ?? 0];
        }

        $add(
            'Por origem',
            'De onde veio cada retorno (Contratação, Análise de Projeto, Viabilidade, Órgão Externo).',
            ['Origem', 'Retornos', 'Sem atribuição', 'Sem andamento +24 h', 'Mais antigo (dias)'],
            $table,
            [
                'tab'      => DashboardTheme::AMBER,
                'center'   => ['B', 'C', 'D', 'E'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT],
                'dataBars' => ['B' => DashboardTheme::BLUE],
                'widths'   => ['A' => 26, 'B' => 14, 'C' => 16, 'D' => 20, 'E' => 18],
                'chart'    => ['type' => 'doughnut', 'title' => 'Retornos por origem', 'category' => 'A', 'series' => ['B'], 'colors' => ['94A3B8', DashboardTheme::AMBER, DashboardTheme::GREEN, DashboardTheme::BLUE], 'rows' => count($table), 'from' => 'G4', 'to' => 'N20'],
            ],
        );
    }

    /** Por responsável: quem está com o quê, do maior para o menor; os sem atribuição fecham a lista. */
    private function responsibleSheet(callable $add): void
    {
        $rows  = collect($this->rows);
        $table = $rows->where('hasActivity', true)
            ->groupBy(fn ($r) => ($r['responsible'] ?: 'Desconhecido') . '|' . ($r['responsible_company'] ?: '—'))
            ->map(function (Collection $group, string $key) {
                [$name, $company] = explode('|', $key, 2);

                return [$name, $company, $group->count(), $group->where('staleTone', 'red')->count(), $group->max('days')];
            })
            ->sortByDesc(fn ($r) => $r[2])->values()->all();

        $unassigned = $rows->where('hasActivity', false);

        if ($unassigned->isNotEmpty()) {
            $table[] = ['Sem atribuição', '—', $unassigned->count(), 0, $unassigned->max('days')];
        }

        $add(
            'Por responsável',
            count($table) . ' linha(s): retornos em aberto por responsável, com o mais antigo de cada um.',
            ['Responsável', 'Empresa', 'Retornos', 'Sem andamento +24 h', 'Mais antigo (dias)'],
            $table ?: [['Nenhum retorno', '—', 0, 0, 0]],
            [
                'tab'      => DashboardTheme::GREEN,
                'center'   => ['C', 'D', 'E'],
                'formats'  => ['C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT],
                'dataBars' => ['C' => DashboardTheme::BLUE, 'E' => DashboardTheme::RED],
                'widths'   => ['A' => 34, 'B' => 30, 'C' => 12, 'D' => 20, 'E' => 18],
            ],
        );
    }

    private function date(mixed $value): ?float
    {
        return $value ? Date::dateTimeToExcel(Carbon::parse($value)) : null;
    }
}
