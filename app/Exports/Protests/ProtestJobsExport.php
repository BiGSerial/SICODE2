<?php

namespace App\Exports\Protests;

use App\Models\ProtestJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProtestJobsExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
{
    use Exportable;

    public function __construct(
        protected array $filters
    ) {
    }

    public function query(): Builder
    {
        $start = Carbon::parse($this->filters['start'])->startOfDay();
        $end   = Carbon::parse($this->filters['end'])->endOfDay();
        $advanceFilter = $this->filters['advanceFilter'] ?? 'all';
        $userId = $this->filters['userId'] ?? null;

        return ProtestJob::query()
            ->with([
                'protest:id,nota,tipoNota,cidade,dtAberturaNota,dtConclusaoDesej',
                'medProtest:id,med_id,protest_id,dtCriacaoMedida,dtFimMedidaDesej,dtFimMedida,statusSist',
                'creator:id,name',
                'owner:id,name',
            ])
            ->whereBetween('sent_at', [$start, $end])
            ->when($advanceFilter === 'advance', fn ($q) => $q->where('is_advance', true))
            ->when($advanceFilter === 'normal', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('is_advance', false)
                        ->orWhereNull('is_advance');
                });
            })
            ->when($userId, function ($q) use ($userId) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('created_by', $userId)
                        ->orWhere('owner_id', $userId)
                        ->orWhere('closed_by', $userId);
                });
            })
            ->orderByDesc('sent_at');
    }

    public function map($job): array
    {
        $protest = $job->protest;
        $med     = $job->medProtest;

        return [
            $job->id,
            // $job->protest_id,
            $protest?->nota,
            $protest?->tipoNota,
            $protest?->cidade,
            $this->formatDate($protest?->dtAberturaNota),
            $this->formatDate($protest?->dtConclusaoDesej),
            // $job->med_protest_id,
            $med?->med_id,
            $med?->statusSist,
            $this->formatDate($med?->dtCriacaoMedida),
            $this->formatDate($med?->dtFimMedidaDesej),
            $this->formatDate($med?->dtFimMedida),
            $job->creator?->name,
            $job->owner?->name,
            $job->status?->value ?? $job->status,
            $job->priority?->value ?? $job->priority,
            $job->is_advance ? 'Sim' : 'Não',
            // $job->need_evidence ? 'Sim' : 'Não',
            $job->notes,
            $this->formatDate($job->sent_at),
            $this->formatDate($job->started_at),
            $this->formatDate($job->finished_at),
            $this->formatDate($job->closed_at),
            $job->close_reason,
            $this->formatDate($job->sla_due_at),
            $this->formatDate($job->sla_breached_at),
            $job->closer?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Job ID',
            // 'Protest ID',
            'Nota',
            'Tipo Nota',
            'Cidade',
            'Reclamação aberto em',
            'Reclamação conclusão desejada',
            // 'Med Protest ID',
            'Medida ID',
            'Status MEdida',
            'Med criada em',
            'Med conclusão desejada',
            'Med finalizada em',
            'Criado por',
            'Responsável',
            'Status',
            'Prioridade',
            'Avança parceiro',
            // 'Precisa recebido',
            'Notas',
            'Atividade enviado em',
            'Atividade iniciado em',
            'Atividade finalizado em',
            'Atividade encerrado em',
            'Motivo encerramento',
            'SLA previsto',
            'SLA estourado em',
            'Encerrado por',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    protected function formatDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
