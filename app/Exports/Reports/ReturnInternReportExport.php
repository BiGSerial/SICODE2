<?php

namespace App\Exports\Reports;

use App\Custom\Notestatus;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReturnInternReportExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
{
    use Exportable;

    public function __construct(
        protected array $filters
    ) {
    }

    public function query(): Builder
    {
        $start = $this->filters['dt_in']
            ? Carbon::parse($this->filters['dt_in'])->startOfDay()
            : now()->startOfMonth();
        $end = $this->filters['dt_out']
            ? Carbon::parse($this->filters['dt_out'])->endOfDay()
            : now()->endOfDay();

        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        $viabilitySub = DB::table('reclaim_viability')->select('reclaim_id')->distinct();
        $waitingSub = DB::table('hiring_waitings')->select('reclaim_id')->whereNotNull('reclaim_id')->distinct();
        $approvalSub = DB::table('viability_approval_reclaim')->select('reclaim_id')->distinct();
        $externalSub = DB::table('external_reclaim')->select('reclaim_id')->distinct();

        $firstCommentSub = DB::table('comment_reclaim as cr')
            ->join('comments as c', 'c.id', '=', 'cr.comment_id')
            ->selectRaw('cr.reclaim_id, c.id as comment_id')
            ->whereRaw('c.id = (SELECT c2.id FROM comment_reclaim cr2 JOIN comments c2 ON c2.id = cr2.comment_id WHERE cr2.reclaim_id = cr.reclaim_id ORDER BY c2.created_at ASC, c2.id ASC LIMIT 1)');

        $query = DB::table('reclaims as r')
            ->leftJoin('notes as n', 'n.id', '=', 'r.note_id')
            ->leftJoin('services as s', 's.uuid', '=', 'r.service_id')
            ->leftJoin('productions as p', 'p.id', '=', 'r.production_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'p.user_id')
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->leftJoinSub($viabilitySub, 'rv', 'rv.reclaim_id', '=', 'r.id')
            ->leftJoinSub($waitingSub, 'hw', 'hw.reclaim_id', '=', 'r.id')
            ->leftJoinSub($approvalSub, 'var', 'var.reclaim_id', '=', 'r.id')
            ->leftJoinSub($externalSub, 'er', 'er.reclaim_id', '=', 'r.id')
            ->leftJoinSub($firstCommentSub, 'fc', 'fc.reclaim_id', '=', 'r.id')
            ->leftJoin('comments as c', 'c.id', '=', 'fc.comment_id')
            ->leftJoin('users as du', 'du.id', '=', 'c.user_id')
            ->whereBetween('r.created_at', [$start, $end])
            ->select([
                'r.id as reclaim_id',
                'r.created_at as reclaim_created_at',
                'r.completed_at as reclaim_completed_at',
                'r.category as reclaim_category',
                'n.note as note_number',
                's.service as service_name',
                'c.message as first_comment_message',
                'du.name as dispatcher_name',
                'p.att_at as production_att_at',
                'p.completed_at as production_completed_at',
                'p.status as production_status',
                'pu.name as production_user_name',
                'co.name as production_company_name',
            ])
            ->selectRaw("
                CASE
                    WHEN rv.reclaim_id IS NOT NULL THEN 'Viabilidade'
                    WHEN hw.reclaim_id IS NOT NULL THEN 'Contratacao'
                    WHEN var.reclaim_id IS NOT NULL THEN 'Aprovacao'
                    WHEN er.reclaim_id IS NOT NULL THEN 'Orgao Externo'
                    ELSE 'Sem Origem'
                END as origin_label
            ");

        if (!empty($this->filters['search'])) {
            $search = trim((string) $this->filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('n.note', 'like', '%' . $search . '%')
                    ->orWhere('r.category', 'like', '%' . $search . '%');
            });
        }

        if (!empty($this->filters['serviceIds'])) {
            $query->whereIn('r.service_id', $this->filters['serviceIds']);
        }

        if (!empty($this->filters['category'])) {
            $query->where('r.category', 'like', '%' . trim((string) $this->filters['category']) . '%');
        }

        if (!empty($this->filters['dispatcherUserId'])) {
            $query->where('c.user_id', $this->filters['dispatcherUserId']);
        }

        if (!empty($this->filters['productionUserId'])) {
            $query->where('p.user_id', $this->filters['productionUserId']);
        }

        if (!empty($this->filters['companyId'])) {
            $query->where('p.company_id', $this->filters['companyId']);
        }

        if ($this->filters['productionStatus'] !== '') {
            $query->where('p.status', $this->filters['productionStatus']);
        }

        if ($this->filters['completedFilter'] === 'open') {
            $query->where('r.completed', false);
        }
        if ($this->filters['completedFilter'] === 'closed') {
            $query->where('r.completed', true);
        }

        if (!empty($this->filters['originFilters'])) {
            $origins = $this->filters['originFilters'];
            $query->where(function ($q) use ($origins) {
                if (in_array('viability', $origins, true)) {
                    $q->orWhereNotNull('rv.reclaim_id');
                }
                if (in_array('waiting', $origins, true)) {
                    $q->orWhereNotNull('hw.reclaim_id');
                }
                if (in_array('approval', $origins, true)) {
                    $q->orWhereNotNull('var.reclaim_id');
                }
                if (in_array('external', $origins, true)) {
                    $q->orWhereNotNull('er.reclaim_id');
                }
                if (in_array('unknown', $origins, true)) {
                    $q->orWhere(function ($sub) {
                        $sub->whereNull('rv.reclaim_id')
                            ->whereNull('hw.reclaim_id')
                            ->whereNull('var.reclaim_id')
                            ->whereNull('er.reclaim_id');
                    });
                }
            });
        }

        if ($this->filters['resolutionMin'] !== '' || $this->filters['resolutionMax'] !== '') {
            $query->whereNotNull('r.completed_at');

            if ($this->filters['resolutionMin'] !== '') {
                $query->whereRaw(
                    'TIMESTAMPDIFF(DAY, r.created_at, r.completed_at) >= ?',
                    [(int) $this->filters['resolutionMin']]
                );
            }

            if ($this->filters['resolutionMax'] !== '') {
                $query->whereRaw(
                    'TIMESTAMPDIFF(DAY, r.created_at, r.completed_at) <= ?',
                    [(int) $this->filters['resolutionMax']]
                );
            }
        }

        return $query->orderByDesc('r.created_at');
    }

    public function map($row): array
    {
        $statusLabel = $row->production_status !== null
            ? (Notestatus::status((int) $row->production_status)->status ?? (string) $row->production_status)
            : 'Aguardando atribuicao';

        return [
            $row->note_number,
            $row->origin_label,
            $row->service_name,
            $row->dispatcher_name,
            $row->reclaim_category,
            $row->first_comment_message,
            $this->formatDate($row->reclaim_created_at),
            $this->formatDate($row->production_att_at),
            $this->formatDate($row->reclaim_completed_at),
            $row->production_user_name,
            $row->production_company_name,
            $statusLabel,
        ];
    }

    public function headings(): array
    {
        return [
            'Numero da nota',
            'Origem',
            'Servico',
            'Quem despachou',
            'Categoria',
            'Descricao',
            'Criado em',
            'Producao att em',
            'Retorno concluido em',
            'Usuario producao',
            'Empresa producao',
            'Status producao',
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
