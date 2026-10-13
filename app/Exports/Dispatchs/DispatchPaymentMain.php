<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use App\Helpers\DaysLeft;
use App\Models\Note;
use App\Services\D5\D5WorkReportAssociationResolver;
use App\Services\Payment\WorkReportPaymentCandidateQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromCollection,
    ShouldAutoSize,
    WithEvents,
    WithHeadings,
    WithMapping,
    WithProperties
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DispatchPaymentMain implements FromCollection, WithMapping, WithHeadings, WithProperties, WithEvents, ShouldAutoSize
{
    use Exportable;

    /** @var \Illuminate\Database\Eloquent\Builder */
    protected Builder $queryBuilder;

    protected string $serviceUuid;

    protected array $selectedWorkReportIds;

    protected array $selectedPartialIds;

    protected array $selectedContextKeys;

    protected bool $onlyD5;

    public function __construct(
        Builder $queryBuilder,
        string $serviceUuid,
        array $selectedWorkReportIds = [],
        array $selectedPartialIds = [],
        array $selectedContextKeys = [],
        bool $onlyD5 = false,
    ) {
        $this->queryBuilder          = $queryBuilder;
        $this->serviceUuid           = $serviceUuid;
        $this->selectedWorkReportIds = collect($selectedWorkReportIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->selectedPartialIds = collect($selectedPartialIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->selectedContextKeys = collect($selectedContextKeys)
            ->filter()
            ->map(fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();
        $this->onlyD5 = $onlyD5;
    }

    public function collection()
    {
        $notes = $this->queryBuilder->with([
            'WorkReports' => fn ($q) => $q->where('canceled', false),
            'WorkReports.Note',
            'WorkReports.Company',
            'WorkReports.Orders.Operations',
            'WorkReports.Orders.Pep:objeto,disponivel',
            'WorkReports.Adsform',
            'WorkReports.FiveNote',
            'Productions' => fn ($q) => $q->with(['User', 'Company']),
            'Partials'    => fn ($q) => $q
                ->where('allow', true)
                ->where('deny', false)
                ->where('supervision', true)
                ->where('payment', false)
                ->orderByDesc('created_at'),
            'Partials.Company',
            'Partials.Orders.Operations',
            'Partials.Orders.Pep:objeto,disponivel',
            'FiveNote',
            'FiveNotes',
        ])->get();

        $rows = $notes->flatMap(function ($note) {
            $workForms = $note->WorkReports ?? collect();

            $eligibleWorkReports = $workForms
                ->filter(function ($workForm) use ($note) {
                    $five = $workForm->FiveNote ?: $this->legacyD5ForWorkReport($note, $workForm);

                    return !$five?->returned && (
                        $this->workReportEligibleForPayment($workForm)
                        || $five?->isEligibleForPaymentReturn()
                    );
                })
                ->values();

            if ($eligibleWorkReports->isEmpty()) {
                // Espelha a fila: D5 de Ciclo 1 ainda pendente também é uma
                // demanda exibível; não apenas o retorno de Ciclo 2.
                if ($workForms->isNotEmpty() && !$note->FiveNote?->needsPaymentAction()) {
                    return [];
                }

                $partial = ($note->Partials ?? collect())->first();

                $row = clone $note;

                if ($partial) {
                    $row->setAttribute('dispatch_partial_id', (int) $partial->id);
                    $row->setAttribute('payment_context_key', $note->id . ':0:p' . $partial->id);
                } else {
                    $row->setAttribute('payment_context_key', (string) $note->id);
                }

                return [$row];
            }

            return $eligibleWorkReports->map(function ($workForm) use ($note) {
                $row  = clone $note;
                $five = $workForm->FiveNote ?: $this->legacyD5ForWorkReport($note, $workForm);
                $row->setRelation('FiveNote', $five);
                $row->setRelation('WorkForm', $workForm);
                $row->setRelation('WorkReports', collect([$workForm]));
                $row->setAttribute('payment_work_report_id', (int) $workForm->id);
                $row->setAttribute('dispatch_five_note_id', $five?->id);
                $row->setAttribute('payment_context_key', $note->id . ':' . $workForm->id);

                return $row;
            });
        })->values();

        if ($this->onlyD5) {
            // "Somente D5" não exporta os demais Informes da mesma Nota: só
            // as linhas que efetivamente exibem D5 na fila.
            $rows = $rows->filter(fn ($row) => (bool) $row->FiveNote)->values();
        }

        if (empty($this->selectedContextKeys) && empty($this->selectedWorkReportIds) && empty($this->selectedPartialIds)) {
            return $rows;
        }

        return $rows->filter(function ($row): bool {
            if (!empty($this->selectedContextKeys)) {
                return in_array((string) ($row->payment_context_key ?? $row->id), $this->selectedContextKeys, true);
            }

            return in_array((int) ($row->payment_work_report_id ?? 0), $this->selectedWorkReportIds, true)
                || in_array((int) ($row->dispatch_partial_id ?? 0), $this->selectedPartialIds, true);
        })->values();
    }

    public function map($list): array
    {
        $workForm         = $list->WorkForm;
        $workFormCanceled = (bool) ($workForm?->canceled);
        $partial          = !$workForm ? ($list->Partials?->first()) : null;

        if ($workForm) {
            $type     = 'TOTAL';
            $orders   = $workForm->Orders ?? collect();
            $company  = $workForm->Company?->name;
            $dateInfo = $workForm->informed_at;
            $adsDate  = $workForm->Adsform?->created_at;
        } elseif ($partial) {
            $type     = 'PARCIAL';
            $orders   = $partial->Orders ?? collect();
            $company  = $partial->Company?->name;
            $dateInfo = $partial->created_at;
            $adsDate  = $dateInfo;
        } else {
            $type     = 'DESCONHECIDO';
            $orders   = collect();
            $company  = null;
            $dateInfo = null;
            $adsDate  = null;
        }

        $lastProduction = ($list->Productions ?? collect())
            ->where('service_id', $this->serviceUuid)
            ->sortBy('created_at')
            ->last();

        if ($lastProduction && (($type === 'TOTAL' && $lastProduction->partial) || ($type === 'PARCIAL' && $workForm))) {
            $lastProduction = null;
        }

        $operations  = $orders->first()?->Operations ?? collect();
        $pepCodes    = $orders->pluck('pep')->filter()->implode("\n") ?: '---';
        $pepBalances = $orders
            ->map(fn ($order) => $order->Pep?->disponivel === null
                ? '---'
                : number_format((float) $order->Pep->disponivel, 2, ',', '.'))
            ->implode("\n") ?: '---';
        $executionDate = $orders
            ->flatMap(fn ($order) => $order->Operations ?? collect())
            ->where('operacao', '0020')
            ->pluck('fimReal')
            ->filter()
            ->sort()
            ->first();

        $five     = $list->FiveNote;
        $hasD5    = $five ? 'SIM' : 'NÃO';
        $numberD5 = $five ? (string) $five->note_d5 : '-';
        $statusD5 = $five
            ? ($five->is_supervisioned ? 'Finalizar D5' : 'Gerar D5')
            : '---';

        return [
            $list->note,
            $type . ($workFormCanceled ? ' (CANCELADO)' : ''),
            $orders->isNotEmpty() ? $orders->pluck('ordem')->implode("\n") : '---',
            $pepCodes,
            $pepBalances,
            $orders->sum('moaberto'),
            $operations->where('operacao', '0030')->first()?->status ? explode(' ', $operations->where('operacao', '0030')->first()->status)[0] : '---',
            $operations->where('operacao', '0040')->first()?->status ? explode(' ', $operations->where('operacao', '0040')->first()->status)[0] : '---',
            $operations->where('operacao', '0050')->first()?->status ? explode(' ', $operations->where('operacao', '0050')->first()->status)[0] : '---',
            $operations->where('operacao', '0010')->first()?->cenTrab ?? '---',
            $company ? $company . ($workFormCanceled ? ' (CANCELADO)' : '') : $company,
            $list->lexp ?? '---',
            $executionDate ? Carbon::parse($executionDate)->format('d/m/Y') : '---',
            $dateInfo ? Carbon::parse($dateInfo)->format('d/m/Y') : '---',
            $adsDate ? Carbon::parse($adsDate)->format('d/m/Y') : '---',
            $list->type_note == 2 ? $list->nstats : ($list->centerjob ?? '---'),
            $list->fimLancado ? Carbon::parse($list->fimLancado)->format('d/m/Y') : '---',
            (new DaysLeft($list))->getLastDate(),
            $lastProduction?->User?->name ?? '---',
            $lastProduction ? Notestatus::status($lastProduction->status)->status : '---',
            $hasD5,
            $numberD5,
            $statusD5,
        ];
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Tipo',
            'Ordem',
            'PEP',
            'Saldo disponível PEP',
            'MOA',
            'OP30',
            'OP40',
            'OP50',
            'CentroTrab',
            'Empresa',
            'Município',
            'Data Execução',
            'Data Informe',
            'Data Ads',
            'Status',
            'Dt Final OP20',
            'Prazo Obra',
            'User Production',
            'Status Production',
            'Possui D5',
            'Número D5',
            'Status D5',
        ];
    }

    private function workReportEligibleForPayment($workForm): bool
    {
        return ($workForm->Orders ?? collect())
            ->contains(fn ($order) => app(WorkReportPaymentCandidateQuery::class)->orderIsEligible($order));
    }

    private function legacyD5ForWorkReport(Note $note, $workForm): ?\App\Models\FiveNote
    {
        if (!$workForm || !$note->relationLoaded('FiveNotes')) {
            return null;
        }

        $matches = $note->FiveNotes
            ->filter(fn ($five) => !$five->work_report_id
                && !$five->isPassive
                && $five->needsPaymentAction()
                && app(D5WorkReportAssociationResolver::class)->closestFor($five)?->is($workForm))
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function properties(): array
    {
        $user = auth()->user();
        $name = $user?->name ?? 'SICODE';

        return [
            'creator'        => $name,
            'lastModifiedBy' => $name,
            'title'          => 'Relatorio Automatico SICODE',
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
                $sheet         = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow    = $sheet->getHighestRow();

                // Header bold + fundo
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Larguras dinâmicas
                for ($col = 'A'; $col !== $highestColumn; $col = chr(ord($col) + 1)) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
                $sheet->getColumnDimension($highestColumn)->setWidth(15);

                // Quebra de linha nas células (linhas de dados) e centralização
                if ($highestRow > 1) {
                    $sheet->getStyle("A2:{$highestColumn}{$highestRow}")
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Formatação de números inteiros para Nota (A) e Ordem (C)
                if ($highestRow > 1) {
                    $sheet->getStyle("A2:A{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#');
                    $sheet->getStyle("C2:C{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#');
                    $sheet->getStyle("F2:F{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
