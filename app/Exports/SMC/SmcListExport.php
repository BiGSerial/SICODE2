<?php

namespace App\Exports\SMC;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Custom\Notestatus;

class SmcListExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
    * Retorna uma query para exportação.
    */
    public function query()
    {
        // dd($this->data);
        return $this->data;
    }



    /**
    * Define os cabeçalhos das colunas.
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Note',
            'Ordens',
            'Rubrica',
            'Equipamentos',
            'Equipe',
            'Data da Digitação',
            'Hora da Digitação',
            'Dt Digitação Considerada',
            'Hr Digitação Considerada',
            'Data do Informe Final',
            'Hora do Informe Final',
            'status',
            'Qtd Reijeição',
            'Dt Ult Rejeição',
            'Hr Ult Rejeição',
            'Responsável',
        ];
    }

    /**
    * Define o número de linhas a serem lidas por chunk.
    *
    * @return int
    */
    public function chunkSize(): int
    {
        return 50;
    }


    /**
    * Mapeia cada linha para o formato desejado.
    *
    * @param mixed $row
    * @return array
    */
    public function map($row): array
    {
        $workForm = $row->note->WorkForm ?: $row->note->WorkFormAny;


        $status = 'Aguardando Informe Final'; // default status

        if ($row->rejected) {
            $status = 'Rejeitado';
        } elseif ($workForm && $workForm->exists()) {
            $status = $workForm->canceled ? 'Cancelado' : 'Concluído';
        }


        return [
            $row->note->note,
            implode("\n", $row->Orders->pluck('ordem')->toArray()),
            $row->note->rubrica,
            $row->BtzeroEquipment->count(),
            $workForm?->team,
            $row->created_at->format('Y-m-d'),
            $row->created_at->format('H:i'),
            $row->informed_at?->format('Y-m-d'),
            $row->informed_at?->format('H:i'),
            $workForm?->informed_at?->format('Y-m-d'),
            $workForm?->informed_at?->format('H:i'),
            $status,
            $row->ReturnRamal->count(),
            $row->rejected_at?->format('Y-m-d'),
            $row->rejected_at?->format('H:i'),
            $row->User->name,
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
                $event->sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('M')->getNumberFormat()->setFormatCode('0');

                // Habilita quebra de linha na coluna B e ajusta alinhamento
                $event->sheet->getStyle('B')->getAlignment()->setWrapText(true);

                // Formata as colunas F, H, J para data (d/m/Y)
                $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('HH:mm');
                $event->sheet->getStyle('H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('HH:mm');
                $event->sheet->getStyle('J')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('K')->getNumberFormat()->setFormatCode('HH:mm');
                $event->sheet->getStyle('N')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('O')->getNumberFormat()->setFormatCode('HH:mm');

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
