<?php

namespace App\Exports;

use App\Custom\Viabilitiesstatus;
use App\Helpers\DaysLeft;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromQuery,
    WithEvents,
    WithHeadings,
    WithProperties,
    WithChunkReading,
    WithMapping
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HiringListExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function query()
    {
        return $this->data;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE REPORTS',
            'lastModifiedBy' => 'SICODE REPORTS',
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function headings(): array
    {
        return [
            'Ordem', 'Note', 'Pep', 'Rubrica',  'Material', 'Municipio', 'Status Ordem', 'Status OV/NOTA', 'Status 0010', 'Centro OP10', 'Prazo Restante',  'Situação'
        ];
    }

    public function map($row): array
    {

        $situation = '';
        $days = '--';

        if ($row->note->Viabilities->count() > 0) {
            $situation = mb_strtoupper(Viabilitiesstatus::status($row->note->Viabilities->last()->status)->status);
            $days = $row->note->Viabilities->last()->getDays();


            if ($row->note->Viabilities->last()->returned_at) {

                $predictedDate = Carbon::parse($row->note->Viabilities->last()->sended_at)->addDays(7 + $days);
                $days = Carbon::parse($row->note->Viabilities->last()->returned_at, false)->diffInDays($predictedDate, false);

            } elseif (!$row->note->Viabilities->last()->returned_at) {

                $predictedDate = Carbon::parse($row->note->Viabilities->last()->sended_at)->addDays(7 + $days);
                $days = $predictedDate->diffInDays(now(), false);

            }

        } else {
            if ($row->note->Waitings->count() > 0) {
                $situation = 'EM ESPERA RI';
            } else {
                $situation = 'SEM CONTRATAÇÃO';
            }
        }



        return [
            $row->ordem,
            $row->note->note,
            $row->pep,
            $row->note->rubrica,
            $row->note->material,
            $row->note->lexp,
            explode(' ', $row->statusSist)[0],
            $row->note->type_note == 1 ? $row->note->centerjob : $row->note->nstats,
            $row->Operations->where('operacao', '0010')?->first()?->status ? explode(' ', $row->Operations->where('operacao', '0010')?->first()?->status)[0] : null,
            $row->Operations->where('operacao', '0010')?->first()?->cenTrab ? explode(' ', $row->Operations->where('operacao', '0010')?->first()?->cenTrab)[0] : null,
            (new DaysLeft($row->note))->getDaysLeft(),
            // $days,
            $situation
        ];
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->getStyle('A1:B' . $highestRow)->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle($highestColumn . '1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('@');

                // AutoSize para todas as colunas
                $columnRange = range('A', $highestColumn);
                foreach ($columnRange as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }


}
