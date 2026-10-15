<?php

namespace App\Exports\Reports;

use App\Services\Reports\PostWorkProcessReportService as Service;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

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
                ['Tipo de informe', 'Total', 'Concluídos no prazo', 'Concluídos fora do prazo', 'Em aberto no prazo', 'Em aberto estourados',
                    '% estouro (concluídos)', 'Com inconsistências',
                    'Estouro ' . $l['fiscal_dispatch'], 'Estouro ' . $l['fiscalization'], 'Estouro ' . $l['measurement'], 'Estouro Total',
                    'Média Total – concluídos (dias úteis)'],
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
            $summary   = $this->service->summarize($rows);
            $stageLate = fn (string $key) => $rows->filter(fn ($r) => $r['stages'][$key]['late'])->count();
            $avg       = $rows->where('current_stage', 'done')->pluck('stages.total.days')->filter(fn ($d) => $d !== null);

            return [
                $label,
                $summary['total'],
                $summary['done_on_time'],
                $summary['done_late'],
                $summary['open_on_time'],
                $summary['open_late'],
                $summary['late_rate_done'],
                $summary['with_issues'],
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
            ['Fonte dos dados', 'Final: marcos do consolidado note_inform_flows (o mesmo do log SAP; sincronizado de hora em hora e completo às 8h, 12h e 16h) · Parcial: campos do próprio parcial (aprovação do engenheiro = despacho da fiscalização; supervisão = fim da fiscalização e despacho da medição; pagamento = fim da medição) · D5: produções posteriores à conclusão da D5, completadas por is_supervisioned/is_payed.'],
            ['Despacho da medição', 'Intervalo entre o fim da fiscalização e o despacho da medição. Não tem prazo próprio, mas conta dentro do prazo Total.'],
            ['Concluído', 'Informe com Pagamento/Medição finalizado. A taxa de estouro considera só os concluídos; os em aberto estourados aparecem em coluna própria.'],
            ['Inconsistências', 'Datas ausentes ou fora de ordem (ex.: despacho anterior ao informe, conclusão antes do despacho, pagamento sem fiscalização). Os prazos dessas linhas podem não ser confiáveis.'],
            ['Data do informe', 'Final: informed_at · Parcial: created_at · D5: completed_at (somente productions posteriores).'],
            ['Dias úteis', 'Conta cada dia útil após a data de partida até a data final (inclusive). Exclui sábados, domingos e feriados cadastrados.'],
            ['Etapa em aberto', 'Sem data de conclusão: os dias contam até a data/hora da exportação (números em itálico).'],
            ['Cor cinza', 'Sem dados: data ausente ou etapa ainda não iniciada.'],
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
            ['Filtro - Somente em aberto', filter_var($this->filters['only_open'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'Sim' : 'Não'],
            ['Registros exportados', number_format($this->rows->count(), 0, ',', '.') . ' registros'],
        ];
    }

    private function matrixSheet(): PostWorkProcessStrictSheet
    {
        $matrix = $this->service->matrix($this->rows);
        $max    = Service::MATRIX_MAX_DAY;

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
