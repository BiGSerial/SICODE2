<?php

namespace App\Exports\Protests;

use App\Enum\ProtestJobStatus;
use App\Models\ProtestJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringProtestJobsExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, WithEvents
{
    use Exportable;

    protected Carbon $reportGeneratedAt;

    public function __construct(
        protected array $filters
    ) {
        $this->reportGeneratedAt = now();
    }

    public function query(): Builder
    {
        $query = ProtestJob::query()
            ->with([
                'medProtest:id,protest_id,med_id,statusSist,dtCriacaoMedida,dtFimMedidaDesej,dtFimMedida',
                'protest:id,nota,tipoNota,cidade,codecodf,txtGrpCodificacao,dtAberturaNota,dtConclusaoDesej',
                'owner:id,name,company_id',
                'owner.company:id,name',
                'creator:id,name',
            ])
            ->where('confirmed', '!=', true)
            ->orderBy('priority', 'desc')
            ->orderBy('sla_due_at')
            ->orderBy('id');

        $showOnlyBtzero = (bool) ($this->filters['showOnlyBtzero'] ?? false);
        $hideBtzero = (bool) ($this->filters['hideBtzero'] ?? true);
        if ($showOnlyBtzero) {
            $hideBtzero = false;
        }

        if ($showOnlyBtzero) {
            $query->whereHas('medProtest', function ($q) {
                $q->identifiedAsBtzero();
            });
        } elseif ($hideBtzero) {
            $query->where(function ($sub) {
                $sub->whereNull('med_protest_id')
                    ->orWhereHas('medProtest', function ($q) {
                        $q->notIdentifiedAsBtzero();
                    });
            });
        }

        $query->when(!empty($this->filters['userViewer']), function (Builder $q) {
            $user = User::find($this->filters['userViewer']);
            if (!$user) {
                $q->whereRaw('1 = 0');
                return;
            }

            $onlySelectedUser = (bool) ($this->filters['onlySelectedUser'] ?? false);
            $ownerIds = $onlySelectedUser
                ? [$user->id]
                : $user->descendantsQuery(true, true, true)->pluck('users.id')->toArray();

            $q->where(function ($qq) use ($ownerIds, $onlySelectedUser) {
                $qq->whereIn('owner_id', $ownerIds);

                if (!$onlySelectedUser) {
                    $qq->orWhereNull('owner_id');
                }
            });
        });

        $query->when(!empty($this->filters['search']), function (Builder $q) {
            $term = '%' . $this->filters['search'] . '%';

            $q->where(function (Builder $qq) use ($term) {
                $qq->where('id', 'like', $term)
                    ->orWhereHas('protest', function (Builder $sub) use ($term) {
                        $sub->where('nota', 'like', $term)
                            ->orWhere('cidade', 'like', $term)
                            ->orWhere('txtGrpCodificacao', 'like', $term)
                            ->orWhere('codecodf', 'like', $term);
                    })
                    ->orWhereHas('owner', function (Builder $sub) use ($term) {
                        $sub->where('name', 'like', $term);
                    });
            });
        });

        $query->when(!empty($this->filters['typeNote']), function (Builder $q) {
            $q->whereHas('protest', function (Builder $sub) {
                $sub->where('tipoNota', $this->filters['typeNote']);
            });
        });

        $query->when(!empty($this->filters['slaFilter']), function (Builder $q) {
            $now = now();
            $q->whereNotNull('sla_due_at');

            if ($this->filters['slaFilter'] === 'overdue') {
                $q->where('sla_due_at', '<', $now);
            } elseif ($this->filters['slaFilter'] === 'dueSoon') {
                $q->whereBetween('sla_due_at', [$now, $now->clone()->addDays(3)]);
            } elseif ($this->filters['slaFilter'] === 'within') {
                $q->where('sla_due_at', '>', $now->clone()->addDays(3));
            }
        });

        $deadlineCardFilter = $this->filters['deadlineCardFilter'] ?? null;
        if (!empty($deadlineCardFilter)) {
            $today = now()->toDateString();

            if ($deadlineCardFilter === 'due_today') {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', $today);
                    });
                });
            } elseif ($deadlineCardFilter === 'overdue') {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', '<', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', '<', $today);
                    });
                });
            } elseif ($deadlineCardFilter === 'finished_pending') {
                $query->where('status', ProtestJobStatus::DONE->value);
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Prioridade',
            'Despachante',
            'Tipo',
            'Avança parceiro',
            'Nota',
            'Medida',
            'Cód',
            'Tipo Reclamação',
            'Município',
            'Responsável',
            'Empresa',
            'Abertura',
            'Fim desejado',
            'Despachado em',
            'Status',
            'SLA definido',
            'Finalizado em',
            'SLA',
            'PRAZO',
            'Status Sist Medida',
            'Dt Fim Medida',
            'Resultado OU/PR',
        ];
    }

    public function map($job): array
    {
        $opened = $this->resolveOpenedDate($job);
        $desired = $this->resolveDesiredDate($job);
        $finishedAt = $job->finished_at;
        $medFinishedAt = $job->medProtest?->dtFimMedida;

        return [
            $job->priority?->value ?? $job->priority,
            $job->creator?->name,
            $job->protest?->tipoNota,
            $job->is_advance ? 'Sim' : 'Não',
            $job->protest?->nota,
            $job->medProtest?->med_id,
            $job->protest?->codecodf,
            $job->protest?->txtGrpCodificacao,
            $job->protest?->cidade,
            $job->owner?->name,
            $job->owner?->company?->name,
            $this->formatDate($opened),
            $this->formatDate($desired),
            $this->formatDate($job->sent_at),
            $this->resolveStatusLabel($job->status),
            $this->formatDate($job->sla_due_at),
            $this->formatDate($finishedAt),
            $this->resolveSlaCompliance($finishedAt, $job->sla_due_at),
            $this->resolveDeadlineCompliance($finishedAt, $desired),
            $job->medProtest?->statusSist,
            $this->formatDate($medFinishedAt),
            $this->resolveOuPrDeadlineResult($job->protest?->tipoNota, $medFinishedAt, $desired),
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);
            },
        ];
    }

    protected function resolveOpenedDate($job)
    {
        if ($job->protest?->tipoNota === 'NA') {
            return $job->protest?->dtAberturaNota;
        }

        return $job->medProtest?->dtCriacaoMedida;
    }

    protected function resolveDesiredDate($job)
    {
        if ($job->protest?->tipoNota === 'NA') {
            return $job->protest?->dtConclusaoDesej;
        }

        return $job->medProtest?->dtFimMedidaDesej;
    }

    protected function formatDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    protected function resolveStatusLabel($status): ?string
    {
        if ($status instanceof ProtestJobStatus) {
            return $status->label();
        }

        if (!$status) {
            return null;
        }

        return ProtestJobStatus::tryFrom((string) $status)?->label() ?? (string) $status;
    }

    protected function resolveSlaCompliance($finishedAt, $slaDueAt): string
    {
        if (!$slaDueAt) {
            return 'SEM SLA';
        }

        $reference = $this->resolveReferenceDate($finishedAt);
        $due = Carbon::parse($slaDueAt);

        return $reference->gt($due) ? 'ESTOURADO SLA' : 'EM PRAZO';
    }

    protected function resolveDeadlineCompliance($finishedAt, $desiredAt): string
    {
        if (!$desiredAt) {
            return 'SEM PRAZO';
        }

        $reference = $this->resolveReferenceDate($finishedAt);
        $desired = Carbon::parse($desiredAt);

        if ($reference->lte($desired)) {
            return 'NO PRAZO';
        }

        return $finishedAt ? 'FORA DO PRAZO' : 'EXPIRADO PRAZO';
    }

    protected function resolveReferenceDate($finishedAt): Carbon
    {
        if ($finishedAt) {
            return Carbon::parse($finishedAt);
        }

        return $this->reportGeneratedAt->copy();
    }

    protected function resolveOuPrDeadlineResult(?string $tipoNota, $medFinishedAt, $desiredAt): ?string
    {
        if (!in_array($tipoNota, ['OU', 'PR'], true)) {
            return null;
        }

        if (!$desiredAt) {
            return null;
        }

        $desired = Carbon::parse($desiredAt);

        if ($medFinishedAt) {
            $finished = Carbon::parse($medFinishedAt);
            return $finished->lte($desired) ? 'NO PRAZO' : 'FORA DO PRAZO';
        }

        return $this->reportGeneratedAt->lte($desired) ? 'EM PRAZO' : 'FORA DO PRAZO';
    }
}
