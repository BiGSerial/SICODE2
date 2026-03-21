<?php

namespace App\Exports\ProjectReview;

use App\Exports\ProjectReview\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class GovernanceWorkbookExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param array<int, array{title:string, headings:array<int,string>, rows:array<int,array<int,mixed>>}> $sheetsData
     */
    public function __construct(
        private readonly array $sheetsData
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->sheetsData as $sheet) {
            $sheets[] = new ArraySheetExport(
                $sheet['title'],
                $sheet['headings'],
                $sheet['rows']
            );
        }

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Relatório - Análise de Projeto',
            'description' => 'Exportação de governança da Análise de Projeto',
            'subject' => 'Análise de Projeto',
            'company' => config('app.name', 'SICODE'),
        ];
    }
}

