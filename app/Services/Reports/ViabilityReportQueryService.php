<?php

namespace App\Services\Reports;

use App\Models\Viability;
use Illuminate\Database\Eloquent\Builder;

class ViabilityReportQueryService
{
    private const DATE_COLUMNS = [
        'completed_at',
        'hired_at',
        'sended_at',
    ];

    public function query(array $params = []): Builder
    {
        return $this->baseQuery($params);
    }

    public function exportQuery(array $params = []): Builder
    {
        return $this->baseQuery($params);
    }

    private function baseQuery(array $params = []): Builder
    {
        $query = Viability::query()
            ->select([
                'id',
                'order_id',
                'note_id',
                'company_id',
                'user_id',
                'engineer_id',
                'hired',
                'tacit',
                'sended_at',
                'hired_at',
                'returned_at',
                'completed_at',
                'tacit_at',
                'status',
            ])
            ->with([
                'User:id,name,company_id',
                'User.Company:id,name',
                'User.Employee:id,user_id,contract_id',
                'User.Employee.Contract:id,company_id',
                'User.Employee.Contract.company:id,name',
                'Engineer:id,name',
                'Company:id,name',
                'Note:id,note,material',
                'Order:id,note_id,ordem,statusSist',
                'Orders:id,note_id,ordem,statusSist',
            ]);

        $column = $this->dateColumn($params['column'] ?? null);
        $dtInit = $params['dt_init'] ?? null;
        $dtEnd  = $params['dt_end'] ?? null;

        if ($column && ($dtInit || $dtEnd)) {
            if ($dtInit) {
                $query->whereDate($column, '>=', $dtInit);
            }

            if ($dtEnd) {
                $query->whereDate($column, '<=', $dtEnd);
            }
        }

        $multiSearchTerms = $this->searchTerms('', $params['multi_search_terms'] ?? []);
        $searchTerms      = !empty($multiSearchTerms)
            ? $multiSearchTerms
            : $this->searchTerms($params['search'] ?? '', []);

        if (!empty($searchTerms)) {
            $query->where(fn ($q) => !empty($multiSearchTerms)
                ? $this->applyExactSearch($q, $searchTerms)
                : $this->applyLikeSearch($q, $searchTerms));
        }

        return $query
            ->orderBy($column ?: 'sended_at')
            ->orderBy('id');
    }

    private function applyExactSearch($query, array $terms): void
    {
        $query->whereHas('Note', function ($note) use ($terms) {
            $note->whereIn('note', $terms)
                ->orWhereIn('material', $terms);
        })->orWhereHas('Orders', function ($order) use ($terms) {
            $order->whereIn('ordem', $terms);
        })->orWhereHas('Order', function ($order) use ($terms) {
            $order->whereIn('ordem', $terms);
        });
    }

    private function applyLikeSearch($query, array $terms): void
    {
        foreach ($terms as $term) {
            $like = '%' . $term . '%';

            $query->orWhereHas('Note', function ($note) use ($like) {
                $note->where('note', 'like', $like)
                    ->orWhere('material', 'like', $like);
            })->orWhereHas('Orders', function ($order) use ($like) {
                $order->where('ordem', 'like', $like);
            })->orWhereHas('Order', function ($order) use ($like) {
                $order->where('ordem', 'like', $like);
            });
        }
    }

    public function normalizeParams(array $params): array
    {
        return [
            'search'             => trim((string) ($params['search'] ?? '')),
            'multi_search_terms' => $this->searchTerms('', $params['multi_search_terms'] ?? []),
            'column'             => $this->dateColumn($params['column'] ?? null),
            'dt_init'            => $params['dt_init'] ?? null,
            'dt_end'             => $params['dt_end'] ?? null,
        ];
    }

    private function dateColumn(?string $column): ?string
    {
        return in_array($column, self::DATE_COLUMNS, true) ? $column : null;
    }

    private function searchTerms(string $search, array $multiSearchTerms = []): array
    {
        $inlineTerms = preg_split('/[\s,;\n\r\t]+/', $search) ?: [];

        return collect($inlineTerms)
            ->merge($multiSearchTerms)
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();
    }
}
