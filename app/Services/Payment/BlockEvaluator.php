<?php

namespace App\Services\Payment;

use App\Models\{MeasurementProductionContext, Note, Production, Service, WorkReportFlowProduction};

class BlockEvaluator
{
    public const FREE        = 0; // sem Production ativa para esta demanda
    public const HOLD_BLUE   = 1; // atribuída a um usuário
    public const HOLD_YELLOW = 2; // na pilha da empresa, sem usuário
    public const HOLD_GREEN  = 3; // concluída há menos de 24 h, SAP pendente
    public const HOLD_RED    = 4; // concluída há 24 h ou mais, SAP pendente

    public function evaluate(Note $note, Service $service): array
    {
        $partialId = (int) ($note->dispatch_partial_id ?? 0);
        // Despacho/linha de Parcial: a identidade é o parcial, nunca o Informe Final
        // que a Nota carrega por relação. Produção de outro informe não bloqueia.
        $workReport = $partialId > 0 && (int) ($note->dispatch_work_report_id ?? 0) === 0
            ? null
            : $note->WorkForm;
        $fiveNote = $workReport?->FiveNote ?? $note->FiveNote;
        $partials = $note->Partials
            ?->where('allow', true)
            ->where('supervision', true)
            ->where('deny', false)
            ->sortByDesc('created_at');
        $partial = ($partialId > 0 ? $partials?->firstWhere('id', $partialId) : null) ?? $partials?->first();

        // Linha de Informe Parcial: a Production precisa ser a daquele parcial.
        // Uma Medição concluída de outro parcial da mesma Nota não bloqueia este.
        $scopedPartial = !$workReport && $partialId > 0 ? $partial : null;
        $production    = $scopedPartial
            ? $this->productionForPartial($note, $service->uuid, $scopedPartial)
            : $this->latestProductionForService($note, $service->uuid, $fiveNote);

        if (!$production) {
            if ($workReport?->rejected) {
                return $this->res(self::HOLD_RED, false, 'work_report_rejected');
            }

            return $this->res(self::FREE, true, $partial ? 'partial_without_production' : 'without_production');
        }

        // Aberta: azul para usuário, amarelo para pilha. Ambas impedem uma
        // segunda Production equivalente da mesma demanda.
        if (!$production->completed) {
            return empty($production->user_id)
                ? $this->res(self::HOLD_YELLOW, false, 'production_in_company_stack', $production)
                : $this->res(self::HOLD_BLUE, false, 'production_assigned_to_user', $production);
        }

        if ($workReport?->rejected) {
            return $this->res(self::HOLD_RED, false, 'work_report_rejected', $production);
        }

        // Finalizada com a condição de Medição ainda presente no SAP: verde até
        // 24 h e vermelho depois. Continua bloqueada para não repetir o ciclo.
        if ($this->demandStillQualifiesInSap($workReport, $fiveNote, $partial)) {
            $completedAt = $production->completed_at ?: $production->created_at;

            return $completedAt && $completedAt->lessThanOrEqualTo(now()->subDay())
                ? $this->res(self::HOLD_RED, false, 'completed_over_24h_sap_not_updated', $production)
                : $this->res(self::HOLD_GREEN, false, 'completed_under_24h_waiting_sap', $production);
        }

        return $this->res(self::FREE, true, 'completed_and_sap_updated', $production);
    }

    private function demandStillQualifiesInSap($workReport, $fiveNote, $partial): bool
    {
        if ($fiveNote?->isEligibleForPaymentReturn()) {
            return true;
        }

        if ($workReport) {
            $orders = $workReport->relationLoaded('Orders')
                ? $workReport->Orders
                : $workReport->Orders()->with('Operations')->get();

            if ($orders->contains(fn ($order) => app(WorkReportPaymentCandidateQuery::class)->orderIsEligible($order))) {
                return true;
            }
        }

        return (bool) $partial;
    }

    /**
     * Production do Informe Parcial: vínculo explícito (productionables) ou, para
     * históricos sem vínculo, uma Production parcial sem nenhum parcial associado
     * criada depois da Fiscalização do parcial.
     */
    private function productionForPartial(Note $note, string $serviceUuid, $partial): ?Production
    {
        $productions = ($note->relationLoaded('Productions')
            ? $note->Productions
            : $note->Productions()->get())
            ->where('service_id', $serviceUuid)
            ->sortByDesc('created_at');

        $linked = $productions->first(
            fn (Production $production) => $production->partialInforms()->whereKey($partial->id)->exists()
        );

        if ($linked) {
            return $linked;
        }

        return $productions->first(
            fn (Production $production) => (bool) $production->partial
                && !$production->partialInforms()->exists()
                && !$production->WorkReportFlowProductions()->exists()
                && !$production->measurementContext?->work_report_id
                && (!$partial->supervision_at || $production->created_at >= $partial->supervision_at)
        );
    }

