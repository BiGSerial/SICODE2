<?php

namespace App\Exports\oexterno;

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

class ProtocolsList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $export;

    public function __construct($export)
    {
        $this->export = $export;
    }

    public function query()
    {
        return $this->export;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Nota/OV',
            'Protocolo',
            'Ultimo Protocolo',
            'Data Primeiro Protocolo',
            'Hora Primeiro Protocolo',
            'Status Ultimo Protocolo',
            'Entidade',
            'rubrica',
            'Municipio',
            'Pedido',
            'Status',
            'Data Ultima Movimentação',
            'Hora Ultima Movimentação',
            'Data Status',
            'Hora Status',
            'Dias Status',
            'Data Criação',
            'Hora Criação',
            'Dias Criação',
            'Situação',
        ];
    }

    public function map($row): array
    {

        return [
            $row->note,
            $row->externals?->where('completed', true)->count().'/' . $row->externals?->count(),
            $row->externals?->last()?->protocols?->last()?->protocol,
            $row->externals?->first()?->protocols?->first()?->created_at?->format('d/m/Y'),
            $row->externals?->first()?->protocols?->first()?->created_at?->format('H:i'),
            $row->externals?->last()?->comments?->last()?->title,
            $row->externals?->last()?->entidade,
            $row->rubrica,
            $row->lexp,
            $row->numPedido,
            $row->nstats,
            $row->externals?->first()?->comments?->first()?->created_at?->format('d/m/Y'),
            $row->externals?->first()?->comments?->first()?->created_at?->format('H:i'),
            $row->dt_status?->format('d/m/Y'),
            $row->dt_status?->format('H:i'),
            $row->dt_status?->startOfDay()->diffInDays(Carbon::now()->startOfDay()),
            $row->dt_created?->format('d/m/Y'),
            $row->dt_created?->format('H:i'),
            $row->dt_created?->startOfDay()->diffInDays(Carbon::now()->startOfDay()),
            $row->externals->isEmpty() ? 'SEM REGISTRO' : (!$row->externals?->where('completed', false)->count() ? 'COMPLETO' : 'EM ANDAMENTO'),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Sicode',
            'lastModifiedBy' => 'Sicode',
            'title'          => 'Survey List',
            'description'    => 'List of all Survey notes',
            'subject'        => 'Survey List',
            'keywords'       => 'Survey, list, sicode',
            'category'       => 'Survey',
            'manager'        => 'Sicode',
            'company'        => 'Sicode',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = chr(64 + count($this->headings())); // Convert column count to letter (A=65, B=66, etc)
                $event->sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
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


                $event->sheet->autoSize();
            },
        ];
    }
}
