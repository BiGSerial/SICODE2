<?php

namespace App\Services\Partner;

use App\Models\{Note, Partial, Production, Service, WorkReport};
use App\Services\WorkReports\WorkReportFinalScopeResolver;
use App\Support\SicodeRules;

class BlockEvaluator
{
    public const FREE        = 0; // Pode Informar
    public const HOLD_BLUE   = 1; // Agujardando Conclusao de Parcial
    public const HOLD_YELLOW = 2; // Obra Fora de Status de Construção
    public const HOLD_GREEN  = 3; // Concluído
    public const HOLD_RED    = 4; // Obra Já Informada

    private const CONSTRUCTION_NSTATS = [51, 52, 53];

    public function evaluate(Note $note)
    {
        // Carrega WorkForm e a parcial mais recente (via relação currentPartial)
        $note->load(['WorkForm', 'partials']);

        $payment_id = Service::where('service', 'Pagamento')->value('uuid');

        $supervision_id = Service::where('service', 'Fiscalização')->value('uuid');

        $wf = $note->WorkForm;

        $ps = $note->partials()?->orderBy('created_at', 'desc')->first();

        $production = null;
        ;

        // 1) Já informada?
        if ($this->hasCompletedFinalScopeCoverage($note, $wf)) {
            return $this->res(self::HOLD_RED, false, 'Obra já informada', null, $wf);
        }

        // 2) Parcial ativa aguardando algo?
        if ($ps && $this->isActivePartial($ps)) {
            // Determine service ID based on supervision status
            $serviceId = !$ps->supervision ? $supervision_id : $payment_id;

            // Only execute one query with the correct service ID
            $production = $note->productions()
            ->where('service_id', $serviceId)
            ->where('partial', true)
            ->where('completed', false)
            ->with(['user:id,name', 'service:uuid,service'])
            ->orderBy('created_at', 'desc')
            ->first();

            $reason = !$ps->supervision
            ? 'Aguardando fiscalização da parcial'
            : 'Aguardando pagamento da parcial';

            return $this->res(self::HOLD_BLUE, false, $reason, $ps, null, $production);
        }

        if (!$this->noteStatusBlocksSuspended()) {
            // // 3) Obra fora de status de construção
            if ($note->type_note == 2 && !$this->isConstructionNstats($note->nstats)) {
                return $this->res(self::HOLD_YELLOW, false, '<strong>Obra fora de status de construção</strong> <p>Entre em contato com o engenheiro da sua região</p>');
            }

            // // 4) Obra fora de status de construção
            if ($note->type_note == 1 && $this->centerjobIsSetAndNotCONS($note->centerjob)) {
                return $this->res(self::HOLD_YELLOW, false, '<strong>Obra fora de status de construção</strong> <p>Entre em contato com o engenheiro da sua região</p>');
            }
        }

        // 5) Liberado
        return $this->res(self::FREE, true, 'Liberado para informar');
    }

    private function isActivePartial(Partial $ps): bool
    {
        // Garante boolean
        return (bool)$ps->allow === true
            && (bool)$ps->deny === false
            && (bool)$ps->complete === false;
    }

    private function isConstructionNstats(int $nstats): bool
    {
        return in_array($nstats, self::CONSTRUCTION_NSTATS, true);
    }

    private function centerjobIsSetAndNotCONS(?string $centerjob): bool
    {
        if (!$centerjob) {
            return false; // sem centerjob não dispara essa regra
        }

        $normalized = mb_strtoupper(trim($centerjob));

        // PHP 8+: testa se NÃO inicia com 'CONS'
        return !str_starts_with($normalized, 'CONS');
    }

    private function noteStatusBlocksSuspended(): bool
    {
        return !SicodeRules::workReportBlocksByNoteStatus();
    }

    private function hasCompletedFinalScopeCoverage(Note $note, ?WorkReport $workReport): bool
    {
        if (!SicodeRules::workReportSplitsBtzeroEpFinalFlows() || (int) $note->type_note !== 1) {
            return (bool) $workReport;
        }

        $orders = $note->relationLoaded('Orders') ? $note->Orders : $note->Orders()->get();
        $orders = $orders
            ->filter(fn ($order) => !(strpos((string) $order->statusSist, 'ENT') === 0 || strpos((string) $order->statusSist, 'ENC') === 0))
            ->values();

        $detectedScopes = collect(app(WorkReportFinalScopeResolver::class)->resolve($note->type_note, $orders))
            ->pluck('scope')
            ->filter()
            ->unique()
            ->values();

        if ($detectedScopes->isEmpty()) {
            return (bool) $workReport;
        }

        $activeScopes = WorkReport::query()
            ->with(['Note', 'Orders'])
            ->where('note_id', $note->id)
            ->where('canceled', false)
            ->get()
            ->flatMap(fn (WorkReport $activeWorkReport) => collect($activeWorkReport->finalScopePayloads())->pluck('scope'))
            ->filter()
            ->unique()
            ->values();

        return $activeScopes->isNotEmpty()
            && $detectedScopes->diff($activeScopes)->isEmpty();
    }

    private function res(int $block, bool $command, string $reason, ?Partial $partial = null, ?WorkReport $work = null, ?Production $production = null): object
    {
        return (object)[
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'partial'    => $partial,
            'workform'   => $work,
            'production' => $production,
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
