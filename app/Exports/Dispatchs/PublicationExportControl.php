<?php

namespace App\Exports\Dispatchs;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use App\Custom\Notestatus;
use App\Helpers\DaysLeft;

class PublicationExportControl implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $exports;
    // protected $service;


    public function __construct($data)
    {
        $this->exports = $data;

    }

    public function query()
    {

        return $this->exports;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Nota', 'Ordem', 'Rubrica', 'Municipio', 'Material', 'Data Despacho', 'Data Atribuição', 'Data Vencimento', 'SMC', 'Data SMC', 'Informe Final', 'Data Informe', 'Status Informe', 'Status', 'Empresa', 'Usuario'
        ];
    }


    public function map($row): array
    {
        $workForm = $row->Note->WorkForm ?: $row->Note->WorkFormAny;
        $orders = $workForm?->Orders?->pluck('ordem')->toArray();

        if ($orders) {
            $orders = implode(chr(10), $orders);
        } else {
            $orders = '';
        }

        return [
            $row->Note->note,
            $orders,
            $row->Note->rubrica,
            $row->Note->lexp,
            $row->Note->material,
            $row->dispatch_at ? Carbon::parse($row->dispatch_at)->format('d/m/Y H:i:s') : '',
            $row->att_at ? Carbon::parse($row->att_at)->format('d/m/Y H:i:s') : '',
            (new DaysLeft($row->Note))->getLastDate(),
            $row->Note->RamalForm ? 'SIM' : 'NÃO',
            $row->Note->RamalForm?->created_at->format('d/m/Y H:i:s'),
            $workForm ? 'SIM'.($workForm->canceled ? ' (CANCELADO)' : '') : 'NÃO',
            $workForm?->informed_at?->format('d/m/Y H:i:s'),
            !$workForm ? 'NORMAL' : ($workForm->canceled ? 'CANCELADO' : ($workForm->rejected ? 'REJEITADO' : 'NORMAL')),
            (Notestatus::status($row->status))->status,
            isset($row->Company->name) ? $row->Company->name : '',
            isset($row->User->name) ? $row->User->name : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                // Formata a coluna A para número sem casas decimais
                $event->sheet->getStyle('A')->getNumberFormat()->setFormatCode('0');
                // $event->sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                // Formata as colunas F, H, J para data (d/m/Y)
                $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('J')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('L')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                // $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy HH:mm:ss');
                // $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                // Formata as colunas G, I, K para hora (H:i:s)
                // $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('HH:mm:ss');
                // $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('HH:mm:ss');
                // $event->sheet->getStyle('K')->getNumberFormat()->setFormatCode('HH:mm:ss');

                // Define o tamanho automático para as colunas
                $event->sheet->autoSize();

                // Alinha todas as células ao centro horizontalmente e verticalmente
                $event->sheet->getStyle('A1:K' . $event->sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle('A1:K' . $event->sheet->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            },
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => Auth()->user()->name,
            'lastModifiedBy' => Auth()->user()->name,
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }
}
