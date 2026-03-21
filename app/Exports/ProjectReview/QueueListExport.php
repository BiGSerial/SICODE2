<?php

namespace App\Exports\ProjectReview;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;

class QueueListExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithProperties
{
    /**
     * @param array<int, array<int, mixed>> $rows
     */
    public function __construct(
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Desenhista',
            'Empresa',
            'Ordens',
            'Custo total',
            'Custo empresa',
            'Custo cliente',
            'Status',
            'Quando foi enviado',
            'Analista',
            'Laudo técnico',
        ];
    }

    public function title(): string
    {
        return 'Lista Analise';
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Exportação - Lista para Analisar',
            'description' => 'Lista da fila da Análise de Projeto',
            'subject' => 'Análise de Projeto',
            'company' => config('app.name', 'SICODE'),
        ];
    }
}
