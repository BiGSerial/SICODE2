<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{Exportable, FromView, WithEvents, WithProperties};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HiringAccompanyExport implements FromView, WithEvents, WithProperties
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data->get()->sortBy('Order.Note.note');
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE REPORTS',
            'lastModifiedBy' => 'SICODE REPORTS',
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:K1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->getStyle('A:B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->autoSize();
            },
        ];
    }

    public function view(): View
    {
        return view('exports.hiring.hiringaccompanyexport', [
            'lists' => $this->getData(),
        ]);
    }
}
