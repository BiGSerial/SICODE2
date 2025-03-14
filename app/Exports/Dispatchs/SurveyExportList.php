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
use Illuminate\Support\Facades\DB; // Importe a fachada DB
use Illuminate\Support\Facades\Cache; // Importe a fachada Cache

class SurveyExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $exports;
    protected $service;
    protected $serviceUuid; // Use UUID para o serviço
    protected $daysLeftCache = []; // Cache para DaysLeft
    protected $selectedIds; // IDs selecionados para otimização

    public function __construct($data, $service, $selectedIds = [])
    {
        $this->exports = $data;
        $this->service = $service;
        $this->serviceUuid = $service; // Armazene o UUID
        $this->selectedIds = $selectedIds; // Armazene os IDs selecionados

    }

    public function query()
    {
        $query = $this->exports;

        // Selecionar as colunas necessárias e otimizar o eager loading
        $query->select([
            'notes.id',  // Importante incluir o ID
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
            'notes.dt_status', // Importante para o DaysLeft
        ]);

        // Carregar os relacionamentos necessários
        $query->with([
            'Orders' => function ($q) {
                $q->select(['note_id', 'ordem', 'statusSist']);  // Selecionar apenas as colunas necessárias
            },
            'wpas' => function ($q) {
                $q->select(['note_id', 'dd']);  // Selecionar apenas as colunas necessárias
            },
            'productions' => function ($q) {
                $q->where('service_id', $this->serviceUuid); // Usar UUID
                $q->with(['Company' => function ($q) {
                    $q->select(['id', 'name']);  // Selecionar apenas as colunas necessárias
                }, 'User' => function ($q) {
                    $q->select(['id', 'name']);  // Selecionar apenas as colunas necessárias
                }]);
            }
        ]);
        return $query;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Note', 'Ordem', 'DD', 'Postes', 'NumPedido', 'Rubrica', 'Municipio', 'Gp2', 'Gp5', 'Status', 'Prazo', 'Empresa', 'Usuario'
        ];
    }

    public function map($row): array
    {
        // Cache para DaysLeft
        if (!isset($this->daysLeftCache[$row->id])) {
            $this->daysLeftCache[$row->id] = 30 - (new DaysLeft($row))->getDaysLeft();
        }
        $daysLeft = $this->daysLeftCache[$row->id];

        // Processamento das ordens
        $ordens = '';
        if ($row->Orders) {
            $ordensArray = $row->Orders->filter(function ($order) {
                return !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT');
            })->pluck('ordem')->toArray();
            $ordens = implode(" \n", $ordensArray);
        }

        // Obtenção da DD
        $dd = $row->wpas->isNotEmpty() ? $row->wpas->last()->dd : '---';

        // Obtenção da empresa e do usuário
        $empresa = '---';
        $usuario = '---';

        if ($row->productions->isNotEmpty()) {
            $production = $row->productions->last();
            $empresa = $production->Company ? $production->Company->name : '---';
            $usuario = $production->User ? $production->User->name : '---';
        }

        return [
            $row->note,
            $ordens,
            $dd,
            $row->postes ?? '---',
            $row->numPedido,
            $row->rubrica,
            $row->lexp,
            $row->group2,
            $row->group5,
            $row->type_note == 2 ? $row->nstats : $row->centerjob,
            $daysLeft,
            $empresa,
            $usuario,
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
                $event->sheet->getStyle('A1:N1')->applyFromArray([
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
