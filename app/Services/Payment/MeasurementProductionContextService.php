<?php

namespace App\Services\Payment;

use App\Models\{FiveNote, MeasurementProductionContext, Partial, Production, WorkReport};
use DomainException;

/** Persiste a identidade da demanda no mesmo commit da Production. */
class MeasurementProductionContextService
{
    public function recordPartial(Production $production, Partial $partial): MeasurementProductionContext
    {
        if ((int) $partial->note_id !== (int) $production->note_id) {
            throw new DomainException('O informe parcial não pertence à Nota/OV da Production.');
        }

        return MeasurementProductionContext::create([
            'production_id'     => $production->id,
            'partial_id'        => $partial->id,
            'cycle'             => MeasurementProductionContext::CYCLE_PARTIAL,
            'active_demand_key' => MeasurementProductionContext::partialActiveDemandKey($partial->id),
        ]);
    }

    public function recordCycleOne(Production $production, WorkReport $workReport, ?FiveNote $fiveNote = null): MeasurementProductionContext
    {
        $this->assertWorkReportBelongsToProduction($production, $workReport);
        $this->assertFiveNoteBelongsToWorkReport($fiveNote, $workReport);

        return MeasurementProductionContext::create([
            'production_id'  => $production->id,
            'work_report_id' => $workReport->id,
            'five_note_id'   => $fiveNote?->id,
            'cycle'          => MeasurementProductionContext::CYCLE_ONE,
        ]);
    }

    public function recordD5Return(Production $production, ?WorkReport $workReport, FiveNote $fiveNote): MeasurementProductionContext
    {
        if ($workReport) {
            $this->assertWorkReportBelongsToProduction($production, $workReport);
            $this->assertFiveNoteBelongsToWorkReport($fiveNote, $workReport);
        } elseif (!$fiveNote->isPassive) {
            throw new DomainException('Retorno de D5 sem Informe só é permitido para D5 passiva.');
        }

        if (!$fiveNote->isEligibleForPaymentReturn()) {
            throw new DomainException('A D5 não está elegível para Retorno de D5.');
        }

        return MeasurementProductionContext::create([
            'production_id'  => $production->id,
            'work_report_id' => $workReport?->id,
            'five_note_id'   => $fiveNote->id,
            'cycle'          => MeasurementProductionContext::CYCLE_D5_RETURN,
        ]);
    }

    public function releasePartial(Production $production): void
    {
        $production->measurementContext()
            ->where('cycle', MeasurementProductionContext::CYCLE_PARTIAL)
            ->update(['active_demand_key' => null]);
    }

    private function assertWorkReportBelongsToProduction(Production $production, WorkReport $workReport): void
    {
        if ((int) $workReport->note_id !== (int) $production->note_id) {
            throw new DomainException('O informe não pertence à Nota/OV da Production.');
        }
    }

    private function assertFiveNoteBelongsToWorkReport(?FiveNote $fiveNote, WorkReport $workReport): void
    {
        if ($fiveNote && (int) $fiveNote->work_report_id !== (int) $workReport->id) {
            throw new DomainException('A D5 não pertence ao Informe informado.');
        }
    }
}
