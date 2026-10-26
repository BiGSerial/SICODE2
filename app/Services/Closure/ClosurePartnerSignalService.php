<?php

namespace App\Services\Closure;

use App\Models\{Centerjob, Company, User};
use App\Models\Closure\{ClosureDepositCalendarEntry, ClosureOrderState, ClosurePartnerSignal, ClosurePartnerSignalState};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Bloqueio de depósito. O depósito é o código SAP (ex.: 5503); os centros de trabalho que ele cobre vêm do cadastro
 * da empresa (`centerjobs`: Administração > Empresa), e as Ordens afetadas são as que têm um desses centros em
 * `cenTrab` na operação 0010, em tratativa ativa. Um clique bloqueia todas e um clique libera todas, devolvendo
 * cada uma ao status que tinha. Ordens já bloqueadas à mão por um usuário interno não são tocadas nem liberadas.
 *
 * Duas portas de entrada: a parceira sinaliza (origem PARTNER) ou o despacho confirma uma data do calendário de
 * inventário da gestão de ativos (origem CALENDAR).
 */
class ClosurePartnerSignalService
{
    public function __construct(private ClosureOrderWorkflowService $workflow)
    {
    }

    /**
     * Centros de trabalho cadastrados para o depósito (opcionalmente restrito ao centro SAP).
     *
     * @return Collection<int, Centerjob>
     */
    public function workCenterRows(string $deposit, ?string $center = null): Collection
    {
        $deposit = $this->normalize($deposit);
        $center  = $center !== null ? $this->normalize($center) : null;

        return Centerjob::query()
            ->whereRaw('upper(trim(deposit)) = ?', [$deposit])
            ->whereNotNull('centerjob')
            ->where('centerjob', '!=', '')
            ->when($center, fn ($q) => $q->where(fn ($w) => $w->whereRaw('upper(trim(center)) = ?', [$center])->orWhereNull('center')->orWhere('center', '')))
            ->with('Company')
            ->get();
    }

    /** @return array<int, string> códigos de centro de trabalho (cenTrab) do depósito */
    public function workCenters(string $deposit, ?string $center = null): array
    {
        return $this->workCenterRows($deposit, $center)->pluck('centerjob')->map(fn ($c) => $this->normalize((string) $c))->unique()->values()->all();
    }

