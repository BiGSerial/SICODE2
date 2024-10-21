<?php

namespace App\Exports\parner;

use App\Models\Edp_depc\City;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class exportExcel implements FromView, WithEvents, WithProperties
{
    use Exportable;

    protected $data;
    protected $cities;

    public function __construct($data)
    {
        $this->data = $data;
        $this->cities = City::orderBy('cidade')->get();
    }

    public function getData()
    {
        return $this->data;
    }

    public function getCities()
    {
        return $this->cities;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE REPORTS',
            'lastModifiedBy' => 'SICODE REPORTS',
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Pedro',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $event->sheet->getStyle('A:B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->autoSize();
            },
        ];
    }

    public function view(): View
    {

        return view('exports.partner.parnerviabexport', [
            'lists' => $this->getData(),
            'cities' => $this->getCities(),
        ]);
    }
}
