<?php

namespace App\Exports\Services;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class analisesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    protected $query;
    protected $service_id;

    public function __construct($query, $service_id)
    {
        $this->query = $query;
        $this->service_id = $service_id;
    }

    /**
    * @return \Illuminate\Database\Query\Builder
    */
    public function query()
    {
        return $this->query;
    }

    /**
    * @return array
    */
    public function headings(): array
    {
        return [
            'Nota',
            'Rubrica',
            'Municipio',
            'Material',
            'Grupo 1',
            'Grupo 2',
            'status',
            'Data',
            'Dias Restantes',
            'Pze',
            'Centro Trabalho',
            'Atribuição',
        ];
    }

    /**
    * @param $row
    * @return array
    */
    public function map($row): array
    {
        return [
            $row->note,
            $row->rubrica,
            $row->lexp,
            $row->material,
            $row->group1,
            $row->group2,
            $row->nstats,
            $row->dt_created,
            $row->days_left,
            $row->pze,
            $row->centerjob,
            isset($row->Productions->where('service_id', $this->service_id)->last()->Company->name) ? $row->Productions->where('service_id', $this->service_id)->last()->Company->name : '',
        ];
    }

    /**
    * @param Worksheet $sheet
    * @return array
    */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0000FF'],
            ],
        ]);

        $sheet->getStyle('A:Z')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }

    /**
    * @return int
    */
    public function chunkSize(): int
    {
        return 1000;
    }
}
