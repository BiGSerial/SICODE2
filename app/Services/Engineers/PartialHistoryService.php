<?php

namespace App\Services\Engineers;

use App\Models\{Company, Partial, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PartialHistoryService
{
    public const STATUS_REJECTED    = 'rejected';
    public const STATUS_PAID        = 'paid';
    public const STATUS_PAYMENT     = 'payment';
    public const STATUS_SUPERVISION = 'supervision';
    public const STATUS_EVALUATION  = 'evaluation';

    /**
     * @param array<string,mixed> $filters
     */
    public function query(array $filters, ?User $user = null): Builder
    {
        $user ??= auth()->user();
        $terms = $this->normalizeSearchTerms($filters);

        return Partial::query()
            ->with(['Note.Orders', 'Orders', 'Company', 'engineer', 'supervisor', 'payer', 'user'])
            ->where(function (Builder $q) {
                $q->where('allow', true)
                    ->orWhere('deny', true);
            })
            ->when($user && !$user->superadm, function (Builder $query) use ($user) {
                $companyIds = $this->visibleCompanyIds($user);

                if ($companyIds->isNotEmpty()) {
                    $query->whereIn('company_id', $companyIds->all());
                } elseif ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when(!empty($filters['company_id']), function (Builder $query) use ($filters) {
                $query->where('company_id', $filters['company_id']);
            })
            ->when(!empty($filters['rubrica']), function (Builder $query) use ($filters) {
                $query->whereRelation('Note', 'rubrica', $filters['rubrica']);
            })
            ->when(!empty($filters['status']), function (Builder $query) use ($filters) {
                $this->applyStatus($query, (string) $filters['status']);
            })
            ->when(!empty($filters['dt_in']) && empty($filters['dt_out']), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['dt_in']);
            })
            ->when(!empty($filters['dt_out']) && empty($filters['dt_in']), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['dt_out']);
            })
            ->when(!empty($filters['dt_in']) && !empty($filters['dt_out']), function (Builder $query) use ($filters) {
                $query->whereBetween('created_at', [$filters['dt_in'], $filters['dt_out']]);
            })
            ->when($terms->isNotEmpty(), function (Builder $query) use ($terms) {
                $query->where(function (Builder $outer) use ($terms) {
                    foreach ($terms as $term) {
                        $like = '%' . $term . '%';
                        $outer->orWhereRelation('Note', 'note', 'like', $like)
                            ->orWhereRelation('Note', 'numPedido', 'like', $like)
                            ->orWhereRelation('Note.Orders', 'ordem', 'like', $like)
                            ->orWhereRelation('Orders', 'ordem', 'like', $like);
                    }
                });
            })
            // Do mais recente para o mais antigo, pela data de envio (a que a coluna "Enviada em" mostra).
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Indicadores do topo do histórico (todo o recorte filtrado, não só a página).
     *
     * @return array<string, mixed>
     */
    public function summary(Builder $query): array
    {
        $base = fn () => (clone $query)->reorder()->toBase();

        $agg = $base()->selectRaw(
            'COUNT(*) as total, COALESCE(SUM(value), 0) as value, '
            . 'COALESCE(SUM(deny = 1), 0) as rejected, '
            . 'COALESCE(SUM(allow = 1 AND deny = 0 AND supervision = 0), 0) as supervision, '
            . 'COALESCE(SUM(allow = 1 AND deny = 0 AND supervision = 1 AND payment = 0), 0) as payment, '
            . 'COALESCE(SUM(allow = 1 AND deny = 0 AND payment = 1), 0) as paid, '
            . 'COUNT(DISTINCT company_id) as companies, '
            . 'AVG(CASE WHEN decision_at IS NOT NULL AND allow = 1 THEN TIMESTAMPDIFF(HOUR, created_at, decision_at) END) as approval_hours'
        )->first();

        $months = $base()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as n, COALESCE(SUM(deny = 1), 0) as rejected, COALESCE(SUM(value), 0) as value")
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $byCompany = $base()
            ->selectRaw('company_id, COUNT(*) as total, COALESCE(SUM(value), 0) as value, COALESCE(SUM(deny = 1), 0) as rejected')
            ->groupBy('company_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $names = Company::withTrashed()->whereIn('id', $byCompany->pluck('company_id'))->pluck('name', 'id');

        $total = (int) $agg->total;

        return [
            'total'          => $total,
            'value'          => (float) $agg->value,
            'average_value'  => $total > 0 ? (float) $agg->value / $total : 0.0,
            'companies'      => (int) $agg->companies,
            'rejected'       => (int) $agg->rejected,
            'rejected_rate'  => $total > 0 ? round((int) $agg->rejected / $total * 100, 1) : 0.0,
            'supervision'    => (int) $agg->supervision,
            'payment'        => (int) $agg->payment,
            'paid'           => (int) $agg->paid,
            'in_progress'    => (int) $agg->supervision + (int) $agg->payment,
            'approval_days'  => $agg->approval_hours === null ? null : round((float) $agg->approval_hours / 24, 1),
            'month_labels'   => $months->map(fn ($m) => substr($m->ym, 5, 2) . '/' . substr($m->ym, 2, 2))->all(),
            'month_totals'   => $months->pluck('n')->map(fn ($n) => (int) $n)->all(),
            'month_rejected' => $months->pluck('rejected')->map(fn ($n) => (int) $n)->all(),
            'month_values'   => $months->pluck('value')->map(fn ($n) => (float) $n)->all(),
            'by_company'     => $byCompany->map(fn ($r) => [
                'label'    => $names[$r->company_id] ?? 'Desconhecida',
                'total'    => (int) $r->total,
                'value'    => (float) $r->value,
                'rejected' => (int) $r->rejected,
            ])->all(),
        ];
    }

    public function statusLabel(Partial $partial): string
    {
        if ($partial->deny) {
            return 'REJEITADO';
        }

        if ($partial->payment && $partial->allow) {
            return 'PAGO';
        }

        if ($partial->supervision && !$partial->payment) {
            return 'EM MEDIÇÃO';
        }

        if ($partial->allow && !$partial->supervision) {
            return 'EM FISCALIZAÇÃO';
        }

        return 'AVALIAÇÃO';
    }

    /**
     * @return Collection<int, Company>
     */
    public function companyOptions(?User $user = null): Collection
    {
        $user ??= auth()->user();

        $query = Company::query()->orderBy('name');

        if ($user && !$user->superadm) {
            $companyIds = $this->visibleCompanyIds($user);

            if ($companyIds->isNotEmpty()) {
                $query->whereIn('id', $companyIds->all());
            } elseif ($user->company_id) {
                $query->where('id', $user->company_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->get(['id', 'name']);
    }

    /**
     * @param array<string,mixed> $filters
     * @return Collection<int,string>
     */
    private function normalizeSearchTerms(array $filters): Collection
    {
        $raw = [];

        foreach (['search', 'bulk_search'] as $key) {
            if (!empty($filters[$key])) {
                $raw[] = (string) $filters[$key];
            }
        }

        return collect($raw)
            ->flatMap(fn (string $value) => preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();
    }

    private function applyStatus(Builder $query, string $status): void
    {
        match ($status) {
            self::STATUS_REJECTED    => $query->where('deny', true),
            self::STATUS_PAID        => $query->where('payment', true)->where('allow', true),
            self::STATUS_PAYMENT     => $query->where('supervision', true)->where('payment', false),
            self::STATUS_SUPERVISION => $query->where('allow', true)->where('supervision', false),
            self::STATUS_EVALUATION  => $query->where('allow', false)->where('deny', false),
            default                  => null,
        };
    }

    /**
     * @return Collection<int,string>
     */
    private function visibleCompanyIds(User $user): Collection
    {
        if ($user->Companies->isNotEmpty() && $user->engineer) {
            return $user->Companies->pluck('id')->filter()->values();
        }

        return collect([$user->company_id])->filter()->values();
    }
}
