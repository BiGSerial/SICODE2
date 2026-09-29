<?php

namespace App\Services\Supervision;

use App\Models\{Production, Service, WorkReport, WorkReportFlowProduction};

class WorkReportBlockEvaluator
{
    public const FREE        = 0;
    public const HOLD_BLUE   = 1;
    public const HOLD_YELLOW = 2;
    public const HOLD_GREEN  = 3;
    public const HOLD_RED    = 4;

    public function evaluate(WorkReport $workReport, Service $service): array
    {
        $production = $this->currentProductionFor($workReport, $service);

        if ((bool) $workReport->canceled) {
            return $this->res(self::HOLD_RED, false, 'workreport_cancelado', $production);
        }

        if ((bool) $workReport->rejected) {
            return $this->res(
                self::HOLD_YELLOW,
                false,
                $production ? 'workreport_rejeitado_com_producao_atribuida' : 'workreport_rejeitado',
                $production
            );
        }

        if (!$production) {
            return $this->res(self::FREE, true, 'workreport_sem_producao');
        }

        if (!$production->completed) {
            if ((int) $production->status === 1 || empty($production->user_id)) {
                return $this->res(self::HOLD_YELLOW, false, 'workreport_producao_na_pilha', $production);
            }

            return $this->res(self::HOLD_BLUE, false, 'workreport_producao_em_andamento', $production);
        }

        $workReportMark = $workReport->informed_at ?? $workReport->created_at;
        $productionMark = $production->completed_at ?? $production->created_at;

        if ($workReportMark && $productionMark && $workReportMark > $productionMark) {
            return $this->res(self::FREE, true, 'workreport_reinformado_pos_producao_concluida', $production);
        }

        if (
            $production->dt_note
            && $workReport->Note?->dt_status
            && $production->dt_note->equalTo($workReport->Note->dt_status)
            && $production->status_note == $workReport->Note?->nstats
        ) {
            return $this->res(self::HOLD_RED, true, 'workreport_sap_nao_refletido_mesma_data_status', $production);
        }

        if ($production->confirmed) {
            return $this->res(self::HOLD_RED, true, 'workreport_producao_confirmada', $production);
        }

        return $this->res(self::HOLD_GREEN, false, 'workreport_producao_concluida', $production);
    }

    public function currentProductionFor(WorkReport $workReport, Service $service): ?Production
    {
        $links = $workReport->relationLoaded('FlowProductions')
            ? $workReport->FlowProductions
            : $workReport->FlowProductions()->with(['Production.User', 'Production.Company'])->get();

        return $links
            ->filter(function (WorkReportFlowProduction $link) use ($service) {
                return $link->stage === WorkReportFlowProduction::STAGE_FISCALIZATION
                    && $link->is_current
                    && $link->Production
                    && $link->Production->service_id === $service->uuid;
            })
            ->sortByDesc(fn (WorkReportFlowProduction $link) => $link->Production?->created_at)
            ->first()
            ?->Production;
    }

    private function res(int $block, bool $command, string $reason, ?Production $production = null): array
    {
        return [
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'production' => $production,
            'isPartial'  => false,
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
