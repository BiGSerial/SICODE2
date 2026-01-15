<?php

namespace App\Exports\Oexterno;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExternalReclaimsExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
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
            : now()->startOfYear();
        $end = $this->filters['dt_out']
            ? Carbon::parse($this->filters['dt_out'])->endOfDay()
            : now()->endOfDay();

        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        return DB::table('external_reclaim as er')
            ->join('reclaims as r', 'r.id', '=', 'er.reclaim_id')
            ->join('externals as ex', 'ex.id', '=', 'er.external_id')
            ->leftJoin('entities as en', 'en.id', '=', 'ex.entity_id')
            ->leftJoin('entity_types as et', 'et.id', '=', 'en.entity_type_id')
            ->leftJoin('notes as nt', 'nt.id', '=', 'ex.note_id')
            ->leftJoin('subcategories as sc', 'sc.id', '=', 'r.subcategory_id')
            ->leftJoin('categories as ca', 'ca.id', '=', 'sc.category_id')
            ->leftJoin('productions as p', 'p.id', '=', 'r.production_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('companies as co', 'co.id', '=', 'p.company_id')
            ->whereBetween('r.created_at', [$start, $end])
            ->when(!empty($this->filters['status']), fn ($q) => $q->whereIn('ex.status', $this->filters['status']))
            ->when(!empty($this->filters['entityTypeIds']), fn ($q) => $q->whereIn('en.entity_type_id', $this->filters['entityTypeIds']))
            ->when(!empty($this->filters['entityIds']), fn ($q) => $q->whereIn('ex.entity_id', $this->filters['entityIds']))
            ->when(!empty($this->filters['rubrics']), fn ($q) => $q->whereIn('nt.rubrica', $this->filters['rubrics']))
            ->select([
                'r.id as reclaim_id',
                'r.created_at as reclaim_created_at',
                'r.completed as reclaim_completed',
                'r.completed_at as reclaim_completed_at',
                'r.category as reclaim_category',
                'sc.name as subcategory',
                'ca.name as category',
                'ex.id as external_id',
                'ex.status as external_status',
                'ex.created_at as external_created_at',
                'en.id as entity_id',
                'en.name as entity_name',
                'en.nick as entity_nick',
                'et.name as entity_type',
                'nt.note as note',
                'nt.rubrica as rubrica',
                'er.completed as external_reclaim_completed',
                'er.completed_at as external_reclaim_completed_at',
                'u.name as production_user_name',
                'co.name as production_company_name',
                'p.att_at as production_att_at',
                'p.completed_at as production_completed_at',
            ])
            ->orderByDesc('r.created_at');
    }

    public function map($row): array
    {
        return [
            $row->reclaim_id,
            $this->formatDate($row->reclaim_created_at),
            $row->reclaim_completed ? 'Sim' : 'Nao',
            $this->formatDate($row->reclaim_completed_at),
            $row->reclaim_category,
            $row->subcategory,
            $row->category,
            $row->external_id,
            $row->external_status,
            $this->formatDate($row->external_created_at),
            $row->entity_id,
            $row->entity_name,
            $row->entity_nick,
            $row->entity_type,
            $row->note,
            $row->rubrica,
            $row->external_reclaim_completed ? 'Sim' : 'Nao',
            $this->formatDate($row->external_reclaim_completed_at),
            $row->production_user_name,
            $row->production_company_name,
            $this->formatDate($row->production_att_at),
            $this->formatDate($row->production_completed_at),
        ];
    }

    public function headings(): array
    {
        return [
            'Reclaim ID',
            'Reclaim criado em',
            'Reclaim concluido',
            'Reclaim concluido em',
            'Reclaim categoria',
            'Reclaim subcategoria',
            'Categoria',
            'External ID',
            'External status',
            'External criado em',
            'Entidade ID',
            'Entidade nome',
            'Entidade apelido',
            'Entidade tipo',
            'Nota',
            'Rubrica',
            'External reclaim concluido',
            'External reclaim concluido em',
            'Producao usuario',
            'Producao empresa',
            'Producao att em',
            'Producao concluida em',
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
