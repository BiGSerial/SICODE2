<?php

namespace App\Exports\Engineers;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;

class NotRealized implements FromView, WithProperties, WithEvents
{
    use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function properties(): array
    {
        return [
            'creator'        =>  Auth()->user()->name,
            'lastModifiedBy' =>  Auth()->user()->name,
            'title'          => 'Engineers Not Realized',
            'description'    => 'SICODE - Relatório Automático',
            'subject'        => 'Engineers Not Realized',
            'keywords'       => 'Engineers, Not Realized',
            'category'       => 'Engineers',
            'manager'        => 'João Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set background color to blue and font color to white for the first row from A to N
                $sheet->getStyle('A1:N1')->applyFromArray([
                    'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF0000FF'],
                    ],
                    'font' => [
                    'color' => ['argb' => 'FFFFFFFF'],
                    ],
                ]);

                // Set columns A and B to numbers without decimal places
                $sheet->getStyle('A')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');

                // Set columns L and M to Brazilian currency format
                $sheet->getStyle('L')->getNumberFormat()->setFormatCode('R$ #.##0,00');
                $sheet->getStyle('M')->getNumberFormat()->setFormatCode('R$ #.##0,00');

                // Center align all cells horizontally and vertically
                $sheet->getStyle('A1:Z1000')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:Z1000')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Set columns A to Z to auto size
                foreach (range('A', 'Z') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }

    public function view(): View
    {
        return view('exports.engineers.not_realized', [
            'data' => $this->data,
        ]);
    }
}
