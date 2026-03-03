<?php

namespace App\Exports\Protests;

use App\Enum\ProtestJobStatus;
use App\Enum\ProtestType;
use App\Models\ProtestJob;
use App\Models\User;
use App\Traits\WildcardFormmater;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClosedProtestJobsExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
{
    use Exportable;
    use WildcardFormmater;

    public function __construct(
        protected array $filters
    ) {
    }

    public function query(): Builder
    {
        $query = ProtestJob::query()
            ->whereIn('status', [
                ProtestJobStatus::DONE->value,
                ProtestJobStatus::CANCELED->value,
            ])
            ->where(function (Builder $q) {
                $q->where('status', ProtestJobStatus::CANCELED->value)
                    ->orWhere(function (Builder $done) {
                        $done->where('status', ProtestJobStatus::DONE->value)
                            ->where('confirmed', true);
                    });
            })
            ->with([
                'protest:id,nota,tipoNota,cidade,statUsuar,txtGrpCodificacao,dtAberturaNota,dtConclusaoDesej,type',
                'protest.medProtests:id,protest_id,statusSist,protest_type',
                'medProtest:id,protest_id,med_id,statusSist,txtCodMedida,dtCriacaoMedida,dtFimMedidaDesej,dtFimMedida,protest_type',
                'creator:id,name',
                'owner:id,name,company_id',
                'owner.company:id,name',
                'closer:id,name',
            ]);

        $query->when(!empty($this->filters['userViewer']), function (Builder $q) {
            $user = User::find($this->filters['userViewer']);
            if (!$user) {
                $q->whereRaw('1 = 0');
                return;
            }

            $ownerIds = $user->descendantsQuery(true, true)
                ->pluck('users.id')
                ->toArray();

            if (empty($ownerIds)) {
                $q->whereRaw('1 = 0');
                return;
            }

            $q->whereIn('owner_id', $ownerIds);
        });

        $query->when(!empty($this->filters['typeNote']), function (Builder $q) {
            $q->whereHas('protest', function (Builder $sub) {
                $sub->where('tipoNota', $this->filters['typeNote']);
            });
        });

        $inPrazo = $this->filters['inPrazo'] ?? '';
        $query->when($inPrazo !== '', function (Builder $q) use ($inPrazo) {
            $inPrazo = (int) $inPrazo;
            if ($inPrazo === 1) {
                $q->whereNotNull('finished_at')
                    ->whereNotNull('sla_due_at')
                    ->whereColumn('finished_at', '>', 'sla_due_at');
            } elseif ($inPrazo === 2) {
                $q->whereNotNull('finished_at')
                    ->whereNotNull('sla_due_at')
                    ->whereColumn('finished_at', '<=', 'sla_due_at');
            }
        });

        $query->when(!empty($this->filters['search']), function (Builder $q) {
            $formatted = $this->formatWithWildcard($this->filters['search']);

            $q->whereHas('protest', function (Builder $sub) use ($formatted) {
                $sub->where('nota', $formatted->type, $formatted->search);
            });
        });

        $protestTypeFilter = $this->filters['protestTypeFilter'] ?? 'without_btzero';
        if ($protestTypeFilter === 'only_btzero') {
            $query->whereHas('protest.medProtests', function (Builder $q) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', ProtestType::BTZERO->value);
            });
        } elseif ($protestTypeFilter === 'without_btzero') {
            $query->whereDoesntHave('protest.medProtests', function (Builder $q) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', ProtestType::BTZERO->value);
            });
        }

        return $query
            ->orderByDesc('finished_at')
            ->orderByDesc('sent_at');
    }

    public function map($job): array
    {
        $protest = $job->protest;
        $med     = $job->medProtest;

        $openedBase = null;
        $desiredBase = null;

        if ($protest?->tipoNota === 'NA') {
            $openedBase = $protest?->dtAberturaNota;
            $desiredBase = $protest?->dtConclusaoDesej;
        } else {
            $openedBase = $med?->dtCriacaoMedida;
            $desiredBase = $med?->dtFimMedidaDesej;
        }

        return [
            $job->id,
            $protest?->nota,
            $protest?->tipoNota,
            $med?->protest_type_label,
            $protest?->statUsuar,
            $protest?->type,
            $protest?->txtGrpCodificacao,
            $protest?->cidade,
            $med?->med_id,
            $med?->txtCodMedida,
            $job->creator?->name,
            $job->owner?->name,
            $job->owner?->company?->name,
            $job->closer?->name,
            $job->priority?->value ?? $job->priority,
            $job->is_advance ? 'Sim' : 'Não',
            $this->formatDate($protest?->dtAberturaNota),
            $this->formatDate($protest?->dtConclusaoDesej),
            $this->formatDate($med?->dtCriacaoMedida),
            $this->formatDate($med?->dtFimMedidaDesej),
            $this->formatDate($med?->dtFimMedida),
            $this->formatDate($openedBase),
            $this->formatDate($desiredBase),
            $this->formatDate($job->sent_at),
            $this->formatDate($job->started_at),
            $this->formatDate($job->finished_at),
            $this->formatDate($job->closed_at),
            $this->formatDate($job->sla_due_at),
            $this->formatDate($job->sla_breached_at),
        ];
    }

    public function headings(): array
    {
        return [
            'Job ID',
            'Nota',
            'Tipo Nota',
            'Tipo Protesto',
            'Status Nota',
            'Categoria',
            'Tipo Reclamação',
            'Município',
            'Medida ID',
            'Medida Cod',
            'Despachante',
            'Responsável',
            'Empresa',
            'Finalizado por',
            'Prioridade',
            'Avança parceiro',
            'Nota aberta em',
            'Nota conclusão desejada',
            'Medida criada em',
            'Medida conclusão desejada',
            'Medida finalizada em',
            'Abertura base (SLA)',
            'Fim desejado base (SLA)',
            'Job enviado em',
            'Job iniciado em',
            'Job finalizado em',
            'Job encerrado em',
            'SLA previsto',
            'SLA estourado em',
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
