<?php

namespace App\Services\Partner;

use App\Models\{Order, Partial, PepBalanceAlert, User, WorkReport};
use App\Notifications\SystemNotification;
use App\Support\Notifications\UserNotificationData;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lista de observação de saldo de PEP: todo informe (final ou parcial) que entra sem saldo, ou com ADS acima do saldo,
 * vai para a pilha da Engenharia da região (e das empresas responsáveis) sem bloquear o parceiro.
 *
 * - Item sai da pilha aberta se o informe é removido (final cancelado; parcial negada ou excluída).
 * - Se o saldo passa a cobrir o pedido e o engenheiro não age por mais de {@see self::AUTO_RESOLVE_HOURS}h,
 *   o item vai sozinho para o histórico (handled_via = auto).
 */
class PepBudgetWatchService
{
    public const AUTO_RESOLVE_HOURS = 48;

    public function __construct(private readonly WorkReportBudgetGuard $guard)
    {
    }

    public function registerWorkReport(WorkReport $workReport): ?PepBalanceAlert
    {
        return $this->register(PepBalanceAlert::TYPE_WORK_REPORT, $workReport, $this->amountFor($workReport));
    }

    public function registerPartial(Partial $partial): ?PepBalanceAlert
    {
        return $this->register(PepBalanceAlert::TYPE_PARTIAL, $partial, $this->amountFor($partial));
    }

