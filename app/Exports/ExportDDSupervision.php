<?php

namespace App\Exports;

use App\Models\Note;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{Exportable, FromView, WithEvents, WithProperties};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExportDDSupervision implements FromView, WithEvents, WithProperties
{
    use Exportable;

    public $exports;

    public $service;

    public function exportDD($notes, $service)
    {
        $this->exports = $notes;
        $this->service = $service;

        return $this;
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
                $event->sheet->getStyle('A1:V1')->applyFromArray([
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

                $event->sheet->getStyle('A:B')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

                $event->sheet->getStyle('A1:Q' . $event->sheet->getHighestRow())->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }

    public function view(): View
    {
        return view('exports.exportddsupervision', [
            'exports' => $this->exports,
            'service' => $this->service,
        ]);
    }
}
