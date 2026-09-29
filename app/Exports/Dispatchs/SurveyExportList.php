<?php

namespace App\Exports\Dispatchs;

use App\Helpers\DaysLeft;
use Carbon\Carbon;
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

class SurveyExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
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
        return $this->query->select([
            'notes.id',
            'notes.note',
            'notes.material',
            'notes.numPedido',
            'notes.rubrica',
            'notes.lexp',
            'notes.group2',
            'notes.group5',
            'notes.type_note',
            'notes.nstats',
            'notes.centerjob',
            'notes.dt_status',
        ])
        ->with([
            'Orders:note_id,ordem,statusSist', // Especificar colunas
            'wpas:note_id,dd',  // Especificar colunas
            'productions' => function ($q) {
                $q->where('service_id', $this->serviceUuid)
                  ->with([
                      'Company:id,name', // Especificar colunas
                      'User:id,name'  // Especificar colunas
                  ]);
            }
        ]);
    }


    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Note', 'Ordem', 'DD', 'Postes', 'NumPedido', 'Rubrica', 'Municipio', 'Gp2', 'Gp5', 'Status', 'Prazo', 'Empresa', 'Usuario', 'Despachado Em', 'Atribuído Em', 'Finalizado Em'
        ];
    }

    public function map($row): array
    {
        if (!isset($this->daysLeftCache[$row->id])) {
            $this->daysLeftCalculator->note = $row;  // Assign row to the daysLeftCalculator
            $this->daysLeftCache[$row->id] = 30 - $this->daysLeftCalculator->getDaysLeft();
        }
        $daysLeft = $this->daysLeftCache[$row->id];

        $ordens = $row->Orders
            ->filter(fn ($order) => !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT'))
            ->pluck('ordem')
            ->implode(" \n");

        $dd = $row->wpas->isNotEmpty() ? $row->wpas->last()->dd : '---';

        $empresa = '---';
        $usuario = '---';

        if ($row->productions->isNotEmpty()) {
            $production = $row->productions->last();
            $empresa = $production->Company?->name ?? '---'; // Uso do operador null safe
            $usuario = $production->User?->name ?? '---';  // Uso do operador null safe
        }

        return [
            $row->note,
            $ordens,
            $dd,
            $row->material ?? '---',
            $row->numPedido,
            $row->rubrica,
            $row->lexp,
            $row->group2,
            $row->group5,
            $row->type_note == 2 ? $row->nstats : $row->centerjob,
            $daysLeft,
            $empresa,
            $usuario,
            $this->formatProductionDate($row->productions->last()?->dispatch_at),
            $this->formatProductionDate($row->productions->last()?->att_at),
            $this->formatProductionDate($row->productions->last()?->completed_at),
        ];
    }

    private function formatProductionDate($date): string
    {
        if (!$date) { return "---"; }
        $value = Carbon::parse($date);
        return $value->format("d/m/Y H:i:s") . " (" . $value->copy()->startOfDay()->diffInDays(now()->startOfDay()) . " dia(s))";
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
                $event->sheet->getStyle('A1:P1')->applyFromArray([
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
