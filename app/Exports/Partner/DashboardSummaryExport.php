<?php

namespace App\Exports\Partner;

use App\Exports\Partner\Sheets\DashboardSummarySheet;
use App\Exports\ProjectReview\Sheets\StyledArraySheetExport;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

class DashboardSummaryExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param array{headline:string, context:array<string,string>, sections:array, footnote:string} $summary
     * @param array<int, array{title:string, headings:array<int,string>, rows:array<int,array<int,mixed>>}> $tables
     */
    public function __construct(
        private readonly array $summary,
        private readonly array $tables
    ) {
    }

    public function sheets(): array
    {
        $sheets = [
            new DashboardSummarySheet(
                $this->summary['headline'],
                $this->summary['context'],
                $this->summary['sections'],
                $this->summary['footnote']
            ),
        ];

        foreach ($this->tables as $table) {
            $sheets[] = new StyledArraySheetExport($table['title'], $table['headings'], $table['rows']);
        }

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => 'Dashboard da Parceira - Resumo',
            'description'    => 'Resumo executivo do dashboard da parceira',
            'company'        => config('app.name', 'SICODE'),
        ];
    }
}
