<?php

namespace App\Exports\ProjectReview;

use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class HistoryListExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param array<int, array<int, mixed>> $summaryRows
     * @param array<int, array<int, mixed>> $detailedRows
     * @param array<int, array<int, mixed>> $commentsRows
     * @param array<int, array<int, mixed>> $auditRows
     */
    public function __construct(
        private readonly array $summaryRows,
        private readonly array $detailedRows,
        private readonly array $commentsRows,
        private readonly array $auditRows
    ) {
    }

    public function sheets(): array
    {
        return [
            new StyledArraySheetExport('Historico Analise', [
                'Nota',
                'Desenhista',
                'Empresa',
                'Ordens',
                'Custo total',
                'Custo empresa',
                'Custo cliente',
                'Variação (%)',
                'Status',
                'Analista',
                'Quando foi enviado',
            ], $this->summaryRows),
            new StyledArraySheetExport('Ordens Revisoes', [
                'Nota',
                'Desenhista',
                'Empresa',
                'Número da ordem',
                'Custo total',
                'Custo empresa',
                'Custo cliente',
                'Inicial/Revisado',
                'Var. custo total (%)',
                'Var. custo empresa (%)',
                'Var. custo cliente (%)',
                'Data envio ciclo',
                'Analista',
            ], $this->detailedRows),
            new StyledArraySheetExport('Comentarios Ciclos', [
                'Nota',
                'Empresa',
                'Desenhista',
                'Rodada',
                'Decisão rodada',
                'Autor comentário',
                'Perfil autor',
                'Comentário',
                'Data comentário',
            ], $this->commentsRows),
            new StyledArraySheetExport('Controle Exportacao', [
                'Campo',
                'Valor',
            ], $this->auditRows),
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Exportação - Histórico de Análise',
            'description' => 'Histórico da Análise de Projeto',
            'subject' => 'Análise de Projeto',
            'company' => config('app.name', 'SICODE'),
        ];
    }
}
