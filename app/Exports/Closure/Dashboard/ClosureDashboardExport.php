<?php

namespace App\Exports\Closure\Dashboard;

use App\Services\Closure\Analytics\{ClosureDashboardReport, ClosureDispatcherAnalytics};
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/**
 * Excel do Painel analítico do Encerramento: uma aba por bloco da tela e, em separado, as atividades (Ordens) detalhadas por status.
 * Abas: Resumo · Volumetria · Por status · Matriz · Ordens ativas · Atividades (uma por status com Ordens) · Parceiras · Pendências por usuário
 * · Obras críticas · Prioridades. Dados em App\Services\Closure\Analytics\ClosureDashboardReport; visual em DashboardTableSheet/DashboardSummarySheet.
 */
class ClosureDashboardExport implements WithMultipleSheets, WithProperties
{
    private const STATUS_TAB = [
        'IN_PROGRESS'      => DashboardTheme::BLUE,
        'ASSIGNED'         => '7C8CE8',
        'PAUSED'           => DashboardTheme::INK,
        'WAITING_PARTNER'  => DashboardTheme::AMBER,
        'DEPOSIT_BLOCKED'  => DashboardTheme::RED,
        'DEPOSIT_RELEASED' => DashboardTheme::GREEN,
        'FINISHED'         => DashboardTheme::TEAL,
        'WAITING'          => '0CD3F8',
        'AVAILABLE'        => '94A3B8',
    ];

    private const ORDER_HEADINGS = ['★', 'Tipo', 'Nota', 'Ordem', 'Status', 'Sinal', 'Responsável', 'Empresa (posse atual)', 'No status desde', 'Dias no status'];

    /** @param array<string, mixed> $report ClosureDashboardReport::build() */
    public function __construct(private readonly array $report, private readonly string $generatedBy)
    {
    }

