<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
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

class SupervisionExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;

    protected $exports;
    protected $service;
    protected $serviceUuid;

    public function __construct($data, $service)
    {
        $this->exports = $data;
        $this->service = $service;
        $this->serviceUuid = $service;
    }

    public function query()
    {
        $query = $this->exports;

        // Selecionar as colunas necessárias
        $query->select([
            'notes.id',
            'notes.note',
            'notes.numPedido',
            'notes.rubrica',
            'notes.lexp',
            'notes.type_note',
            'notes.nstats',
            'notes.centerjob',
            'work_reports.created_at as work_dt_created', // Assumindo que 'work_dt_created' está correto
            'notes.postes',
        ]);

        // Eager load relacionamentos
        $query->with([
            'orders' => function ($q) {
                $q->select(['note_id', 'ordem', 'statusSist', 'moaberto']);
            },
            'wpas' => function ($q) {
                $q->select(['note_id', 'dd']);
            },
            'productions' => function ($q) {
                $q->where('service_id', $this->serviceUuid);
                $q->with(['Company' => function ($q) {
                    $q->select(['id', 'name']);
                }, 'User' => function ($q) {
                    $q->select(['id', 'name']);
                }]);
            },
            'Adsform', 'OldAds'
        ]);

        return $query;
    }

    public function chunkSize(): int
    {
        return 1000; // Experimente valores maiores
    }

    public function headings(): array
    {
        return [
            'Tipo','Note', 'Ordem', 'DD', 'ADS Origem', 'Data ADS', 'Postes', 'Informado Em', 'NumPedido', 'Rubrica', 'Municipio', 'MOA', 'Status', 'Dias Informe', 'Situação', 'Empresa', 'Usuario'
        ];
    }

    public function map($row): array
    {
        // Processamento das ordens
        $ordens = '';
        $sumOrders = 0;
        if ($row->orders) {


            $ordensArray = $row->Orders->filter(function ($order) {
                return !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT');
            })->pluck('ordem')->toArray();

            $ordensMoa = $row->Orders->filter(function ($order) {
                return !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT');
            });

            $sumOrders = $ordensMoa->sum('moaberto');
            $ordens = implode(" \n", $ordensArray);

        }

        $dd = $row->wpas->isNotEmpty() ? $row->wpas->last()->dd : '---';

        //Calculando os dias informados
        $informe = '';
        if ($row->WorkForm) {
            $informe = 'FINAL';
            $diasInforme = $row->work_dt_created ? Carbon::parse($row->work_dt_created)->diffInDays(Carbon::now(), false) : 0;
        } elseif ($row->Partials->isNotEmpty()) {
            $informe = 'PARCIAL';
            $diasInforme = $row->Partials->last() ? Carbon::parse($row->Partials->last()->created_at)->diffInDays(Carbon::now(), false) : 0;
        }


        // Obtendo a production relacionada
        $production = $row->productions->isNotEmpty() ? $row->productions->last() : null;

        $status = $row->productions->isNotEmpty() ? Notestatus::status($row->productions->last()->status)->status : null;

        $empresa = $production && $production->Company ? $production->Company->name : '---';
        $usuario = $production && $production->User ? $production->User->name : '---';


        if ($row->adsform) {
            $ads_origin = 'NOVO';
            $ads = $row->adsform->created_at->format('d/m/Y');
        } elseif ($row->OldAds->isNotEmpty()) {
            $ads_origin = 'ANTIGO';
            $ads =  $row->OldAds->last()->date->format('d/m/Y');
        } else {
            $ads_origin = 'SEM ADS';
            $ads = null;
        }

        return [
            $informe,
            $row->note,
            $ordens,
            $dd,
            $ads_origin,
            $ads ? $ads : '---',
            $row->postes ?? '---',
            $row->work_dt_created ? Carbon::parse($row->work_dt_created)->format('d/m/Y') : '---',
            $row->numPedido,
            $row->rubrica,
            $row->lexp,
            "R$ ".number_format($sumOrders, 2, ',', '.'),
            $row->type_note == 2 ? $row->nstats : $row->centerjob,
            $diasInforme,
            $status,
            $empresa,
            $usuario,
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
                // Centraliza verticalmente e horizontalmente todas as células e habilita quebras de linha
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A:Z')->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);

                $event->sheet->getStyle('A1:Q1')->applyFromArray([
                    'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);
                $event->sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('D')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->autoSize();
            },
        ];
    }
}
