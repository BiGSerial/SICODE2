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
            'work_reports.informed_at as work_dt_informed',
            'notes.postes',
        ]);

        // Eager load relacionamentos
        $query->with([
            'orders' => function ($q) {
                $q->select(['note_id', 'ordem', 'statusSist', 'service_cost']);
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
            'Tipo','Note', 'Nota D5', 'Ordem', 'DD', 'ADS', 'ADS Origem', 'Tipo ADS', 'Data ADS', 'Prazo ADS', 'Informado Em', 'Prazo Informe', 'Usuario Informe', 'Parceira', 'CentroTrab', 'Postes', 'NumPedido', 'Rubrica', 'Municipio', 'Custo', 'Status', 'Dias Informe', 'Dias D5', 'D5 Criada Em', 'D5 Despachada Em', 'D5 Entregue Em', 'Entregue Por', 'Empresa D5', 'Situação', 'Despachado em', 'Atribuido em', 'Empresa', 'Usuario'
        ];
    }

    public function map($row): array
    {
        // Processamento das ordens
        $ordens = '';
        $sumOrders = 0;
        $centrojob = '';
        $partner = '';
        $userInform = '';

        if ($row->Orders && $row->Orders->isNotEmpty()) {


            $ordensArray = $row->Orders->filter(function ($order) {
                return !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT');
            })->pluck('ordem')->toArray();

            $ordensMoa = $row->Orders->filter(function ($order) {
                return !str_starts_with($order->statusSist, 'ENCE') && !str_starts_with($order->statusSist, 'ENT');
            });

            $sumOrders = (float) $ordensMoa->sum('service_cost');
            $ordens = implode(" \n", $ordensArray);

            if (count($ordensArray) > 0) {
                // Pega o centro de trabalho da primeira ordem válida
                $centrojob = $row->Orders()->where('ordem', $ordensArray[0])->first()?->operations?->where('operacao', '0010')->first()?->cenTrab;
            }
        }

        $notaD5 = $row->FiveNote ?? null;
        $diasD5 = '---';

        if ($notaD5 && $notaD5->completed_at) {
            $completedAt = $notaD5->completed_at instanceof Carbon
                ? $notaD5->completed_at->copy()
                : Carbon::parse($notaD5->completed_at);

            $diasD5 = $completedAt->startOfDay()->diffInDays(Carbon::now(), false);
        }

        $dd = $row->wpas->isNotEmpty() ? $row->wpas->last()->dd : '---';

        //Calculando os dias informados
        $informe = '';
        $diasInforme = '---';
        $workForm = $row->WorkForm ?: $row->WorkFormAny;
        if ($workForm) {
            $partner = $workForm->company?->name;
            $userInform = $workForm->user?->name;
            $informe = 'FINAL';
            $diasInforme = $row->work_dt_created ? Carbon::parse($row->work_dt_created)->diffInDays(Carbon::now(), false) : 0;
            if ($workForm->canceled) {
                $informe = 'FINAL (CANCELADO)';
            }
        } elseif ($row->Partials->isNotEmpty()) {
            $informe = 'PARCIAL';
            $diasInforme = $row->Partials->last() ? Carbon::parse($row->Partials->last()->created_at)->diffInDays(Carbon::now(), false) : 0;
        }


        // Obtendo a production relacionada
        $production = $row->productions->isNotEmpty() ? $row->productions->last() : null;

        $status = $row->productions->isNotEmpty() ? Notestatus::status($row->productions->last()->status)->status : null;

        $empresa = $production && $production->Company ? $production->Company->name : '---';
        $usuario = $production && $production->User ? $production->User->name : '---';


        $adsType = null;
        if ($row->adsform) {
            $ads_origin = 'NOVO';
            if ($row->adsform->tacit) {
                $adsType = 'TACITA';
                $ads = $row->adsform->tacit_delivered_at ?? $row->adsform->created_at;
            } else {
                $adsType = 'NORMAL';
                $ads = $row->adsform->created_at;
            }
        } elseif ($row->OldAds->isNotEmpty()) {
            $ads_origin = 'ANTIGO';
            $ads =  $row->OldAds->last()->date;
        } else {
            $ads_origin = 'SEM ADS';
            $ads = null;
        }

        $inPrazo = null;


        $prazoAds = $row->adsform?->tacit_due_at ?? ($row->work_dt_created ? Carbon::parse($row->work_dt_created)->endOfDay()->addDays(6) : null);

        if ($row->work_dt_created) {
            $refDate = $prazoAds ?? $row->work_dt_created;

            if ($ads) {
                $diff = Carbon::parse($refDate)->diffInDays(Carbon::parse($ads), false);
                $inPrazo = $diff > 6 ? 'FORA DO PRAZO' : 'DENTRO DO PRAZO';
            } else {
                $diff = Carbon::parse($refDate)->diffInDays(Carbon::now(), false);
                $inPrazo = $diff > 6 ? 'ATRASADO' : 'EM PRAZO';
            }
        } else {
            $inPrazo = '---';
        }


        return [
            $informe,
            $row->note,
            $notaD5 ? $notaD5->note_d5 : '---',
            $ordens,
            $dd,
            $ads ? 'SIM' : 'NÃO',
            $ads_origin,
            $adsType ?? '---',
            $ads ? $ads->format('d/m/Y') : '---',
            $prazoAds ? $prazoAds->format('d/m/Y') : '---',
            $row->work_dt_informed ? Carbon::parse($row->work_dt_informed)->format('d/m/Y') : '---',
            $inPrazo,
            $userInform,
            $partner,
            $centrojob,
            $row->postes ?? '---',
            $row->numPedido,
            $row->rubrica,
            $row->lexp,
            "R$ ".number_format($sumOrders, 2, ',', '.'),
            $row->type_note == 2 ? $row->nstats : $row->centerjob,
            $diasInforme,
            $diasD5,
            $notaD5 ? ($notaD5->created_at ? $notaD5->created_at->format('d/m/Y H:i:s') : '---') : '---',
            $notaD5 ? ($notaD5->dispatch_at ? $notaD5->dispatch_at->format('d/m/Y H:i:s') : '---') : '---',
            $notaD5 ? ($notaD5->completed_at ? $notaD5->completed_at->format('d/m/Y H:i:s') : '---') : '---',
            $notaD5 ? ($notaD5->name ? $notaD5->name : '---') : '---',
            $notaD5 && $notaD5->company ? $notaD5->company->name : '---',
            $status,
            $production?->dispatch_at->format('d/m/Y H:i:s'),
            $production?->att_at->format('d/m/Y H:i:s'),
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
                $lastCol   = $sheet->getHighestColumn();

                $event->sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
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
