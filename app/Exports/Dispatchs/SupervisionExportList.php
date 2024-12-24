<?php

namespace App\Exports\Dispatchs;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SupervisionExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $exports;
    protected $service;

    public function __construct($data, $service)
    {
        $this->exports = $data;
        $this->service = $service;
    }

    public function query()
    {
        return $this->exports;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Note', 'Ordem', 'DD', 'Postes', 'Informado Em', 'NumPedido', 'Rubrica', 'Municipio', 'Gp2', 'Gp5', 'Status', 'Dias Informe', 'Empresa', 'Usuario'
        ];
    }

    public function map($row): array
    {
        return [
            $row->note,
            isset($row->Orders) ? implode(" \n", $row->Orders->filter(function ($order) {
                return !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT');
            })->pluck('ordem')->toArray()) : '',
            $row->wpas->isNotEmpty() ? $row->wpas->last()->dd : '---',
            $row->postes ? $row->postes : '---',
            $row->work_dt_created ? Carbon::parse($row->WorkForm->informed_at)->format('d/m/Y') : '---',
            $row->numPedido,
            $row->rubrica,
            $row->lexp,
            $row->group2,
            $row->group5,
            $row->type_note == 2 ? $row->nstats : $row->centerjob,
            $row->work_dt_created ? Carbon::parse($row->WorkForm->informed_at)->diffInDays(Carbon::now(), false) : 0,
            $row->productions->isNotEmpty() && $row->productions->where('service_id', $this->service)->isNotEmpty() && $row->productions->where('service_id', $this->service)->last()->Company ? $row->productions->where('service_id', $this->service)->last()->Company->name : '---',
            $row->productions->isNotEmpty() && $row->productions->where('service_id', $this->service)->isNotEmpty() && $row->productions->where('service_id', $this->service)->last()->User ? $row->productions->where('service_id', $this->service)->last()->User->name : '---',
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Sicode',
            'lastModifiedBy' => 'Sicode',
            'title'          => 'Supervision List',
            'description'    => 'List of all supervision notes',
            'subject'        => 'Supervision List',
            'keywords'       => 'supervision, list, sicode',
            'category'       => 'Supervision',
            'manager'        => 'Sicode',
            'company'        => 'Sicode',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);
                $event->sheet->getStyle('A')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('E')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->autoSize();
            },
        ];
    }


}
