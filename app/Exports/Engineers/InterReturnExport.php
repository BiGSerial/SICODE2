<?php

namespace App\Exports\Engineers;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;

class InterReturnExport implements FromView, WithProperties, WithEvents
{
    use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function view(): View
    {
        return view('exports.engineers.inter_return_export', [
            'myLists' => $this->data
        ]);
    }

    public function properties(): array
    {
        return [
            'creator'        =>  Auth()->user()->name,
            'lastModifiedBy' =>  Auth()->user()->name,
            'title'          => 'Engineers Inter Return',
            'description'    => 'SICODE - Relatório Automático',
            'subject'        => 'Engineers Inter Return',
            'keywords'       => 'Engineers, Inter Return',
            'category'       => 'Engineers',
            'manager'        => 'João Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
            $event->sheet->getDelegate()->getStyle('A1:Z1')->getFont()->setBold(true);
            foreach (range('A', 'Z') as $column) {
                $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);
            }
            // Set columns A and C to numbers without decimal places
            $event->sheet->getDelegate()->getStyle('A')->getNumberFormat()->setFormatCode('0');
            $event->sheet->getDelegate()->getStyle('C')->getNumberFormat()->setFormatCode('0');
            }
        ];
    }
}