    /**
     * Depósitos cadastrados para a empresa, com os centros de trabalho e quantas Ordens um bloqueio alcançaria agora.
     *
     * @return Collection<int, object{code: string, center: ?string, label: string, work_centers: array<int,string>, total: int}>
     */
    public function deposits(Company|string $company): Collection
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return Centerjob::query()
            ->where('company_id', $companyId)
            ->whereNotNull('deposit')->where('deposit', '!=', '')
            ->get()
            ->groupBy(fn (Centerjob $row) => $this->normalize((string) $row->deposit))
            ->map(function (Collection $rows, string $code) {
                $centers = $this->workCenters($code, $rows->first()->center);
                $label   = ClosureDepositCalendarEntry::query()->where('deposit', $code)->orderByDesc('year')->value('denomination');

                return (object) [
                    'code'         => $code,
                    'center'       => $rows->first()->center ? $this->normalize((string) $rows->first()->center) : null,
                    'label'        => $label ?: 'Depósito ' . $code,
                    'work_centers' => $centers,
                    'total'        => $centers ? $this->affectedStates($centers)->count() : 0,
                ];
            })
            ->sortBy('code')
            ->values();
    }

    /**
     * Ordens em tratativa ativa cujo centro de trabalho da operação 0010 é um dos informados.
     *
     * @param array<int, string> $workCenters
     */
    public function affectedStates(array $workCenters): Builder
    {
        return ClosureOrderState::query()
            ->whereIn('operational_status', ClosureOrderWorkflowService::DEPOSIT_BLOCKABLE_STATUSES)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('operations as o')
                ->whereColumn('o.order_id', 'closure_order_states.order_id')
                ->where('o.operacao', '0010')
                ->whereIn('o.cenTrab', $workCenters ?: ['']));
    }

    /**
     * Bloqueios de depósito ativos que alcançam cada Ordem (estado) informada, para marcar as listas.
     *
     * @param iterable<int, int|string> $stateIds
     * @return Collection<int, ClosurePartnerSignal> chave = id do estado
     */
    public function activeByState(iterable $stateIds): Collection
    {
        $ids = collect($stateIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $pairs = ClosurePartnerSignalState::query()
            ->whereIn('closure_order_state_id', $ids)
            ->whereNull('released_at')
            ->whereHas('Signal', fn ($q) => $q->active())
            ->with('Signal')
            ->get();

        return $pairs->mapWithKeys(fn (ClosurePartnerSignalState $pair) => [$pair->closure_order_state_id => $pair->Signal]);
    }

    public function activeFor(string $deposit, string $type = ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED): ?ClosurePartnerSignal
    {
        return ClosurePartnerSignal::query()->active()->where('type', $type)->where('deposit_code', $this->normalize($deposit))->first();
    }

    public function blockDeposit(
        string $deposit,
        User $actor,
        string $reason,
        ?string $center = null,
        ?Company $company = null,
        string $origin = ClosurePartnerSignal::ORIGIN_PARTNER,
        ?ClosureDepositCalendarEntry $entry = null,
    ): ClosurePartnerSignal {
        $deposit = $this->normalize($deposit);
        $reason  = trim($reason);

        if ($reason === '') {
            throw new ClosureOrderWorkflowException('Informe o motivo do bloqueio.');
        }

        $rows = $this->workCenterRows($deposit, $center);

        if ($company) {
            $rows = $rows->where('company_id', $company->id)->values();
        }

        $centers = $rows->pluck('centerjob')->map(fn ($c) => $this->normalize((string) $c))->unique()->values()->all();

        if ($centers === []) {
            throw new ClosureOrderWorkflowException("O depósito {$deposit} não tem centro de trabalho cadastrado em nenhuma empresa (Administração > Empresa > Centros e depósitos).");
        }

        if ($this->activeFor($deposit)) {
            throw new ClosureOrderWorkflowException("Já existe um bloqueio ativo para o depósito {$deposit}. Libere-o antes de sinalizar de novo.");
        }

        $signal = ClosurePartnerSignal::query()->create([
            'company_id'        => $company?->id ?? ($rows->pluck('company_id')->unique()->count() === 1 ? $rows->first()->company_id : null),
            'type'              => ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED,
            'status'            => ClosurePartnerSignal::STATUS_ACTIVE,
            'origin'            => $origin,
            'deposit_code'      => $deposit,
            'center'            => $center ? $this->normalize($center) : null,
            'deposit_label'     => $entry?->denomination ?: ClosureDepositCalendarEntry::query()->where('deposit', $deposit)->orderByDesc('year')->value('denomination'),
            'work_centers'      => $centers,
            'calendar_entry_id' => $entry?->id,
            'reason'            => $reason,
            'created_by'        => $actor->id,
        ]);

        $count = 0;

        $this->affectedStates($centers)->orderBy('id')->each(function (ClosureOrderState $state) use ($signal, $actor, $reason, &$count) {
            $previous = $state->operational_status;

            try {
                $this->workflow->blockDepositByPartner($state, $actor, $signal->id, $reason);
            } catch (ClosureOrderWorkflowException) {
                return; // Ordem cancelada/encerrada no SAP: fora do alcance, sem derrubar o lote.
            }

            ClosurePartnerSignalState::query()->create([
                'closure_partner_signal_id' => $signal->id,
                'closure_order_state_id'    => $state->id,
                'previous_status'           => $previous,
            ]);
            $count++;
        });

        $signal->update(['affected_count' => $count]);

        if ($entry) {
            $entry->update(['status' => ClosureDepositCalendarEntry::STATUS_BLOCKED, 'signal_id' => $signal->id, 'confirmed_by' => $actor->id, 'confirmed_at' => now()]);
        }

        app(ClosureNotifier::class)->partnerSignalChanged($signal->fresh(), $actor, true);

        return $signal->fresh();
    }

    public function releaseDeposit(ClosurePartnerSignal $signal, User $actor, ?string $note = null): ClosurePartnerSignal
    {
        if ($signal->status !== ClosurePartnerSignal::STATUS_ACTIVE) {
            throw new ClosureOrderWorkflowException('Este bloqueio já foi liberado.');
        }

        $released = 0;

        $signal->Items()->whereNull('released_at')->with('State')->orderBy('id')->each(function (ClosurePartnerSignalState $item) use ($signal, $actor, &$released) {
            if ($item->State && $item->State->operational_status === ClosureOrderWorkflowService::STATUS_DEPOSIT_BLOCKED) {
                try {
                    $this->workflow->releaseDepositByPartner($item->State, $actor, $signal->id, $item->previous_status);
                    $released++;
                } catch (ClosureOrderWorkflowException) {
                    // Ordem encerrada/cancelada nesse meio tempo: só fecha o item.
                }
            }

            $item->update(['released_at' => now()]);
        });

        $signal->update([
            'status'         => ClosurePartnerSignal::STATUS_RELEASED,
            'released_by'    => $actor->id,
            'released_at'    => now(),
            'released_count' => $released,
            'release_note'   => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);

        ClosureDepositCalendarEntry::query()->where('signal_id', $signal->id)->update(['status' => ClosureDepositCalendarEntry::STATUS_RELEASED]);

        app(ClosureNotifier::class)->partnerSignalChanged($signal->fresh(), $actor, false);

        return $signal->fresh();
    }

    private function normalize(string $value): string
    {
        return mb_strtoupper(trim($value));
    }
}
