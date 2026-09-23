<?php

namespace App\Exports\Dispatchs;

use App\Support\SicodeRules;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SupervisionExportList implements FromCollection, WithEvents, WithProperties, WithHeadings, WithMapping
{
    use Exportable;

    protected $exports;
    protected $service;
    protected $serviceUuid;
    protected array $selectedWorkReportIds;
    protected array $selectedPartialIds;

    public function __construct($data, $service, array $selectedWorkReportIds = [], array $selectedPartialIds = [])
    {
        $this->exports = $data;
        $this->service = $service;
        $this->serviceUuid = $service;
        $this->selectedWorkReportIds = collect($selectedWorkReportIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $this->selectedPartialIds = collect($selectedPartialIds)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function collection()
    {
        $notes = $this->exports->with([
            'orders' => fn ($q) => $q->where('statusSist', 'not like', 'ENT%')->where('statusSist', 'not like', 'ENC%'),
            'WorkForms.Orders',
            'WorkForms.Adsform',
            'WorkForms.FiveNote',
            'WorkForms.Company',
            'Productions.User',
            'Productions.Company',
            'Productions.WorkReportFlowProductions',
            'Wpas',
            'Partials.Orders',
            'OldAds',
            'FiveNote',
        ])->get()->unique('id')->values();

        $rows = $notes->flatMap(function ($note) {
            $workForms = $note->WorkForms ?? collect();

            if ($workForms->isNotEmpty()) {
                return $workForms->map(function ($workForm) use ($note) {
                    $row = clone $note;
                    $row->setRelation('WorkForm', $workForm);
                    $row->setAttribute('dispatch_work_report_id', (int) $workForm->id);
                    return $row;
                });
            }

            return [clone $note];
        })->values();

        if (empty($this->selectedWorkReportIds) && empty($this->selectedPartialIds)) {
            return $rows;
        }

        return $rows->filter(function ($row): bool {
            $workReportId = (int) ($row->dispatch_work_report_id ?? 0);
            $partialId = (int) (($row->Partials ?? collect())
                ->where('allow', true)
                ->where('supervision', false)
                ->where('deny', false)
                ->sortByDesc('created_at')
                ->first()?->id ?? 0);

            return in_array($workReportId, $this->selectedWorkReportIds, true)
                || in_array($partialId, $this->selectedPartialIds, true);
        })->values();
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Nota',
            'Ordem',
            'DD',
            'MMGD',
            'Postes',
            'Informado Em',
            'Dt ADS',
            'numPedido',
            'Rubrica',
            'Municipio',
            'Custo',
            'Fiscalizações',
            'Status',
            'Dias D5',
            'Situação',
        ];
    }

    public function map($row): array
    {
        $workForm = $row->WorkForm;
        $partial = !$workForm
            ? ($row->Partials?->where('allow', true)->where('supervision', false)->where('deny', false)->sortByDesc('created_at')->first())
            : null;
        $orders = $workForm?->Orders ?? $partial?->Orders ?? collect();
        $production = ($row->Productions ?? collect())
            ->where('service_id', $this->serviceUuid)
            ->sortByDesc('created_at')
            ->first();
        $informedAt = $workForm?->informed_at ?? $partial?->created_at;

        $scope = $workForm
            ? collect($workForm->finalScopeBadges())->pluck('label')->implode(' / ')
            : '---';
        $typeLabel = ($partial ? 'P' : 'F') . ($scope !== '---' ? ' / ' . $scope : '');

        $ads = $workForm?->Adsform;
        $oldAds = $row->OldAds?->last();
        $adsDate = $oldAds?->date ?? $ads?->created_at;
        $adsLabel = $adsDate ? Carbon::parse($adsDate)->format('d/m/Y H:i:s') : '---';
        if ($ads?->tacit) {
            $adsLabel .= ' (TÁCITO)';
        }

        $fiscalizations = ($row->Productions ?? collect())
            ->where('service_id', $this->serviceUuid)
            ->filter(function ($production) use ($workForm) {
                $workReportId = (int) ($workForm?->id ?? 0);
                return $workReportId === 0
                    ? true
                    : $production->WorkReportFlowProductions
                        ->where('work_report_id', $workReportId)
                        ->where('stage', AppModelsWorkReportFlowProduction::STAGE_FISCALIZATION)
                        ->where('is_current', true)
                        ->isNotEmpty();
            });
        $fiscalizationValue = $fiscalizations->count()
            ? $fiscalizations->count() . ' - ' . ($fiscalizations->first()?->User?->name ?? '--')
            : '--';

        $d5 = $row->FiveNote;
        $d5Days = $d5?->completed_at
            ? Carbon::parse($d5->completed_at)->startOfDay()->diffInDays(Carbon::now())
            : '---';

        return [
            $typeLabel,
            $d5?->is_completed && !$d5?->is_supervisioned ? 'D5 ' . $row->note : $row->note,
            $orders->isNotEmpty() ? $orders->pluck('ordem')->implode("\n") : '---',
            SicodeRules::dispatchDdFor($row, $this->serviceUuid, $production) ?? '',
            $row->mmgd ? 'MMGD' : '---',
            $row->postes ?? '---',
            $informedAt ? Carbon::parse($informedAt)->format('d/m/Y') : '---',
            $adsLabel,
            mb_strtoupper((string) ($row->numPedido ?? '')),
            $row->rubrica ?? '---',
            $row->lexp ?? '---',
            'R$ ' . number_format((float) ($row->orders?->sum('service_cost') ?? 0), 2, ',', '.'),
            $fiscalizationValue,
            ($row->nstats ?? '---') . ' / ' . ($row->centerjob ?? '---'),
            $d5Days,
            $row->pze_parecer ?? 'DESCONHECIDO',
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
                $lastCol   = $sheet->getHighestColumn();

                $sheet->getStyle('A:' . $lastCol)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $sheet->getStyle('A:' . $lastCol)->getAlignment()->setWrapText(true);

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
                $event->sheet->getStyle('I:L')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->autoSize();
            },
        ];
    }
}
