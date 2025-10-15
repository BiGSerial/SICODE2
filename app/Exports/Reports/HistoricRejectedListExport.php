<?php

namespace App\Exports\Reports;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class HistoricRejectedListExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    /** @var \Illuminate\Database\Query\Builder */
    protected $builder;

    public function __construct(BaseBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function query()
    {
        // Retorna o builder já com filtros/joins montados no Job
        return $this->builder;
    }

    public function headings(): array
    {
        return ['Abertura', 'Nota', 'Empreiteira', 'Categoria', 'Observação'];
    }

    public function map($row): array
    {
        // $row tem os aliases definidos no select do Job
        return [
            Carbon::parse($row->opened_at)->format('d/m/Y H:i'),
            $row->note_number,
            $row->company_name,
            $row->category,
            $row->observation,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
