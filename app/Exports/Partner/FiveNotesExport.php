<?php

namespace App\Exports\Partner;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Carbon\Carbon;

class FiveNotesExport implements FromQuery, WithHeadings, WithMapping, WithProperties, WithChunkReading
{
    use Exportable;

    protected Builder $query;
    protected bool $historic;
    protected array $options;

    public function __construct(Builder $query, bool $historic = false, array $options = [])
    {
        $this->query    = $query;
        $this->historic = $historic;
        $this->options  = $options;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        if (!$this->isTrackingMode()) {
            return $this->legacyHeadings();
        }

        $columns = [
            'Nota D5',
            'Nota',
            'Ordem',
            'PEP',
            'Local',
            'Motivo',
            'CodificaÃ§Ã£o',
            'Despachado em',
            'Retorno Empreiteira',
            'Atividade Atual',
        ];

        if ($this->showAssignee()) {
            $columns[] = 'Atribuido';
            $columns[] = 'Empresa Atribuida';
        }

        $columns[] = 'Status';
        $columns[] = 'Timeline';

        $columns[] = 'Empresa';
        $columns[] = 'Passivo?';

        return $columns;
    }

    public function map($five): array
    {
        if (!$this->isTrackingMode()) {
            return $this->legacyMap($five);
        }

        $dispatchAt = optional($five->dispatch_at)->format('d/m/Y H:i');
        $row = [
            $five->note_d5,
            optional($five->note)->note,
            $this->resolveOrder($five),
            $five->pep,
            $five->loc_install,
            $five->reason,
            $five->codify,
            $dispatchAt,
            optional($five->completed_at)->format('d/m/Y H:i'),
            $this->activityLabel($five),
        ];

        if ($this->showAssignee()) {
            $assignee = $this->resolveAssignee($five);
            $row[] = $assignee['name'] ?? 'Sem atribuicao';
            $row[] = $assignee['company'] ?? '-';
        }

        $row[] = $this->resolveStatus($five);
        $row[] = $this->buildTimelineText($five);
        $row[] = optional($five->company)->name;
        $row[] = $five->isPassive ? 'Sim' : 'NÃ£o';

        return $row;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => $this->historic ? 'HistÃ³rico de D5' : 'Lista de D5 pendentes',
            'description'    => 'ExportaÃ§Ã£o contendo os registros filtrados em tela.',
            'subject'        => 'FiveNotes',
            'keywords'       => 'five, notas, export, excel',
            'category'       => 'Exports',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    protected function resolveOrder($five): string
    {
        $workForm = $five->note?->WorkForm ?: $five->note?->WorkFormAny;
        $workFormOrders = optional($workForm)->Orders;
        $workFormOrder  = optional(optional($workFormOrders)->sortBy('ordem'))->first();

        if ($workFormOrder) {
            return (string) $workFormOrder->ordem;
        }

        $orders = optional($five->note)->Orders;
        $order  = optional(optional($orders)->sortBy('ordem'))->first();

        return (string) ($order->ordem ?? '');
    }

    protected function resolveStatus($five): string
    {
        if ($five->is_payed) {
            if ($five->is_archived) {
                return 'Finalizada';
            }

            if ($five->is_supervisioned) {
                return 'Aguardando LiberaÃ§Ã£o Pagamento';
            }

            if ($five->is_completed) {
                return 'Aguardando FiscalizaÃ§Ã£o';
            }

            if ($five->visible_partner) {
                return 'Aguardando ConclusÃ£o Parceira';
            }
        }

        return 'Aguardando Despacho Pagamento';
    }

    protected function showAssignee(): bool
    {
        $statusFilter = (string) ($this->options['statusFilter'] ?? '');
        return in_array($statusFilter, ['aguardando_fiscalizacao', 'aguardando_pagamento'], true);
    }

    protected function isTrackingMode(): bool
    {
        return (bool) ($this->options['d5_tracking'] ?? false);
    }

    protected function legacyHeadings(): array
    {
        $columns = [
            'Nota D5',
            'Nota',
            'Ordem',
            'PEP',
            'Local',
            'Motivo',
            'CodificaÃ§Ã£o',
            'Despachado em',
        ];

        if ($this->historic) {
            $columns[] = 'ConcluÃ­do em';
            $columns[] = 'Status';
        } else {
            $columns[] = 'Dias em aberto';
            $columns[] = 'Status';
        }

        $columns[] = 'Empresa';
        $columns[] = 'Passivo?';

        return $columns;
    }

    protected function legacyMap($five): array
    {
        $dispatchAt = optional($five->dispatch_at)->format('d/m/Y H:i');
        $row = [
            $five->note_d5,
            optional($five->note)->note,
            $this->resolveOrder($five),
            $five->pep,
            $five->loc_install,
            $five->reason,
            $five->codify,
            $dispatchAt,
        ];

        if ($this->historic) {
            $row[] = optional($five->completed_at)->format('d/m/Y H:i');
            $row[] = $this->resolveStatus($five);
        } else {
            $row[] = $five->dispatch_at instanceof Carbon
                ? $five->dispatch_at->diffInDays(now())
                : '';
            $row[] = $this->resolveStatus($five);
        }

        $row[] = optional($five->company)->name;
        $row[] = $five->isPassive ? 'Sim' : 'NÃ£o';

        return $row;
    }

    protected function activityKey($five): string
    {
        if ($five->is_archived) {
            return 'finalizado';
        }
        if ($five->is_supervisioned) {
            return 'aguardando_pagamento';
        }
        if ($five->is_completed) {
            return 'aguardando_fiscalizacao';
        }
        return 'aguardando_fornecedor';
    }

    protected function activityLabel($five): string
    {
        return match ($this->activityKey($five)) {
            'finalizado' => 'Finalizado',
            'aguardando_pagamento' => 'Aguardando Pagamento',
            'aguardando_fiscalizacao' => 'Aguardando Fiscalizacao',
            default => 'Aguardando Fornecedor',
        };
    }

    protected function resolveAssignee($five): array
    {
        $activityKey = $this->activityKey($five);

        if (!in_array($activityKey, ['aguardando_fiscalizacao', 'aguardando_pagamento'], true)) {
            return ['name' => null, 'company' => null];
        }

        $targetService = $activityKey === 'aguardando_fiscalizacao'
            ? ($this->options['fiscalization_service_id'] ?? null)
            : ($this->options['payment_service_id'] ?? null);

        if (!$targetService) {
            return ['name' => null, 'company' => null];
        }

        $productions = optional($five->note)->Productions ?? collect();
        $partnerReturnAt = $five->completed_at;

        $candidates = $productions
            ->where('service_id', $targetService)
            ->where('completed', false);

        if ($partnerReturnAt instanceof Carbon) {
            $candidates = $candidates->filter(function ($production) use ($partnerReturnAt) {
                $mark = $production->att_at ?: $production->created_at;
                return $mark instanceof Carbon && $mark->greaterThanOrEqualTo($partnerReturnAt);
            });
        }

        $candidate = $candidates
            ->sortByDesc(function ($production) {
                return $production->att_at ?: $production->created_at;
            })
            ->first();

        if (!$candidate) {
            $candidate = $productions
                ->where('service_id', $targetService)
                ->where('completed', false)
                ->sortByDesc(function ($production) {
                    return $production->att_at ?: $production->created_at;
                })
                ->first();
        }

        return [
            'name' => $candidate?->User?->name,
            'company' => $candidate?->Company?->name,
        ];
    }

    protected function buildTimelineText($five): string
    {
        $steps = [
            'Despacho' => $five->dispatch_at,
            'Retorno empreiteira' => $five->completed_at,
            'Fiscalizacao' => $five->supervisioned_at,
            'Pagamento' => $five->payed_at,
            'Finalizado' => $five->is_archived ? ($five->updated_at ?? null) : null,
        ];

        $parts = [];

        foreach ($steps as $label => $date) {
            if ($date instanceof Carbon) {
                $parts[] = $label . ': ' . $date->format('d/m/Y H:i');
            } else {
                $parts[] = $label . ': -';
            }
        }

        return implode(' | ', $parts);
    }
}
