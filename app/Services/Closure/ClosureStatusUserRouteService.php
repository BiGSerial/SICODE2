<?php

namespace App\Services\Closure;

use App\Models\Closure\{ClosureOrderState, ClosureStatusUserRoute};
use App\Models\User;
use Illuminate\Support\Facades\{Cache, DB};
use InvalidArgumentException;

/**
 * Mapa "status usuário → serviço" que alimenta a aba "Em acompanhamento" das caixas de Medição e Fiscalização.
 * Um prefixo (2 primeiros tokens de orders.statusUser) pertence a UM serviço (índice único no banco); a caixa só mostra
 * as Ordens cujo status atual está mapeado para ela e a Ordem sai sozinha quando o status muda (consulta ao vivo).
 */
class ClosureStatusUserRouteService
{
    private const CATALOG_CACHE_KEY = 'closure:status-user-catalog:v1';

    /** @return array<string, string> prefixo => área */
    public function map(): array
    {
        return ClosureStatusUserRoute::query()->pluck('area', 'status_user_prefix')->all();
    }

    /** @return array<int, string> */
    public function prefixesFor(string $area): array
    {
        return ClosureStatusUserRoute::query()->where('area', $area)->orderBy('status_user_prefix')->pluck('status_user_prefix')->all();
    }

    /**
     * TODOS os status usuário existentes nas Ordens (prefixo de 2 tokens), com quantas Ordens estão em cada um (`total`),
     * quantas delas são do Encerramento ainda abertas (`count`) e a quem pertencem. A lista de status é cacheada por pouco tempo
     * (varre orders); o número do Encerramento e o dono são sempre atuais.
     *
     * @return array<int, array{prefix: string, count: int, total: int, area: ?string}>
     */
    public function catalog(): array
    {
        $map   = $this->map();
        $total = Cache::remember(self::CATALOG_CACHE_KEY, 900, function () {
            $rows = [];

            DB::table('orders')
                ->whereNotNull('statusUser')
                ->selectRaw("trim(substring_index(trim(statusUser), ' ', 2)) as pfx, count(*) as n")
                ->groupBy('pfx')
                ->get()
                ->each(function ($row) use (&$rows) {
                    if ($row->pfx !== '') {
                        $rows[mb_strtoupper((string) $row->pfx)] = ($rows[mb_strtoupper((string) $row->pfx)] ?? 0) + (int) $row->n;
                    }
                });

            return $rows;
        });

        $closure = [];

        ClosureOrderState::query()
            ->whereNotIn('operational_status', [ClosureOrderWorkflowService::STATUS_CLOSED, ClosureOrderWorkflowService::STATUS_CANCELLED])
            ->join('orders', 'orders.id', '=', 'closure_order_states.order_id')
            ->whereNotNull('orders.statusUser')
            ->selectRaw("trim(substring_index(trim(orders.statusUser), ' ', 2)) as pfx, count(*) as n")
            ->groupBy('pfx')
            ->toBase()
            ->get()
            ->each(function ($row) use (&$closure) {
                if ($row->pfx !== '') {
                    $closure[mb_strtoupper((string) $row->pfx)] = (int) $row->n;
                }
            });

        // Prefixos já mapeados continuam na lista mesmo sem Ordens agora (para poder desmarcá-los).
        foreach (array_keys($map) as $prefix) {
            $total[$prefix] ??= 0;
        }

        ksort($total);

        return collect($total)
            ->map(fn (int $n, string $prefix) => ['prefix' => $prefix, 'count' => $closure[$prefix] ?? 0, 'total' => $n, 'area' => $map[$prefix] ?? null])
            ->values()
            ->all();
    }

    /**
     * Define EXATAMENTE quais prefixos pertencem à área. Recusa prefixo que já pertence a outra área.
     *
     * @param array<int, string> $prefixes
     *
     * @return array{added: int, removed: int}
     */
    public function sync(string $area, array $prefixes, User $actor): array
    {
        if (!ClosureArea::hasInbox($area)) {
            throw new InvalidArgumentException('Este serviço não tem caixa de acompanhamento.');
        }

        $wanted = collect($prefixes)
            ->map(fn ($prefix) => mb_strtoupper(ClosureEligibleOrders::statusUserPrefix((string) $prefix)))
            ->filter()
            ->unique()
            ->values();

        return DB::transaction(function () use ($area, $wanted, $actor) {
            $current = ClosureStatusUserRoute::query()->lockForUpdate()->get()->keyBy('status_user_prefix');

            foreach ($wanted as $prefix) {
                $owner = $current[$prefix]->area ?? null;

                if ($owner !== null && $owner !== $area) {
                    throw new InvalidArgumentException("O status \"{$prefix}\" já pertence a " . ClosureArea::label($owner) . '. Libere-o lá antes.');
                }
            }

            $removed = ClosureStatusUserRoute::query()
                ->where('area', $area)
                ->whereNotIn('status_user_prefix', $wanted->all())
                ->delete();

            $added = 0;

            foreach ($wanted as $prefix) {
                if (!isset($current[$prefix])) {
                    ClosureStatusUserRoute::query()->create(['area' => $area, 'status_user_prefix' => $prefix, 'created_by' => $actor->id]);
                    $added++;
                }
            }

            return ['added' => $added, 'removed' => $removed];
        });
    }
}