    public function sheets(): array
    {
        $generated = 'Gerado em ' . $this->report['generated_at']->format('d/m/Y H:i');
        $tables    = $this->tables($generated);

        $tabs = array_map(fn (DashboardTableSheet $sheet) => ['title' => $sheet->title(), 'description' => $this->descriptions[$sheet->title()] ?? ''], $tables);

        return array_merge([new DashboardSummarySheet($this->report, $this->generatedBy, $tabs)], $tables);
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->generatedBy,
            'title'          => 'Painel analítico do Encerramento',
            'description'    => 'Volumetria, status, matriz, atividades por status, interação com a parceira, prioridades e obras críticas',
            'subject'        => 'Encerramento',
        ];
    }

    /** @var array<string, string> descrição de cada aba (índice da aba Resumo) */
    private array $descriptions = [];

    /** @return array<int, DashboardTableSheet> */
    private function tables(string $generated): array
    {
        $r      = $this->report;
        $sheets = [];

        $add = function (string $title, string $description, array $headings, array $rows, array $options = []) use (&$sheets, $generated) {
            $this->descriptions[mb_substr($title, 0, 31)] = $description;
            $sheets[]                                     = new DashboardTableSheet($title, $headings, $rows, array_merge(['description' => $description, 'generated' => $generated], $options));
        };

        // Volumetria por usuário
        $volume = $r['volume'];
        $add(
            'Volumetria',
            'Ordens ativas por usuário, empilhadas por tipo (P/M/F).',
            ['Usuário', 'Passivo', 'Meta atual', 'Meta futura', 'Sem tipo', 'Total', '% do total'],
            array_merge($volume['rows'], [$volume['total']]),
            [
                'tab'      => DashboardTheme::TEAL, 'totalRows' => [count($volume['rows'])], 'center' => ['B', 'C', 'D', 'E', 'F'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => '0.0"%"'],
                'dataBars' => ['F' => DashboardTheme::TEAL],
                'chart'    => ['type' => 'stackedBar', 'title' => 'Volumetria por usuário', 'category' => 'A', 'series' => ['B', 'C', 'D', 'E'],
                    'colors'          => [DashboardTheme::RED, DashboardTheme::GREEN, DashboardTheme::BLUE, '94A3B8'], 'rows' => count($volume['rows']), 'from' => 'I4', 'to' => 'S24'],
            ],
        );

        // Ordens por status
        $add(
            'Por status',
            'Distribuição do estoque ativo por situação operacional.',
            ['Status', 'Ordens', '% do total'],
            $r['status'],
            [
                'tab'   => DashboardTheme::BLUE, 'center' => ['B'], 'formats' => ['B' => DashboardTableSheet::INT, 'C' => '0.0"%"'], 'dataBars' => ['B' => DashboardTheme::BLUE],
                'chart' => ['type' => 'doughnut', 'title' => 'Ordens por status', 'category' => 'A', 'series' => ['B'],
                    'colors'       => ['263CC8', 'A8B1E9', '212E3E', 'F7D200', 'E32C2C', '0F8A77', '225E66', '0CD3F8', '91AFB3'], 'rows' => count($r['status']), 'from' => 'E4', 'to' => 'M22'],
            ],
        );

        // Matriz usuário × tipo × status
        $matrix     = $r['matrix'];
        $rowCount   = count($matrix['rows']);
        $statusCols = array_map(fn (string $code) => ClosureDashboardReport::statusLabel($code), ClosureDispatcherAnalytics::MATRIX_STATUSES);
        $numberCols = range('C', 'L');
        $add(
            'Matriz',
            'Usuário × tipo × status (a contagem de cada célula da matriz da tela, separada por tipo).',
            array_merge(['Usuário', 'Tipo'], $statusCols, ['Total']),
            array_merge($matrix['rows'], $matrix['totals']),
            [
                'tab'       => DashboardTheme::TEAL, 'center' => array_merge(['B'], $numberCols),
                'formats'   => array_fill_keys($numberCols, DashboardTableSheet::INT), 'dataBars' => ['L' => DashboardTheme::TEAL],
                'totalRows' => range($rowCount, $rowCount + count($matrix['totals']) - 1),
                'widths'    => ['A' => 30],
            ],
        );

        // Ordens ativas e atividades por status
        $orderOptions = fn (array $orders) => [
            'center'   => ['A', 'B', 'F', 'J'], 'formats' => ['J' => DashboardTableSheet::INT], 'dataBars' => ['J' => DashboardTheme::BLUE],
            'rowFills' => collect($orders)->map(fn ($o) => DashboardTheme::SIGNAL_ROW[$o['signal_key']] ?? null)->filter()->all(),
            'rowEdges' => collect($orders)->map(fn ($o) => DashboardTheme::SIGNAL_EDGE[$o['signal_key']] ?? null)->filter()->all(),
            'widths'   => ['A' => 6, 'B' => 8, 'C' => 14, 'D' => 17, 'E' => 20, 'F' => 29, 'G' => 28, 'H' => 32, 'I' => 18, 'J' => 12],
        ];

        $orderRows = fn (array $orders) => array_map(fn ($o) => [$o['priority'] ? '★' : '', $o['type'], $o['note'], $o['ordem'], $o['status'], $o['signal'], $o['responsible'], $o['company'], $o['since'], $o['days']], $orders);

        $add(
            'Ordens ativas',
            'Todas as Ordens ativas (a tela mostra só as 50 primeiras). A cor da linha é o sinal: azul atribuída · verde concluída · vermelho +24h sem baixa no SAP · amarelo na pilha sem atribuição.',
            self::ORDER_HEADINGS,
            $orderRows($r['orders']),
            $orderOptions($r['orders']) + ['tab' => DashboardTheme::GREEN],
        );

        foreach ($r['orders_by_status'] as $code => $orders) {
            $label = ClosureDashboardReport::statusLabel($code);
            $add(
                'Atividades · ' . $label,
                count($orders) . ' atividade(s) em "' . $label . '", da mais antiga para a mais recente no status.',
                self::ORDER_HEADINGS,
                $orderRows($orders),
                $orderOptions($orders) + ['tab' => self::STATUS_TAB[$code] ?? DashboardTheme::TEAL],
            );
        }

        // Interação com a parceira
        $add(
            'Parceiras',
            'Pendências enviadas a cada parceira e o tempo de espera por resposta (em dias corridos).',
            ['Parceira', 'Pendências abertas', 'Aguardando resposta', 'Espera média (dias)', 'Maior espera (dias)', 'Devoluções', 'Respondidas', 'Resposta média (dias)'],
            $r['partners'],
            [
                'tab'      => DashboardTheme::AMBER, 'center' => ['B', 'C', 'F', 'G'],
                'formats'  => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => '0.0', 'E' => '0.0', 'F' => DashboardTableSheet::INT, 'G' => DashboardTableSheet::INT, 'H' => '0.0'],
                'dataBars' => ['C' => DashboardTheme::AMBER, 'E' => DashboardTheme::RED], 'widths' => ['A' => 34],
            ],
        );

        $add(
            'Pendências por usuário',
            'Pendências enviadas por cada usuário do Encerramento e a resposta média da parceira (em dias corridos).',
            ['Usuário', 'Pendências enviadas', 'Aguardando resposta', 'Respondidas', 'Resposta média (dias)'],
            $r['users'],
            [
                'tab'      => DashboardTheme::AMBER, 'center' => ['B', 'C', 'D'], 'formats' => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => '0.0'],
                'dataBars' => ['B' => DashboardTheme::TEAL], 'widths' => ['A' => 34],
            ],
        );

        $add(
            'Obras críticas',
            'Obras com Ordens P paradas no status ou pendência parada com a parceira há mais dias que o limite do painel (' . ($r['filters']['Obra crítica: parada há (dias)'] ?? '7') . ').',
            ['Nota', 'Ordens abertas', 'Ordens P', 'P parados', 'Passivo mais antigo (dias)', 'Pendências paradas', 'Pendência parada há (dias)', 'Motivo'],
            $r['critical'],
            [
                'tab'      => DashboardTheme::RED, 'center' => ['B', 'C', 'D', 'E', 'F', 'G'], 'formats' => ['B' => DashboardTableSheet::INT, 'C' => DashboardTableSheet::INT, 'D' => DashboardTableSheet::INT, 'E' => DashboardTableSheet::INT, 'F' => DashboardTableSheet::INT, 'G' => DashboardTableSheet::INT],
                'dataBars' => ['E' => DashboardTheme::RED, 'G' => DashboardTheme::RED], 'widths' => ['A' => 16, 'H' => 38],
            ],
        );

        $add(
            'Prioridades',
            'Pedidos de prioridade ainda sem decisão do despachante.',
            ['Nota', 'Solicitante', 'Motivo', 'Solicitado em', 'Há (dias)'],
            $r['priorities'],
            [
                'tab'    => DashboardTheme::RED, 'center' => ['D', 'E'], 'formats' => ['E' => DashboardTableSheet::INT], 'wrap' => ['C'],
                'widths' => ['A' => 16, 'B' => 28, 'C' => 70, 'D' => 18, 'E' => 12],
            ],
        );

        return $sheets;
    }
}
