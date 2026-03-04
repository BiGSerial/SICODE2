<?php

namespace App\Exports\Dispatchs;

use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromQuery,
    WithChunkReading,
    WithEvents,
    WithHeadings,
    WithMapping,
    WithProperties
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Custom\Notestatus;

class SupervisionExportStack implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $exports;
    protected $service;
    protected $serviceUuid;

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
        return 1000; // Experimente valores maiores
    }

    public function headings(): array
    {
        return [
            'Tipo','Nota/OV', 'DD', 'Sts WPA', 'MMGD', 'Rubrica', 'Municipio', 'Descrição', 'Despachado Por',
            'Emp Despachante', 'Atribuído Por', 'Emp Atribuidor', 'Fiscal', 'Emp Fiscal', 'Dt Despacho',
            'Dt Atribuição', 'Dt Fiscalização', 'Dt Informe', 'Dt ADS', 'Empresa Informe', 'Informado Por', 'Status Informe', 'Motivo Rejeição Informe', 'Observação Informe', 'Status Produção'
        ];
    }

    public function map($row): array
    {
        $adsInfomed = '';
        $workForm = $row->Note->WorkForm ?: $row->Note->WorkFormAny;

        if ($row->partial) {
            $informed_at = $row->Note->Partials?->where('supervision_id', $row->user_id)->last()?->supervision_at ?? '';
        } else {
            $informed_at = $workForm?->informed_at?->format('d/m/Y H:i') ?? '';
            $adsForm = $workForm?->Adsform;
            $adsInfomed = $adsForm
                ? ($adsForm->tacit ? $adsForm->tacit_delivered_at : $adsForm->created_at)?->format('d/m/Y H:i')
                : '';
        }



        return [
            $row->partial ? 'Parcial' : 'Final',
            $row->Note->note,
            $row->Note->Wpas?->last()?->dd ?? '',
            $row->Note->Wpas?->last()?->execstats ?? '',
            $row->Note->mmgd ? 'Sim' : 'Não',
            $row->Note->rubrica,
            $row->Note->lexp,
            $row->Note->material,
            $row->Dispatcher->name ?? '',
            $row->Dispatcher->Company->name ?? '',
            $row->Att->name ?? '',
            $row->Att->Company->name ?? '',
            $row->User->name ?? '',
            $row->User->Company->name ?? '',
            $row->dispatch_at ? $row->dispatch_at->format('d/m/Y') : '',
            $row->att_at ? $row->att_at->format('d/m/Y') : '',
            $row->completed_at ? $row->completed_at->format('d/m/Y') : '',
            $informed_at,
            $adsInfomed,
            $workForm?->Company?->name,
            $workForm?->informer,
            !$workForm ? 'NORMAL' : ($workForm->canceled ? 'CANCELADO' : ($workForm->rejected ? 'REJEITADO' : 'NORMAL')),
            $workForm?->rejected ? $workForm?->Returnwork?->last()?->category : '',
            $workForm?->rejected ? $workForm?->Returnwork?->last()?->text_obs : '',

            Notestatus::status($row->status)->status,
        ];
    }


    public function properties(): array
    {
        return [
            'creator'        => 'Sicode',
            'lastModifiedBy' => 'Sicode',
            'title'          => 'Supervision List',
            'description'    => 'List of all supervision notes',
            'subject'        => 'Supervision List',
            'keywords'       => 'supervision, list, sicode',
            'category'       => 'Supervision',
            'manager'        => 'Sicode',
            'company'        => 'Sicode',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Centraliza verticalmente e horizontalmente apenas as células com dados
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()->setWrapText(true);

                // Aplica estilo ao cabeçalho
                $event->sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);

                // Aplica formatação numérica apenas nas linhas com dados
                if ($highestRow > 1) {
                    $event->sheet->getStyle('B2:B' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $event->sheet->getStyle('C2:C' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $event->sheet->getStyle('D2:D' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $event->sheet->getStyle('O2:S' . $highestRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                }

                $event->sheet->autoSize();
            },
        ];
    }
}
