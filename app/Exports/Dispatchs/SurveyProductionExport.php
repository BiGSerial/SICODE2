<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use App\Custom\WpaStatus;
use App\Helpers\DaysLeft;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Database\Eloquent\Builder;

class SurveyProductionExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $serviceUuid;
    protected $selectedIds;
    protected $daysLeftCache = [];
    protected $daysLeftCalculator;
    protected $query;

    public function __construct(Builder $query, $serviceUuid, array $selectedIds = [])
    {
        $this->query = $query;
        $this->serviceUuid = $serviceUuid;
        $this->selectedIds = $selectedIds;
        $this->daysLeftCalculator = new DaysLeft(); // Initialize DaysLeft outside loop
    }

    public function query()
    {
        return $this->query->when($this->selectedIds, function ($q) {
            return $q->whereIn('id', $this->selectedIds);
        })->with('note', 'dispatcher', 'att', 'service', 'user', 'company', 'wpas', 'analise');
    }


    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Rubrica',
            'Municipio',
            'Material',
            'DD',
            'StsDD',
            'Grupo2',
            'MMGD',
            'Despachado Por',
            'Empresa Despacho',
            'Atribuído Por',
            'Empresa Atribuído',
            'Data Status',
            'Despachado Em',
            'Atribuído Em',
            'Dias Despachado',
            'Dias Atribuído',
            'Retorno Interno',
            'Usuário',
            'Empresa',
            'Status',
            'Mesalização',
            'Prazo',
            'Finalizado em',
        ];
    }

    public function map($row): array
    {
        if (!isset($this->daysLeftCache[$row->note->id])) {
            $this->daysLeftCalculator->note = $row->note;  // Assign row to the daysLeftCalculator
            $this->daysLeftCache[$row->note->id] = 30 - $this->daysLeftCalculator->getDaysLeft();
        }
        $daysLeft = $this->daysLeftCache[$row->note->id];



        $dd = $row->wpas->isNotEmpty() ? $row->wpas->last()->dd : '---';
        $stsDD = $row->wpas->isNotEmpty() ? $row->wpas->last() : null;


        if ($stsDD) {
            $wpa = WpaStatus::status(
                $stsDD->stats,
                $stsDD->execstats,
                $stsDD->completed_at,
            );

            $stsDD = $wpa->info;
        }

        $empresa = '---';
        $usuario = '---';

        $empresa = $row->Company?->name ?? '---'; // Uso do operador null safe
        $usuario = $row->User?->name ?? '---';  // Uso do operador null safe

        return [
            $row->note->note,
            $row->note->rubrica,
            $row->note->lexp,
            $row->note->material,
            $dd,
            $stsDD,
            $row->note->group2,
            $row->note->mmgd ? 'Sim' : 'Não',
            $row->dispatcher?->name ?? '---',
            $row->dispatcher?->company?->name ?? '---',
            $row->att?->name ?? '---',
            $row->att?->company?->name ?? '---',
            $row->dt_note?->format('d/m/Y H:i'),
            $row->dispatch_at ? $row->dispatch_at->format('d/m/Y H:i') : '---',
            $row->att_at ? $row->att_at->format('d/m/Y H:i') : '---',
            $row->dispatch_at ? $row->dispatch_at->diffInDays() : '---',
            $row->att_at ? $row->att_at->diffInDays() : '---',
            $row->d5 ? 'Sim' : 'Não',
            $row->user?->name ?? '---',
            $row->company?->name ?? '---',
            Notestatus::status($row->status)->status,
            $row->note->mesalization,
            $daysLeft,
            $row->completed_at ? $row->completed_at->format('d/m/Y H:i') : '---',
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Sicode',
            'lastModifiedBy' => 'Sicode',
            'title'          => 'Survey List',
            'description'    => 'List of all Survey notes',
            'subject'        => 'Survey List',
            'keywords'       => 'Survey, list, sicode',
            'category'       => 'Survey',
            'manager'        => 'Sicode',
            'company'        => 'Sicode',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:W1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);
                $event->sheet->getStyle('A')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode('0');

                $event->sheet->autoSize();
            },
        ];
    }
}
