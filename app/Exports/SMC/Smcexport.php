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

class Smcexport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
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
            'Parceira',
            'Qtd SMC',
            'Qtd Informado',
            'Digitado por',
            'Digitado Em',
            'Informado Por',
            'Informado Em',
            'Publicado Por',
            'Parcial Em',
            'Publicado Em',
            'Inf SMC',
            'Inf Parceiro',
            'Status Publicação',
            'Status SMC',
            'Status Parceiro',
            'Dt SMC Rejeitado',
            'Dt Parc Rejeitado',
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
        $workForm = $row->WorkForm ?: $row->WorkFormAny;

        return [
            $row->note,
            $row->RamalForm ? $row->RamalForm ->Company->name : '',
            $row->RamalForm &&  $row->RamalForm->BtzeroEquipment ? $row->RamalForm->BtzeroEquipment->count() : '',
            $workForm &&  $workForm->Equipment ? $workForm->Equipment->count() : '',
            $row->RamalForm ? $row->RamalForm->User->name : '',
            $row->RamalForm ? Carbon::parse($row->RamalForm->created_at)->format('d/m/Y') : '',
            $workForm ? $workForm->informer : '',
            $workForm ? Carbon::parse($workForm->informed_at)->format('d/m/Y') : '',
            $row->productions && isset($row->productions->last()->User) ? $row->productions->last()->User->name : '',
            $row->productions && isset($row->productions->last()->partial_at) ? $row->productions->last()->partial_at->format('d/m/Y') : 'Sem Dados',
            $row->productions && isset($row->productions->last()->completed_at) ? $row->productions->last()->completed_at->format('d/m/Y') : 'Não Publicado',
            $row->RamalForm ? $row->RamalForm->observation : '',
            $workForm ? $workForm->observation : '',
            $row->productions && isset($row->productions->last()->status) ? mb_strToUpper(Notestatus::status($row->productions->last()->status)->status) : 'Não Publicado',
            !$row->RamalForm ? 'NÃO INFORMADO' : ($row->RamalForm->rejected ? 'REJEITADO' : 'NORMAL'),
            !$workForm ? 'NÃO INFORMADO' : ($workForm->canceled ? 'CANCELADO' : ($workForm->rejected ? 'REJEITADO' : 'NORMAL')),
            $row->RamalForm && $row->RamalForm->rejected && $row->RamalForm->ReturnRamal ? Carbon::parse($row->RamalForm->ReturnRamal->last()->created_at)->format('d/m/Y') : '',
            $workForm && $workForm->rejected && $workForm->Returnwork ? Carbon::parse($workForm->Returnwork->last()->created_at)->format('d/m/Y') : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->getStyle('A1:Q1')->applyFromArray([
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
                $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                // $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

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
