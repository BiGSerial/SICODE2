<?php

namespace App\Exports\Closure;

use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/** Excel de uma lista do Encerramento: Ordens, Resumo e Controle da exportação (mesmo padrão do relatório Pós Obra). */
class ClosureListExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param array<int, string> $headings
     * @param array<int, array<int, mixed>> $rows
     * @param array<int, array<int, mixed>> $summaryRows
     * @param array<int, array<int, mixed>> $auditRows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $summaryRows,
        private readonly array $auditRows,
        private readonly string $mainSheet = 'Ordens',
    ) {
    }

    public function sheets(): array
    {
        return [
            new ClosureStrictSheet($this->mainSheet, $this->headings, $this->rows),
            new ClosureStrictSheet('Resumo', ['Agrupamento', 'Item', 'Quantidade', 'Percentual (%)'], $this->summaryRows),
            new ClosureStrictSheet('Controle Exportacao', ['Campo', 'Valor'], $this->auditRows),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => $this->title,
            'description'    => 'Lista do Encerramento exportada com os filtros aplicados',
            'subject'        => 'Encerramento',
        ];
    }
}
