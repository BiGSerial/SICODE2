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

class PublicationExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $exports;
    protected $service;


    public function __construct($data, $service)
    {
        $this->exports = $data;
        $this->service = $service;
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
            'Nota', 'Rubrica', 'Municipio', 'Empreiteira', 'DataExecução', 'DataInformada', 'Status', 'CenterJob', 'DataVencimento', 'Empresa', 'Usuario'
        ];
    }


    public function map($row): array
    {
        return [
            $row->note,
            $row->rubrica,
            $row->lexp,
            isset($row->WorkForm->Company->name) ? $row->WorkForm->Company->name : '',
            isset($row->WorkForm->date) ? Carbon::parse($row->WorkForm->date)->format('d/m/Y') : '',
            isset($row->WorkForm->informed_at) ? Carbon::parse($row->WorkForm->informed_at)->format('d/m/Y H:i:s') : '',
            $row->nstats,
            $row->centerjob,
            isset($row->prazo_final) ? Carbon::parse($row->prazo_final)->format('d/m/Y') : '',
            isset($row->Productions->where('service_id', $this->service->uuid)->last()->Company->name) ? $row->Productions->where('service_id', $this->service->uuid)->last()->Company->name : '',
            isset($row->Productions->where('service_id', $this->service->uuid)->last()->User->name) ? $row->Productions->where('service_id', $this->service->uuid)->last()->User->name : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

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

                // Formata a coluna A para número sem casas decimais
                $event->sheet->getStyle('A')->getNumberFormat()->setFormatCode('0');

                // Formata as colunas F, H, J para data (d/m/Y)
                $event->sheet->getStyle('E')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy HH:mm:ss');
                $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                // Formata as colunas G, I, K para hora (H:i:s)
                // $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('HH:mm:ss');
                // $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('HH:mm:ss');
                // $event->sheet->getStyle('K')->getNumberFormat()->setFormatCode('HH:mm:ss');

                // Define o tamanho automático para as colunas
                $event->sheet->autoSize();
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
