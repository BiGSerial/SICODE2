<?php

namespace App\Exports\Workreports;

use App\Models\WorkReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HistListExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    use RegistersEventListeners;

    protected $data;


    public function __construct(array $data)
    {
        // dd($data->limit(5)->get());
        $this->data = $data;

    }

    public function query()
    {

        return WorkReport::whereIn('id', $this->data)->with(['Note.Files', 'Orders', 'Equipment', 'company']);
    }



    public function map($row): array
    {


        if ($row->Note->Adsform) {
            $adsForm = $row->Note->Adsform;
            $ads = ($adsForm->tacit ? $adsForm->tacit_delivered_at : $adsForm->created_at)?->format('d/m/Y');
        } elseif ($row->Note->OldAds->isNotEmpty()) {
            $ads = $row->Note->OldAds->last()->date->format('d/m/Y');
        } else {
            $ads = null;
        }

        return [
            $row->Note->note,
            implode("\n ", $row->Orders->pluck('ordem')->toArray()),
            $row->Note->rubrica,

            $row->Equipment ? $row->Equipment->count() : '',

            $row->changes ? 'SIM' : 'NÃO',

            $row->team,
            $row->date ? $row->date->format('d/m/Y') : '',
            $row->informed_at ? $row->informed_at->format('d/m/Y') : $row->created_at->format('d/m/Y'),
            $ads ? $ads : '',
            $row->responsible,
            $row->observation,
            $row->Company ? $row->Company->name : '',


        ];


    }

    public function headings(): array
    {
        return [
            'Note',
            'Ordens',
            'Rubrica',
            'Equipamentos',
            'Alterações',
            'Equipe WPA',
            'Data da Execução',
            'Data da Entrega',
            'Data entrega ADS',
            'Responsável',
            'Observações',
            'Empreiteira',

        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
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
                // Define o estilo para a primeira linha (cabeçalho)
                $event->sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);

                // Obter o delegate do PhpSpreadsheet para aplicar formatações adicionais
                $sheet = $event->sheet->getDelegate();

                // Permitir quebra de linha na coluna J e nas demais colunas
                $sheet->getStyle('B:M')->getAlignment()->setWrapText(true);

                // Define uma largura fixa para a coluna J para que o texto longo quebre a linha ao invés de expandir
                $sheet->getColumnDimension('F')->setWidth(30);
                $sheet->getColumnDimension('J')->setWidth(30);
                $sheet->getColumnDimension('K')->setWidth(30);

                // Centralizar verticalmente e horizontalmente todas as colunas
                $sheet->getStyle('A:M')->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Formatar as colunas A e B para numeração sem casas decimais
                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('G:G')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('H:H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                $event->sheet->autoSize();
            },
        ];
    }

    public function chunkSize(): int
    {

        return 1000;
    }
}
