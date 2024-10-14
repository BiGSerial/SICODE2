<?php

namespace App\Exports;

use App\Models\Edp_depc\City;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{Exportable, FromView, WithEvents, WithProperties};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionExport implements FromView, WithEvents, WithProperties
{
    use Exportable;

    public $company_id;

    public $exports;

    public $cities;

    public function __construct()
    {
        try {
            $this->cities = City::orderBy('cidade')->get();
        } catch (\Throwable $th) {
            $this->cities = false;
        }
    }

    public function company($company_id)
    {
        $this->company_id = $company_id;

        return $this;
    }

    public function reports($report)
    {
        $this->exports = $report;

        return $this;
    }

    public function view(): View
    {

        return view('exports.productions', [
            'exports' => $this->exports,
            'cities'  => $this->cities,
        ]);
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
}
