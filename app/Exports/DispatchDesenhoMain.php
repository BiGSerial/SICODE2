<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{Exportable, FromView, WithEvents, WithProperties};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DispatchDesenhoMain implements FromView, WithEvents, WithProperties
{
    use Exportable;

    public $data;
    public $service;

    public function __construct($data, $service)
    {
        $this->data = $data;
        $this->service = $service;
    }

    public function properties(): array
    {
        return [
            'creator'        => Auth()->User()->name,
            'lastModifiedBy' => Auth()->User()->name,
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
                $event->sheet->getStyle('A1:U1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->autoSize();
            },
        ];
    }

    public function view(): View
    {
        return view('exports.dispatch.dispatchDesenhoMain', [
            'lists' => $this->data,
            'service' => $this->service,
        ]);
    }
}