    private function latestProductionForService(Note $note, string $serviceUuid, $fiveNote = null): ?Production
    {
        $workReportId = (int) ($note->dispatch_work_report_id ?? 0);

        // Ciclo 2 é uma nova demanda da D5. Uma Production concluída de Ciclo 1
        // da mesma Nota/D5 não pode bloquear esse retorno.
        if ($fiveNote?->isEligibleForPaymentReturn()) {
            return $this->openD5ReturnProduction($note, $serviceUuid, $fiveNote);
        }

        if ($note->relationLoaded('Productions')) {
            $productions = $note->Productions
                ->where('service_id', $serviceUuid)
                ->sortByDesc('created_at');

            if ($workReportId > 0) {
                $scopedProduction = $productions
                    ->first(function (Production $production) use ($workReportId) {
                        return $production->WorkReportFlowProductions
                            ->where('work_report_id', $workReportId)
                            ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                            ->where('is_current', true)
                            ->isNotEmpty();
                    });

                if ($scopedProduction) {
                    return $scopedProduction;
                }

                // Compatibilidade para Productions criadas antes de o link de
                // Pagamento ser obrigatório: o contexto ainda identifica o Informe.
                return $productions->first(
                    fn (Production $production) => (int) ($production->measurementContext?->work_report_id ?? 0) === $workReportId
                );
            }

            return $productions->first();
        }

        $query = Production::where('note_id', $note->id)
            ->where('service_id', $serviceUuid)
            ->orderByDesc('created_at');

        if ($workReportId > 0) {
            $query->whereHas('WorkReportFlowProductions', function ($q) use ($workReportId) {
                $q->where('work_report_id', $workReportId)
                    ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                    ->where('is_current', true);
            });

            $scopedProduction = $query->first();

            if ($scopedProduction) {
                return $scopedProduction;
            }

            return (clone $query)
                ->whereHas('measurementContext', fn ($context) => $context->where('work_report_id', $workReportId))
                ->first();
        }

        return $query->first();
    }

    private function openD5ReturnProduction(Note $note, string $serviceUuid, $fiveNote): ?Production
    {
        if ($note->relationLoaded('Productions')) {
            $productions = $note->Productions
                ->where('service_id', $serviceUuid)
                ->where('completed', false)
                ->sortByDesc('created_at');

            $contextProduction = $productions->first(
                fn (Production $production) => (int) ($production->measurementContext?->five_note_id ?? 0) === (int) $fiveNote->id
                && $production->measurementContext?->cycle === MeasurementProductionContext::CYCLE_D5_RETURN
            );

            if ($contextProduction) {
                return $contextProduction;
            }

            // Fallback temporário para históricos sem contexto: precisa estar
            // aberta, vinculada à D5 exata e ser posterior à conclusão da D5.
            return $productions->first(function (Production $production) use ($fiveNote): bool {
                if (!$production->dfive || !$fiveNote->completed_at || $production->created_at < $fiveNote->completed_at) {
                    return false;
                }

                return $production->fiveNotes()->whereKey($fiveNote->id)->exists();
            });
        }

        $query = Production::query()
            ->where('note_id', $note->id)
            ->where('service_id', $serviceUuid)
            ->where('completed', false)
            ->orderByDesc('created_at');

        $contextProduction = (clone $query)
            ->whereHas('measurementContext', fn ($context) => $context
                ->where('five_note_id', $fiveNote->id)
                ->where('cycle', MeasurementProductionContext::CYCLE_D5_RETURN))
            ->first();

        if ($contextProduction) {
            return $contextProduction;
        }

        return $fiveNote->completed_at
            ? $query->where('dfive', true)
                ->where('created_at', '>=', $fiveNote->completed_at)
                ->whereHas('fiveNotes', fn ($d5) => $d5->whereKey($fiveNote->id))
                ->first()
            : null;
    }

    private function res(int $block, bool $command, string $reason, ?Production $prod = null): array
    {
        return [
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'production' => $prod,
        ];
    }

    private function colorFor(int $block): string
    {
        return match ($block) {
            self::FREE        => '',
            self::HOLD_BLUE   => 'table-primary',
            self::HOLD_YELLOW => 'table-warning',
            self::HOLD_GREEN  => 'table-success',
            self::HOLD_RED    => 'table-danger',
            default           => '',
        };
    }
}
