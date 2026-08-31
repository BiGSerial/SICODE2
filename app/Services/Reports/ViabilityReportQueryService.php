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
        $query = Viability::query()
            ->select([
                'id',
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
                'User.Employee.Contract.Company:id,name',
                'Engineer:id,name',
                'Company:id,name',
                'Note:id,note,material',
                'Note.Orders:id,note_id,ordem,statusSist',
                'Orders:id,note_id,ordem,statusSist',
            ]);

        $column = $this->dateColumn($params['column'] ?? null);
        $dtInit = $params['dt_init'] ?? null;
        $dtEnd = $params['dt_end'] ?? null;

        if ($column && ($dtInit || $dtEnd)) {
            if ($dtInit) {
                $query->whereDate($column, '>=', $dtInit);
            }

            if ($dtEnd) {
                $query->whereDate($column, '<=', $dtEnd);
            }
        }

        $searchTerms = $this->searchTerms($params['search'] ?? '', $params['multi_search_terms'] ?? []);

        if (!empty($searchTerms)) {
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $like = '%' . $term . '%';
                    $q->orWhereHas('Note', function ($note) use ($like) {
                        $note->where('note', 'like', $like)
                            ->orWhere('material', 'like', $like);
                    })->orWhereHas('Orders', function ($order) use ($like) {
                        $order->where('ordem', 'like', $like);
                    });
                }
            });
        }

        return $query
            ->orderBy($column ?: 'sended_at')
            ->orderBy('id');
    }

    public function normalizeParams(array $params): array
    {
        return [
            'search' => trim((string) ($params['search'] ?? '')),
            'multi_search_terms' => $this->searchTerms('', $params['multi_search_terms'] ?? []),
            'column' => $this->dateColumn($params['column'] ?? null),
            'dt_init' => $params['dt_init'] ?? null,
            'dt_end' => $params['dt_end'] ?? null,
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
