<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use App\Helpers\DaysLeft;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{
    Exportable, FromCollection, WithMapping, WithHeadings, WithProperties,
    WithEvents, ShouldAutoSize
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

    public function __construct(
        Builder $queryBuilder,
        string $serviceUuid,
        array $selectedWorkReportIds = [],
        array $selectedPartialIds = [],
    )
    {
        $this->queryBuilder = $queryBuilder;
        $this->serviceUuid  = $serviceUuid;
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
    }

    public function collection()
    {
        $notes = $this->queryBuilder->with([
            'WorkForms.Note',
            'WorkForms.Company',
            'WorkForms.Orders.Operations',
            'WorkForms.Adsform',
            'Partials' => fn ($q) => $q
                ->where('allow', true)
                ->where('deny', false)
                ->where('supervision', true)
                ->where('payment', false)
                ->orderByDesc('created_at'),
            'Partials.Company',
            'Partials.Orders.Operations',
            'FiveNote',
        ])->get();

        $rows = $notes->flatMap(function ($note) {
            $workForms = $note->WorkForms ?? collect();

            $eligibleWorkForms = $workForms
                ->filter(fn ($workForm) => $this->workReportEligibleForPayment($workForm))
                ->values();

            if ($eligibleWorkForms->isEmpty()) {
                if ($workForms->isNotEmpty() && !$this->isD5ReturnReadyForPayment($note)) {
                    return [];
                }

                $partial = ($note->Partials ?? collect())->first();

                $row = clone $note;
                if ($partial) {
                    $row->setAttribute('dispatch_partial_id', (int) $partial->id);
                }

                return [$row];
            }

            return $eligibleWorkForms->map(function ($workForm) use ($note) {
                $row = clone $note;
                $row->setRelation('WorkForm', $workForm);
                $row->setRelation('WorkForms', collect([$workForm]));
                $row->setAttribute('payment_work_report_id', (int) $workForm->id);

                return $row;
            });
        })->values();

        if (empty($this->selectedWorkReportIds) && empty($this->selectedPartialIds)) {
            return $rows;
        }

        return $rows->filter(function ($row): bool {
            return in_array((int) ($row->payment_work_report_id ?? 0), $this->selectedWorkReportIds, true)
                || in_array((int) ($row->dispatch_partial_id ?? 0), $this->selectedPartialIds, true);
        })->values();
    }

    public function map($list): array
    {
        $workForm = $list->WorkForm;
        $partial = !$workForm ? ($list->Partials?->first()) : null;
        $orders = $workForm
            ? ($workForm->Orders ?? collect())
            : ($partial?->Orders ?? collect());

        $type = $partial ? 'PARCIAL' : 'TOTAL';
        $scope = $workForm
            ? collect($workForm->finalScopeBadges())->pluck('label')->implode(' / ')
            : '---';

        $five = $list->FiveNote;
        $noteLabel = $five ? 'D5 ' . $list->note : (string) $list->note;
        $company = $workForm?->Company?->name ?? $partial?->Company?->name ?? '---';

        $statusFor = function ($order, string $operation): string {
            $match = ($order->Operations ?? collect())->firstWhere('operacao', $operation);

            return $match?->status
                ? explode(' ', (string) $match->status)[0]
                : '---';
        };

        $centerFor = function ($order): string {
            $match = ($order->Operations ?? collect())->firstWhere('operacao', '0010');

            return $match?->cenTrab
                ? explode(' ', (string) $match->cenTrab)[0]
                : '---';
        };

        $finalOp20 = $orders
            ->flatMap(fn ($order) => $order->Operations ?? collect())
            ->where('operacao', '0020')
            ->pluck('fimReal')
            ->filter()
            ->sort()
            ->first();

        $informedAt = $workForm?->informed_at ?? $partial?->created_at;
        $adsDate = $workForm?->Adsform?->created_at;
        $inspectionDate = $partial
            ? ($partial->supervision_at ? Carbon::parse($partial->supervision_at)->addDays(5) : null)
            : ($list->fimLancado ? Carbon::parse($list->fimLancado) : null);
        $moa = $workForm ? ($list->total_moaberto ?? 0) : ($partial?->value ?? 0);

        return [
            $noteLabel,
            $type . ' / ' . $scope,
            $orders->isNotEmpty() ? $orders->pluck('ordem')->implode("\n") : '---',
            $moa,
            $orders->isNotEmpty() ? $orders->map(fn ($order) => $statusFor($order, '0030'))->implode("\n") : '---',
            $orders->isNotEmpty() ? $orders->map(fn ($order) => $statusFor($order, '0040'))->implode("\n") : '---',
            $orders->isNotEmpty() ? $orders->map(fn ($order) => $statusFor($order, '0050'))->implode("\n") : '---',
            $orders->isNotEmpty() ? $orders->map($centerFor)->implode("\n") : '---',
            $company,
            $list->lexp ?? '---',
            $finalOp20 ? Carbon::parse($finalOp20)->format('d/m/Y') : '---',
            $informedAt ? Carbon::parse($informedAt)->format('d/m/Y H:i:s') : '---',
            $adsDate ? Carbon::parse($adsDate)->format('d/m/Y H:i:s') : '----',
            $inspectionDate ? $inspectionDate->format('d/m/Y') : '---',
            (new DaysLeft($list))->getLastDate(),
        ];
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Tipo / Escopo',
            'Ordem',
            'MOA',
            'OP30',
            'OP40',
            'OP50',
            'CentroTrab',
            'Empresa',
            'Município',
            'Final OP20',
            'Data Informe',
            'ADS',
            'Fiscalizado',
            'Data Vencimento',
        ];
    }

    private function workReportEligibleForPayment($workForm): bool
    {
        $orders = $workForm->Orders ?? collect();
        $normalizedStatus = fn ($status) => strtoupper(strtok((string) $status, ' ') ?: (string) $status);

        return $orders->contains(function ($order) use ($normalizedStatus) {
            if (!str_starts_with($normalizedStatus($order->statusSist ?? ''), 'LIB')) {
                return false;
            }

            $statuses = function (string $operation) use ($order, $normalizedStatus) {
                return collect($order->Operations ?? [])
                    ->where('operacao', $operation)
                    ->pluck('status')
                    ->map($normalizedStatus);
            };

            return $statuses('0030')->contains(fn ($status) => str_starts_with($status, 'CONF'))
                && $statuses('0040')->contains(fn ($status) => str_starts_with($status, 'LIB') || str_starts_with($status, 'CONF') || str_starts_with($status, 'CNPA'))
                && $statuses('0050')->contains(fn ($status) => str_starts_with($status, 'LIB') || str_starts_with($status, 'CNPA') || str_starts_with($status, 'JBFI'));
        });
    }

    private function isD5ReturnReadyForPayment($note): bool
    {
        $five = $note->FiveNote;

        return (bool) (
            $five
            && !$five->is_archived
            && $five->is_completed
            && $five->is_supervisioned
        );
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
                    $sheet->getStyle("D2:D{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
