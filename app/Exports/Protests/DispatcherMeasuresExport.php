<?php

namespace App\Exports\Protests;

use App\Models\MedProtest;
use App\Models\ProtestJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DispatcherMeasuresExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
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
        $userId = $this->filters['userId'] ?? null;
        $types = $this->filters['protestTypes'] ?? [];

        $firstJobs = ProtestJob::selectRaw('med_protest_id, MIN(id) as job_id')
            ->whereNotNull('created_by')
            ->groupBy('med_protest_id');

        $query = MedProtest::query()
            ->leftJoinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
            ->leftJoin('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id')
            ->leftJoin('users as dispatcher', 'dispatcher.id', '=', 'first_job.created_by')
            ->leftJoin('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->where('protests.tipoNota', 'NA')
                        ->whereBetween('protests.dtConclusaoDesej', [$start, $end]);
                })
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->where(function ($tipo) {
                        $tipo->where('protests.tipoNota', '!=', 'NA')
                            ->orWhereNull('protests.tipoNota');
                    })
                    ->whereBetween('med_protests.dtFimMedidaDesej', [$start, $end]);
                });
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('med_protests as mp2')
                    ->whereColumn('mp2.protest_id', 'med_protests.protest_id')
                    ->where('mp2.statusSist', 'MEDA');
            })
            ->when(!empty($types), fn ($q) => $q->whereIn('med_protests.protest_type', $types))
            ->when($userId, fn ($q) => $q->where('first_job.created_by', $userId))
            ->select([
                'med_protests.id',
                'med_protests.med_id',
                'med_protests.statusSist',
                'med_protests.protest_id',
                'med_protests.protest_type',
                'med_protests.dtFimMedidaDesej',
                'med_protests.dtFimMedida',
                'med_protests.result',
                'protests.nota as protest_nota',
                'protests.tipoNota as protest_tipo_nota',
                'protests.dtConclusaoDesej as protest_dt_conclusao_desej',
                'protests.type',
                'protests.statUsuar as protest_stat_usuar',
                'first_job.id as job_id',
                'first_job.sent_at as job_sent_at',
                'first_job.created_by as dispatcher_id',
                'dispatcher.name as dispatcher_name',

            ])
            ->orderByDesc('med_protests.dtFimMedidaDesej');

        return $query;
    }

    public function map($row): array
    {
        $isOnTime = $this->isOnTime($row);
        $dueBase = $row->protest_tipo_nota === 'NA'
            ? $row->protest_dt_conclusao_desej
            : $row->dtFimMedidaDesej;
        $protestType = $row->protest_type;
        if ($protestType instanceof \App\Enum\ProtestType) {
            $protestType = $protestType->label();
        }

        $lastJobMedProtest = $row->ProtestJobs()?->where('status', 'done')->orderByDesc('id')->first();

        return [
            $row->med_id,
            $row->protest_nota,
            $row->protest_tipo_nota,
            $protestType,
            $row->protest?->type,
            $row->statusSist,
            $this->formatDate($row->dtFimMedidaDesej),
            $this->formatDate($row->dtFimMedida),
            $this->formatDate($row->protest_dt_conclusao_desej),
            $this->formatDate($dueBase),
            $isOnTime ? 'Sim' : 'Nao',
            $row->job_id,
            $row->dispatcher_name,
            $this->formatDate($row->job_sent_at),
            $lastJobMedProtest?->Owner?->name,
            $lastJobMedProtest?->Owner?->Company?->name,

        ];
    }

    public function headings(): array
    {
        return [
            'Medida ID',
            'Reclamacao (Nota)',
            'Tipo Nota',
            'Tipo Reclamacão',
            'Categoria Reclamação',
            'Status Medida',
            'Fim medida desejado',
            'Fim medida',
            'Conclusao desejada (Nota)',
            'SLA base',
            'Dentro do prazo',
            'Job ID',
            'Despachante',
            'Job enviado em',
            'responsavel_conclusao',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    protected function isOnTime($row): bool
    {
        if ($row->protest_tipo_nota === 'NA') {
            if (! $row->protest_dt_conclusao_desej || ! $row->dtFimMedida) {
                return false;
            }

            return Carbon::parse($row->dtFimMedida)
                ->lte(Carbon::parse($row->protest_dt_conclusao_desej));
        }

        if (! $row->dtFimMedidaDesej || ! $row->dtFimMedida) {
            return false;
        }

        return Carbon::parse($row->dtFimMedida)
            ->lte(Carbon::parse($row->dtFimMedidaDesej));
    }

    protected function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
