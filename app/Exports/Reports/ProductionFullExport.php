<?php

namespace App\Exports\Reports;

use App\Models\City;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionFullExport implements FromView, WithEvents, WithProperties
{
    use Exportable;

    public $cities;
    public $exports;


    public function __construct($data)
    {
        try {
            $this->cities = City::orderBy('cidade')->get();
        } catch (\Throwable $th) {
            $this->cities = false;
        }

        $this->exports = $data;
    }

    public function view(): View
    {
        return view('exports.reports.FullExportProductions', [
            'exports' => $this->exports,
            'cities'  => $this->cities,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:AG1')->applyFromArray([
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
}
