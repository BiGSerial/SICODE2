<?php

namespace App\Exports\Protests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OpenProtestJobsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    protected Builder $query;
    protected bool $includeOwner;

    public function __construct(Builder $query, bool $includeOwner = false)
    {
        $this->query = $query;
        $this->includeOwner = $includeOwner;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        $headings = [
            'Prioridade',
            'Reclamação',
            'Tipo',
            'Nota ref.',
            'Município',
        ];

        if ($this->includeOwner) {
            $headings[] = 'Responsável';
        }

        return array_merge($headings, [
            'SLA limite',
            'Status',
            'Descrição do job',
            'Enviado em',
        ]);
    }

    public function map($job): array
    {
        $protest = $job->protest;
        $med = $job->medProtest;
        $noteRef = null;

        if ($med && $med->Notes?->isNotEmpty()) {
            $noteRef = $med->Notes->last()->note;
        } elseif ($protest && $protest->Notes?->isNotEmpty()) {
            $noteRef = $protest->Notes->last()->note;
        }

        $row = [
            $job->priority_label ?? '—',
            $protest?->nota ?? '—',
            $protest?->tipoNota ?? '—',
            $noteRef ?? '—',
            $protest?->cidade ?? '—',
        ];

        if ($this->includeOwner) {
            $row[] = $job->owner?->name ?? '—';
        }

        return array_merge($row, [
            $job->sla_due_at ? Carbon::parse($job->sla_due_at)->format('d/m/Y H:i') : '—',
            $job->status_label ?? '—',
            $job->notes ?? '—',
            $job->sent_at ? Carbon::parse($job->sent_at)->format('d/m/Y H:i') : '—',
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
