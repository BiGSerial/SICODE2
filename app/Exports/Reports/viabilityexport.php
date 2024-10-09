<?php

namespace App\Exports\Reports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class viabilityexport implements FromView, WithEvents, WithProperties
{
    /**
    * @return \Illuminate\Support\Collection
    */
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE REPORTS',
            'lastModifiedBy' => 'SICODE REPORTS',
            'title'          => 'Relatorio Viabilidade Automatico Sicode',
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
                $event->sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->getStyle('C:D')->getNumberFormat()->setFormatCode('0');
                // $event->sheet->getStyle('L:L')->getNumberFormat()->setFormatCode('@');
                $event->sheet->autoSize();
            },
        ];
    }

    public function view(): View
    {

        return view('exports.reports.viabilityexport', [
            'lists' => $this->getData(),
        ]);
    }
}
