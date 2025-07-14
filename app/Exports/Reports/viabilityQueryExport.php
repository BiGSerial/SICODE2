<?php

namespace App\Exports\Reports;

use App\Custom\Viabilitiesstatus;
use App\Models\Viability;
use App\Models\YourModel; // Substitua pelo seu modelo
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class viabilityQueryExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    public $exports;

    public function __construct($data)
    {
        $this->exports = $data;
    }

    public function query()
    {
        return $this->exports; // Substitua pelo seu modelo e ajuste a consulta conforme necessário
    }

    public function headings(): array
    {
        return [
            'CONTRATANTE',
            'EMPRESA',
            'ORDEM',
            'NOTA/OV',
            'RESPONSÁVEL',
            'CONTRATADO',
            'TÁCITO',
            'ENVIADO EM',
            'CONTRATADO EM',
            'VENCIDO EM',
            'EMPREITERA',
            'VIABILIZADO EM',
            'COMPLETADO EM',
            'STATUS'
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

                // Formata a coluna A para número sem casas decimais
                $event->sheet->getStyle('C')->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('D')->getNumberFormat()->setFormatCode('0');

                // Formata as colunas F, H, J para data (d/m/Y)
                $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('L')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('M')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                // Define o tamanho automático para as colunas
                $event->sheet->autoSize();

                // Define o alinhamento horizontal e vertical para todas as células
                $event->sheet->getStyle('A1:N' . $event->sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle('A1:N' . $event->sheet->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            },

        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($row): array
    {
        $orders = "";
        if ($row->Orders) {
            foreach ($row->Orders as $order) {
                $orders .= $order->ordem . "\n";

            }
        }
        $orders = rtrim($orders, "\n"); // Remove the last newline character

        return [
            $row->User->name,
            $row->User->Company->name ?? '---',
            $orders ?? '---',
            $row->Note->note ?? '---',
            $row->Engineer->name ?? '---',
            $row->hired ? 'SIM' : 'NÃO',
            $row->tacit ? 'SIM' : 'NÃO',
            $row->sended_at ? Carbon::parse($row->sended_at)->format('d/m/Y') : '---',
            $row->hired_at ? Carbon::parse($row->hired_at)->format('d/m/Y') : '---',
            $row->tacit_at ? Carbon::parse($row->tacit_at)->format('d/m/Y') : '---',
            $row->Company->name ?? '---',
            $row->returned_at ? Carbon::parse($row->returned_at)->format('d/m/Y') : '---',
            $row->completed_at ? Carbon::parse($row->completed_at)->format('d/m/Y') : '---',
            Viabilitiesstatus::status($row->status)->status,
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => "SICODE",
            'lastModifiedBy' => "SICODE",
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }
}
