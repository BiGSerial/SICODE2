<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Writer;

class ProductionExport implements FromView, WithProperties, WithEvents
{
    use Exportable;

    public $company_id;
    public $exports;

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
            'exports' => $this->exports
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
                $event->sheet->getStyle('A1:Z1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->autoSize();
            },
        ];
    }
}
