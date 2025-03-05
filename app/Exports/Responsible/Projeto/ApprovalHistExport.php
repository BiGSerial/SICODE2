<?php

namespace App\Exports\Responsible\Projeto;

use App\Models\Note;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Custom\Notestatus;
use Carbon\Carbon;

class ApprovalHistExport implements FromQuery, WithEvents, WithChunkReading, WithMapping, WithProperties, WithHeadings
{
    use Exportable;

    public $selected;
    public $date_init;
    public $date_end;
    public $month;


    public function __construct($selected = null, $date_init = null, $date_end = null, $month = null)
    {
        $this->selected = $selected;


        $this->date_init = $date_init ? Carbon::createFromFormat('Y-m-d', $date_init) : null;
        $this->date_end = $date_end ? Carbon::createFromFormat('Y-m-d', $date_end) : null;
        $this->month = $month ? Carbon::createFromFormat('Y-m', $month) : null;

        if (!$date_init && !$date_end && !$month) {
            $this->month = Carbon::createFromFormat('Y-m');
        }

    }

    public function query()
    {
        return Note::when($this->selected, function ($query) {
            return $query->whereIn('id', $this->selected);
        })->whereHas('Approval', function ($q) {
            $q->where('approved', true)
             ->where('user_id', auth()->id());

            if ($this->month && !$this->date_init && !$this->date_end) {
                $fistMonth = $this->month->startOfMonth();
                $lastMonth = $this->month->endOfMonth();

                $q->whereBetween('approved_at', [$fistMonth->startOfDay(), $lastMonth->endOfDay()]);

            } elseif ($this->date_init && $this->date_end) {
                $q->whereBetween('approved_at', [$this->date_init->startOfDay(), $this->date_end->endOfDay()]);
            } elseif ($this->date_init && !$this->date_end) {
                $q->where('approved_at', '>=', $this->date_init->startOfDay());
            } elseif (!$this->date_init && $this->date_end) {
                $q->where('approved_at', '<=', $this->date_end->endOfDay());
            }

        })
        ->with([
            'orders' => function ($q) {
                $q->where('statusSist', 'not like', 'ENT%')
                    ->where('statusSist', 'not like', 'ENC%')
                    ->orderBy('ordem');
            },
            'orders.operations' => function ($q) {
                $q->where('operacao', '0010');
            },
            'approval.reclaims',
        ]);
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Ordem',
            'Rubrica',
            'Municipio',
            'Empreiteira',
            'StsNota',
            'TempoStatus (dias)',
            'Em Atvidade (dias)',
            'Em Resolução (dias)',
            'Status Resolução',
        ];
    }

    public function map($row): array
    {

        $reclaim = $row->approval->reclaims->isNotEmpty()
        ? $row->approval->reclaims->last()
        : null;

        return [
            $row->note,
            $row->orders->isNotEmpty() ? implode("\n", $row->orders->pluck('ordem')->toArray()) : '',
            $row->rubrica,
            $row->lexp,
            $row->orders->isNotEmpty() &&  $row->orders->first()->operations->isNotEmpty() ? $row->orders->last()->operations->first()->cenTrab : '',
            $row->type_note == 2 ? $row->nstats : $row->centerjob,
            $row->type_note == 2 ? $row->dt_status->diffInDays(now()) : '',
            $row->approval ? $row->approval->created_at->diffInDays(now()) : '',
            $reclaim ? $reclaim->created_at->diffInDays(now()) : '',
            $reclaim && $reclaim->production ? Notestatus::status($reclaim->production->status)->status : '',

        ];
    }

    public function chunkSize(): int
    {
        return 500;
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
                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->autoSize();

                // Permitir quebra de linha e centralizar vertical e horizontalmente na coluna B
                $event->sheet->getStyle('B:B')->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('A:J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle('A:J')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Formatar coluna B para exibir apenas números inteiros sem vírgula
                $event->sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('0');
            },

        ];
    }
}
