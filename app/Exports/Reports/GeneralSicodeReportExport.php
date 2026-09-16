<?php

namespace App\Exports\Reports;

use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class GeneralSicodeReportExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param array<string,mixed> $report
     * @param array<string,mixed> $filters
     * @param array<int,array<int,mixed>> $auditRows
     */
    public function __construct(
        private readonly array $report,
        private readonly array $filters,
        private readonly array $auditRows
    ) {
    }

    public function sheets(): array
    {
        return [
            new StyledArraySheetExport(
                'Resumo Geral',
                ['Descrição', 'Quantidade', 'Valor', 'Aplicação Origem', 'Fonte', 'Critério'],
                $this->summaryRows()
            ),
            new StyledArraySheetExport(
                'Detalhe Medicao',
                ['Indicador', 'Quantidade', 'Valor', 'Observação'],
                $this->measurementRows()
            ),
            new StyledArraySheetExport(
                'Criterios',
                ['Linha', 'Quantidade', 'Valor', 'Observação'],
                $this->criteriaRows()
            ),
            new StyledArraySheetExport(
                'Filtros',
                ['Campo', 'Valor'],
                $this->filterRows()
            ),
            new StyledArraySheetExport(
                'Controle Exportacao',
                ['Campo', 'Valor'],
                $this->auditRows
            ),
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Relatório Geral SICODE',
            'description' => 'Consolidação operacional e financeira por atividade',
            'subject' => 'Relatórios',
            'company' => config('app.name', 'SICODE'),
        ];
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function summaryRows(): array
    {
        return collect($this->report['rows'] ?? [])
            ->map(fn (array $row) => [
                $row['description'] ?? '',
                (int) ($row['quantity'] ?? 0),
                round((float) ($row['value'] ?? 0), 2),
                $row['application'] ?? '',
                $row['source'] ?? '',
                $row['resolution'] ?? '',
            ])
            ->push([
                'TOTAL',
                (int) ($this->report['totals']['quantity'] ?? 0),
                round((float) ($this->report['totals']['value'] ?? 0), 2),
                $this->report['application'] ?? '',
                '',
                '',
            ])
            ->all();
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function measurementRows(): array
    {
        $quality = $this->report['quality'] ?? [];

        return [
            [
                'Parciais pagas',
                (int) ($quality['partial_quantity'] ?? 0),
                round((float) ($quality['partial_value'] ?? 0), 2),
                'partials.allow = true e partials.payment = true',
            ],
            [
                'Finais medidos/pagos',
                (int) ($quality['final_quantity'] ?? 0),
                round((float) ($quality['final_value'] ?? 0), 2),
                'work_reports válidos com pagamento concluído por associação nova ou fallback',
            ],
            [
                'Finais com associação nova',
                (int) ($quality['final_with_new_association'] ?? 0),
                0,
                'Encontrados em note_inform_flows ou work_report_flow_productions',
            ],
            [
                'Finais resolvidos por fallback',
                (int) ($quality['final_with_fallback'] ?? 0),
                0,
                'Sem associação nova; resolvidos pelo vínculo histórico de produção/serviço',
            ],
            [
                'Finais sem valor resolvido',
                (int) ($quality['final_without_value'] ?? 0),
                0,
                'Entraram no quantitativo, mas sem valor em ordens/notas',
            ],
        ];
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function criteriaRows(): array
    {
        return [
            ['Analise, Pré-Analise, Fluxo-reverso/Inverso, Desenho, Levantamento, Publicação', 'productions/services deduplicado por nota', 'R$ 0,00', 'Quantitativo operacional. Valor não é inferido por fallback genérico.'],
            ['Fiscalização', 'partials fiscalizadas + work_reports com fiscalização concluída', 'Valor resolvido por parcial/ordem/nota', 'Inclui fiscalizações ainda não medidas.'],
            ['Medição/Pagamento', 'partials pagas + work_reports com pagamento concluído', 'Valor resolvido por parcial/ordem/nota', 'Exclui work_reports cancelados.'],
            ['Analise de Projetos', 'project_review_cycles na última rodada por produção', 'project_review_orders.company_cost + client_cost', 'Usa custo empresa + cliente do módulo de análise de projeto.'],
            ['Viabilidade/Contratação', 'viabilities.hired ou hired_at', 'viabilities.value, depois ordens vinculadas', 'Somente viabilidades contratadas e não canceladas.'],
            ['Cancelados', 'work_reports.canceled = false', 'Não contabiliza final cancelado', 'Parciais não possuem campo canceled; entram apenas se allow=true.'],
            ['Origem', config('app.name', 'SICODE'), 'Não separa ES/SP', 'SP fica em outra aplicação/base e não entra neste relatório.'],
        ];
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function filterRows(): array
    {
        return [
            ['Data inicial', $this->filters['date_from'] ?? ''],
            ['Data final', $this->filters['date_to'] ?? ''],
            ['Empresa ID', $this->filters['company_id'] ?? 'Todas'],
            ['Aplicação origem', $this->report['application'] ?? config('app.name', 'SICODE')],
            ['Gerado em', optional($this->report['generated_at'] ?? now())->format('d/m/Y H:i:s')],
        ];
    }
}
