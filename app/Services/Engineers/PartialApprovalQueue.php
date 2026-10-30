<?php

namespace App\Services\Engineers;

use App\Models\{Partial, User};
use App\Services\Partner\WorkReportBudgetGuard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Fila "Informes parciais aguardando aprovação" da Engenharia: consulta, indicadores do topo e situação do saldo de PEP por parcial.
 * A tela e os cartões leem daqui, para o recorte ser sempre o mesmo.
 *
 * Regras: aguardando = `allow = 0 e deny = 0`; engenheiro com empresas responsáveis vê as delas, superadm vê tudo, os demais veem a própria
 * empresa. Idade = dias corridos desde o envio. O saldo do PEP é lido no servidor (só tela interna da Engenharia).
 */
class PartialApprovalQueue
{
    /** Limite de parciais avaliadas para o indicador de saldo (a fila costuma ser pequena). */
    public const PEP_SCAN_LIMIT = 200;

    public function __construct(private readonly WorkReportBudgetGuard $guard)
    {
    }

    /**
     * @param array{search?: ?string, from?: ?string, to?: ?string, rubrica?: ?string} $filters
     */
    public function query(User $user, array $filters = []): Builder
    {
        $query = Partial::query()->where('allow', false)->where('deny', false);

        if (!$user->superadm) {
            if ($user->Companies->isNotEmpty() && $user->engineer) {
                $query->whereIn('company_id', $user->Companies->pluck('id')->all());
            } else {
                $query->where('company_id', $user->Company?->id);
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            // Agrupado: o OR não pode escapar da restrição de empresa acima.
            $query->where(function (Builder $q) use ($search) {
                $q->whereRelation('Note', 'note', $search)
                    ->orWhereRelation('Note.Orders', 'ordem', $search);
            });
        }

        if (!empty($filters['rubrica'])) {
            $query->whereRelation('Note', 'rubrica', $filters['rubrica']);
        }

        $from = $filters['from'] ?? null;
        $to   = $filters['to'] ?? null;

        if ($from && $to) {
            $query->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);
        } elseif ($from) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        } elseif ($to) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $query;
    }

    /**
     * Indicadores do topo (todo o recorte, não só a página).
     *
     * @return array<string, mixed>
     */
    public function summary(Builder $query, ?Carbon $now = null): array
    {
        $now ??= now();

        $rows = (clone $query)->get(['id', 'company_id', 'value', 'created_at']);
        $ages = $rows->map(fn (Partial $p) => (int) $p->created_at->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));

        $series = [];

        for ($i = 13; $i >= 0; $i--) {
            $day      = $now->copy()->subDays($i)->startOfDay();
            $series[] = [
                'label' => $day->format('d/m'),
                'sent'  => $rows->filter(fn (Partial $p) => $p->created_at->isSameDay($day))->count(),
            ];
        }

        return [
            'waiting'    => $rows->count(),
            'value'      => (float) $rows->sum('value'),
            'companies'  => $rows->pluck('company_id')->unique()->count(),
            'oldest'     => $ages->isEmpty() ? null : (int) $ages->max(),
            'average'    => $ages->isEmpty() ? null : round((float) $ages->avg(), 1),
            'today'      => $rows->filter(fn (Partial $p) => $p->created_at->isSameDay($now))->count(),
            'labels'     => array_column($series, 'label'),
            'sent_daily' => array_column($series, 'sent'),
            'age_values' => $this->ageValues($ages),
            'age_labels' => ['0-2 d', '3-7 d', '8-15 d', '16+ d'],
        ];
    }

    /**
     * Situação do saldo de PEP de cada parcial: `ok` (cobre), `short` (sem saldo ou ADS acima) ou `none` (sem saldo sincronizado).
     *
     * @param Collection<int, Partial> $partials com Orders carregadas
     *
     * @return array<int, array{state: string, available: ?float, reason: ?string}>
     */
    public function pepStates(Collection $partials): array
    {
        $states = [];

        foreach ($partials as $partial) {
            $orders   = $partial->Orders;
            $balances = $this->guard->balancesFor($orders);
            $short    = $this->guard->shortfall($orders, (float) $partial->value);

            $states[$partial->id] = match (true) {
                $short !== null  => ['state' => 'short', 'available' => array_sum($balances), 'reason' => $short['reason']],
                $balances !== [] => ['state' => 'ok', 'available' => array_sum($balances), 'reason' => null],
                default          => ['state' => 'none', 'available' => null, 'reason' => null],
            };
        }

        return $states;
    }

    /** Parciais da fila com saldo insuficiente (indicador do topo). */
    public function shortCount(Builder $query): int
    {
        $partials = (clone $query)->with('Orders')->limit(self::PEP_SCAN_LIMIT)->get();

        return collect($this->pepStates($partials))->where('state', 'short')->count();
    }

    /** @param Collection<int, int> $ages */
    private function ageValues(Collection $ages): array
    {
        return array_map(
            fn (array $r) => $ages->filter(fn (int $d) => $d >= $r[0] && $d <= $r[1])->count(),
            [[0, 2], [3, 7], [8, 15], [16, PHP_INT_MAX]]
        );
    }
}
