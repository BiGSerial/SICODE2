<?php

namespace App\Exports\Reports;

use App\Services\Reports\PostWorkProcessReportService as Service;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class PostWorkProcessReportExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @param array<string, mixed> $filters
     * @param array<int, array<int, mixed>> $auditRows
     */
    public function __construct(
        private readonly Collection $rows,
        private readonly array $filters,
        private readonly array $auditRows
    ) {
        $this->service = app(Service::class);
    }

    private Service $service;

    public function sheets(): array
    {
        $l = $this->service->stageLabels();

        return [
            new PostWorkProcessStrictSheet(
                'Resumo',
                ['Tipo de informe', 'Total', 'No prazo', 'Estourados', 'Em andamento', '% estourados',
                    'Estouro ' . $l['fiscal_dispatch'], 'Estouro ' . $l['fiscalization'], 'Estouro ' . $l['measurement'], 'Estouro Total',
                    'Média Total (dias úteis)'],
                $this->summaryRows()
            ),
            new PostWorkProcessDetailSheet($this->rows, $this->service->limits(), $this->service->stageLabels()),
            $this->matrixSheet(),
            new PostWorkProcessStrictSheet(
                'Regras e Legenda',
                ['Item', 'Descrição'],
                $this->rulesRows()
            ),
            new PostWorkProcessStrictSheet(
                'Controle Exportacao',
                ['Campo', 'Valor'],
                array_merge($this->auditRows, $this->filterRows())
            ),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => 'Processo de Medição - Pós Obra',
            'description'    => 'Prazos por etapa (dias úteis) de Informe Final, Parcial e D5',
            'subject'        => 'Relatórios',
        ];
    }

    /** @return array<int, array<int, mixed>> */
    private function summaryRows(): array
    {
        $line = function (string $label, Collection $rows): array {
            $total = $rows->count();
            $late = $rows->where('overall', 'late')->count();
            $stageLate = fn (string $key) => $rows->filter(fn ($r) => $r['stages'][$key]['late'])->count();
            $avg = $rows->pluck('stages.total.days')->filter(fn ($d) => $d !== null);

            return [
                $label,
                $total,
                $rows->where('overall', 'on_time')->count(),
                $late,
                $rows->where('overall', 'open')->count(),
                $total ? round($late / $total * 100, 2) : 0,
                $stageLate('fiscal_dispatch'),
                $stageLate('fiscalization'),
                $stageLate('measurement'),
                $stageLate('total'),
                $avg->isEmpty() ? null : round($avg->avg(), 2),
            ];
        };

        return [
            $line('Informe Final', $this->rows->where('type', Service::TYPE_FINAL)),
            $line('Informe Parcial', $this->rows->where('type', Service::TYPE_PARTIAL)),
            $line('Informe D5', $this->rows->where('type', Service::TYPE_D5)),
            $line('TOTAL GERAL', $this->rows),
        ];
    }

    /** @return array<int, array<int, string>> */
    private function rulesRows(): array
    {
        $lim = $this->service->limits();
        $lab = $this->service->stageLabels();

        return [
            [$lab['fiscal_dispatch'], "Data do informe → despacho (dispatch_at) da production de Fiscalização. Prazo: {$lim['fiscal_dispatch']} dias úteis."],
            [$lab['fiscalization'], "dispatch_at → completed_at da production de Fiscalização. Prazo: {$lim['fiscalization']} dias úteis."],
            [$lab['measurement'], "dispatch_at → completed_at da production de Pagamento/Medição. Prazo: {$lim['measurement']} dias úteis."],
            ['Total', "Data do informe → completed_at do Pagamento/Medição. Prazo: {$lim['total']} dias úteis."],
            ['Prazos por região', 'Configuráveis por ruleset (SICODE_RULESET) em config/sicode.php › post_work_process.'],
            ['Matriz', 'Acumulador: linhas = etapa atual do informe; colunas = dias úteis desde a data do informe (concluídos: duração total). Final, Parcial e D5 mesclados.'],
            ['Data do informe', 'Final: informed_at · Parcial: created_at · D5: completed_at (somente productions posteriores).'],
            ['Dias úteis', 'Conta cada dia útil após a data de partida até a data final (inclusive). Exclui sábados, domingos e feriados cadastrados.'],
            ['Etapa em aberto', 'Sem data de conclusão: os dias contam até a data/hora da exportação (números em itálico).'],
            ['Cor verde', 'Etapa dentro do prazo.'],
            ['Cor vermelha', 'Etapa concluída fora do prazo.'],
            ['Cor âmbar', 'Etapa ainda em aberto e já além do prazo.'],
            ['Cor azul', 'Etapa em aberto, ainda dentro do prazo.'],
            ['Nº do informe', 'Final: id do informe de obra · Parcial: id do parcial · D5: id da nota D5 (número da nota D5 na coluna própria).'],
            ['Escopo', 'Rede / Ligação para informes finais com encerramento separado; Geral nos demais.'],
        ];
    }

    /** @return array<int, array<int, mixed>> */
    private function filterRows(): array
    {
        $type = ['all' => 'Todos', 'final' => 'Informe Final', 'partial' => 'Informe Parcial', 'd5' => 'Informe D5'][$this->filters['type'] ?? 'all'] ?? 'Todos';

        return [
            ['Filtro - Tipo de informe', $type],
            ['Filtro - Data do informe (de)', $this->filters['from'] ?? '---'],
            ['Filtro - Data do informe (até)', $this->filters['to'] ?? '---'],
            ['Filtro - Empresa', $this->filters['company_name'] ?? 'Todas'],
            ['Filtro - Nota', $this->filters['search'] ?? '---'],
            ['Filtro - Somente estourados', filter_var($this->filters['only_late'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'Sim' : 'Não'],
            ['Registros exportados', number_format($this->rows->count(), 0, ',', '.') . ' registros'],
        ];
    }

    private function matrixSheet(): PostWorkProcessStrictSheet
    {
        $matrix = $this->service->matrix($this->rows);
        $max = Service::MATRIX_MAX_DAY;

        $headings = ['Etapa atual', 'Prazo até (dias)'];
        foreach ($matrix['days'] as $d) {
            $headings[] = $d >= $max ? "{$d}+ dias" : "{$d} dias";
        }
        $headings[] = 'Total';

        $rows = [];
        foreach ($matrix['rows'] as $stage) {
            $rows[] = array_merge([$stage['label'], $stage['limit']], array_map(fn ($c) => $c['count'], $stage['cells']), [$stage['total']]);
        }
        $rows[] = array_merge(['TOTAL GERAL', null], array_values($matrix['col_totals']), [$matrix['total']]);

        return new PostWorkProcessStrictSheet('Matriz', $headings, $rows);
    }
}