    /**
     * Nunca lança: a lista de observação não pode impedir o parceiro de enviar o informe.
     */
    private function register(string $type, WorkReport|Partial $informe, float $amount): ?PepBalanceAlert
    {
        if (!SicodeRules::workReportMonitorsBudget()) {
            return null;
        }

        try {
            $shortfall = $this->guard->shortfall($this->ordersOf($informe), $amount);

            if (!$shortfall) {
                return null;
            }

            $alert = PepBalanceAlert::query()->firstOrCreate(
                ['informe_type' => $type, 'informe_id' => $informe->getKey()],
                [
                    'note_id'    => $informe->note_id,
                    'company_id' => $informe->company_id,
                    'scope'      => $this->scopeOf($informe),
                    'region'     => $this->regionOf($informe),
                    'reason'     => $shortfall['reason'],
                    'balances'   => $shortfall['balances'],
                    'status'     => PepBalanceAlert::STATUS_OPEN,
                ]
            );

            if ($alert->wasRecentlyCreated) {
                $this->notifyEngineers($alert);
            }

            return $alert;
        } catch (Throwable $e) {
            Log::warning('Lista de observação de PEP: falha ao registrar informe.', [
                'type' => $type,
                'id'   => $informe->getKey(),
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Pilha aberta: só itens cujo informe ainda existe e não foi removido.
     */
    public function openQuery(): Builder
    {
        return PepBalanceAlert::query()
            ->open()
            ->where(function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('informe_type', PepBalanceAlert::TYPE_WORK_REPORT)
                        ->whereExists(fn ($sub) => $sub->selectRaw('1')->from('work_reports')
                            ->whereColumn('work_reports.id', 'pep_balance_alerts.informe_id')
                            ->where('work_reports.canceled', false));
                })->orWhere(function (Builder $q) {
                    $q->where('informe_type', PepBalanceAlert::TYPE_PARTIAL)
                        ->whereExists(fn ($sub) => $sub->selectRaw('1')->from('partials')
                            ->whereColumn('partials.id', 'pep_balance_alerts.informe_id')
                            ->where('partials.deny', false));
                });
            });
    }

    /**
     * Restringe ao que o engenheiro enxerga: região da obra ou empresa responsável.
     * Superadm, ou engenheiro sem região nem empresa cadastradas, vê tudo.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->superadm) {
            return $query;
        }

        $regions   = $this->regionsOf($user);
        $companies = $user->Companies->pluck('id')->all();

        if ($regions === [] && $companies === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($regions, $companies) {
            $q->whereIn('region', $regions)->orWhereIn('company_id', $companies);
        });
    }

    /**
     * Engenheiros da região da obra ou responsáveis pela empresa do informe.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(PepBalanceAlert $alert): Collection
    {
        return User::query()
            ->where('engineer', true)
            ->where(function (Builder $q) use ($alert) {
                if ($alert->region) {
                    $q->whereHas('regions', fn ($r) => $r->where('region', $alert->region));
                }

                if ($alert->company_id) {
                    $q->orWhereHas('Companies', fn ($c) => $c->where('companies.id', $alert->company_id));
                }
            })
            ->get();
    }

    public function handle(PepBalanceAlert $alert, User $user, ?string $notes = null): bool
    {
        if ($alert->status !== PepBalanceAlert::STATUS_OPEN) {
            return false;
        }

        $alert->forceFill([
            'status'        => PepBalanceAlert::STATUS_HANDLED,
            'handled_at'    => now(),
            'handled_by'    => $user->getKey(),
            'handled_via'   => PepBalanceAlert::VIA_ENGINEER,
            'handled_notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
        ])->save();

        return true;
    }

    /**
     * Reavalia a pilha: o saldo sincronizado pode ter passado a cobrir o pedido. Cobrindo há mais de 48h sem ação,
     * o item vai para o histórico como saída automática; se o saldo voltar a faltar, a contagem zera.
     *
     * @return array{checked: int, covered: int, resolved: int}
     */
    public function reconcile(): array
    {
        $stats = ['checked' => 0, 'covered' => 0, 'resolved' => 0];

        $this->openQuery()->orderBy('id')->each(function (PepBalanceAlert $alert) use (&$stats) {
            $informe = $alert->informe();

            if (!$informe) {
                return;
            }

            $stats['checked']++;
            $shortfall = $this->guard->shortfall($this->ordersOf($informe), $this->amountFor($informe));

            if ($shortfall) {
                $alert->forceFill([
                    'reason'        => $shortfall['reason'],
                    'balances'      => $shortfall['balances'],
                    'covered_since' => null,
                ])->save();

                return;
            }

            $stats['covered']++;
            $since = $alert->covered_since ?? now();

            if (!$alert->covered_since) {
                $alert->forceFill(['covered_since' => $since])->save();

                return;
            }

            if ($since->lte(now()->subHours(self::AUTO_RESOLVE_HOURS))) {
                $alert->forceFill([
                    'status'        => PepBalanceAlert::STATUS_HANDLED,
                    'handled_at'    => now(),
                    'handled_via'   => PepBalanceAlert::VIA_AUTO,
                    'handled_notes' => 'Saída automática: saldo do PEP passou a cobrir o pedido e não houve ação em ' . self::AUTO_RESOLVE_HOURS . 'h.',
                ])->save();
                $stats['resolved']++;
            }
        });

        return $stats;
    }

    private function notifyEngineers(PepBalanceAlert $alert): void
    {
        $note = $alert->Note?->note ?? $alert->note_id;
        $kind = $alert->isPartial() ? 'parcial' : 'final';

        foreach ($this->recipientsFor($alert) as $engineer) {
            try {
                $engineer->notify(new SystemNotification(new UserNotificationData(
                    title: 'Informe sem saldo no PEP',
                    message: "Entrou um informe {$kind} na obra {$note} sem saldo disponível no PEP. Verifique para tratar.",
                    link: route('engineers.pep_alerts'),
                    status: 'warning',
                )));
            } catch (Throwable $e) {
                Log::warning('Lista de observação de PEP: falha ao notificar engenheiro.', ['user' => $engineer->getKey(), 'erro' => $e->getMessage()]);
            }
        }
    }

    /** @return Collection<int, Order> */
    private function ordersOf(Model $informe): Collection
    {
        return $informe->Orders()->get();
    }

    private function amountFor(Model $informe): float
    {
        return $informe instanceof Partial
            ? (float) $informe->value
            : (float) ($informe->Adsform?->amount ?? 0);
    }

    private function scopeOf(Model $informe): ?string
    {
        if ($informe instanceof Partial) {
            return $informe->scope ?: 'general';
        }

        return collect($informe->selected_final_scopes ?? [])->filter()->first() ?: 'general';
    }

    private function regionOf(Model $informe): ?string
    {
        $region = trim((string) $informe->Note?->City?->baseConstrucao);

        return $region !== '' ? $region : null;
    }

    /** @return array<int, string> */
    private function regionsOf(User $user): array
    {
        return $user->regionNames()->map(fn ($r) => trim((string) $r))->filter()->unique()->values()->all();
    }
}
