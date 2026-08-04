<?php

namespace App\Services\WorkReports;

use App\Models\WorkReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WorkReportStatusResolver
{
    public const FINALIZED = 'Finalizado';
    public const INCONSISTENT_PAYMENT = 'Pagamento Inconsistente';
    public const INFORM = 'Informe';
    public const WAITING_FISCALIZATION = 'Aguardando Fiscalização';
    public const FISCALIZATION = 'Em Fiscalização';
    public const WAITING_PAYMENT = 'Aguardando Pagamento';
    public const WAITING_D5_DISPATCH = 'Aguardando Despacho D5';
    public const WAITING_D5_RESOLUTION = 'Aguardando Resolução D5';
    public const WAITING_D5_FISCALIZATION = 'Aguardando Fiscalização D5';
    public const D5_FISCALIZATION = 'Fiscalização D5';
    public const WAITING_D5_PAYMENT = 'Aguardando Pagamento D5';
    public const RELEASING_LETTER = 'Liberando Carta';

    public function resolve(WorkReport $workReport): array
    {
        return $this->resolveState($this->stateFromWorkReport($workReport));
    }

    public function resolveState(array $state): array
    {
        $normalFiscalAssociated = (bool) ($state['normal_fiscal_associated'] ?? false);
        $normalFiscalFinished = (bool) ($state['normal_fiscal_finished'] ?? false);
        $normalPaymentAssociated = (bool) ($state['normal_payment_associated'] ?? false);
        $normalPaymentFinished = (bool) ($state['normal_payment_finished'] ?? false);

        $d5AssociatedToNote = (bool) ($state['d5_associated_to_note'] ?? false);
        $d5AssociatedToProduction = (bool) ($state['d5_associated_to_production'] ?? false);
        $d5Completed = (bool) ($state['d5_completed'] ?? false);
        $d5FiscalAssociated = (bool) ($state['d5_fiscal_associated'] ?? false);
        $d5FiscalFinished = (bool) ($state['d5_fiscal_finished'] ?? false);
        $d5PaymentAssociated = (bool) ($state['d5_payment_associated'] ?? false);
        $d5PaymentFinished = (bool) ($state['d5_payment_finished'] ?? false);
        $letterReleased = (bool) ($state['letter_released'] ?? false);
        $hasAds = (bool) ($state['has_ads'] ?? false);

        $anyFiscalAssociated = $normalFiscalAssociated || $d5FiscalAssociated;
        $anyPaymentAssociated = $normalPaymentAssociated || $d5PaymentAssociated;
        $paymentWithoutFiscal = ($normalPaymentAssociated && !$normalFiscalAssociated)
            || ($d5PaymentAssociated && !$d5FiscalAssociated);

        if (
            ($normalPaymentFinished || $d5PaymentFinished)
            && $d5Completed
            && !$paymentWithoutFiscal
            && $anyFiscalAssociated
            && (!$normalFiscalAssociated || $normalFiscalFinished)
            && (!$d5FiscalAssociated || $d5FiscalFinished)
        ) {
            return $this->status('finalized', self::FINALIZED, 'text-bg-success');
        }

        if ($paymentWithoutFiscal || ($anyPaymentAssociated && !$anyFiscalAssociated)) {
            return $this->status('inconsistent_payment', self::INCONSISTENT_PAYMENT, 'text-bg-danger');
        }

        if ($normalFiscalFinished && $d5AssociatedToNote && !$d5AssociatedToProduction) {
            return $this->status('waiting_d5_dispatch', self::WAITING_D5_DISPATCH, 'text-bg-warning');
        }

        if ($d5PaymentFinished && !$d5Completed) {
            return $this->status('waiting_d5_resolution', self::WAITING_D5_RESOLUTION, 'text-bg-warning');
        }

        if ($d5AssociatedToProduction && !$d5FiscalAssociated) {
            return $this->status('waiting_d5_fiscalization', self::WAITING_D5_FISCALIZATION, 'text-bg-secondary');
        }

        if ($d5FiscalAssociated && !$d5FiscalFinished) {
            return $this->status('d5_fiscalization', self::D5_FISCALIZATION, 'text-bg-primary');
        }

        if ($d5FiscalFinished && !$d5PaymentAssociated) {
            return $this->status('waiting_d5_payment', self::WAITING_D5_PAYMENT, 'text-bg-warning');
        }

        if ($d5FiscalFinished && $d5PaymentAssociated && (!$d5PaymentFinished || !$letterReleased)) {
            return $this->status('releasing_letter', self::RELEASING_LETTER, 'text-bg-primary');
        }

        if (!$normalFiscalAssociated && !$normalPaymentAssociated) {
            return $this->status(
                $hasAds ? 'waiting_fiscalization' : 'inform',
                $hasAds ? self::WAITING_FISCALIZATION : self::INFORM,
                $hasAds ? 'text-bg-secondary' : 'text-bg-info'
            );
        }

        if ($normalFiscalAssociated && !$normalFiscalFinished && !$normalPaymentAssociated) {
            return $this->status('fiscalization', self::FISCALIZATION, 'text-bg-primary');
        }

        if ($normalFiscalFinished && !$normalPaymentAssociated && !$d5AssociatedToNote) {
            return $this->status('waiting_payment', self::WAITING_PAYMENT, 'text-bg-warning');
        }

        if ($normalPaymentAssociated && !$normalPaymentFinished) {
            return $this->status('payment', 'Em Pagamento', 'text-bg-primary');
        }

        if ($normalPaymentFinished) {
            return $this->status('payment_finished', 'Pagamento Finalizado', 'text-bg-success');
        }

        return $this->status('inform', self::INFORM, 'text-bg-info');
    }

    private function stateFromWorkReport(WorkReport $workReport): array
    {
        $flowProductions = $workReport->FlowProductions ?? collect();
        $fiveNote = $workReport->Note?->FiveNote;
        $d5Productions = $this->finalProductions($fiveNote?->productions ?? collect());
        $d5ProductionIds = $d5Productions->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $normalFiscalProductions = $this->finalProductions(
            $flowProductions->where('stage', 'fiscalization')->pluck('Production')->filter()
        )->reject(fn ($production) => $this->isD5Production($production, $d5ProductionIds))->values();
        $normalPaymentProductions = $this->finalProductions(
            $flowProductions->where('stage', 'payment')->pluck('Production')->filter()
        )->reject(fn ($production) => $this->isD5Production($production, $d5ProductionIds))->values();
        $d5FiscalProductions = $this->productionsByService($d5Productions, 'fiscalizacao');
        $d5PaymentProductions = $this->productionsByService($d5Productions, 'pagamento');
        $effectiveD5PaymentProductions = $this->paymentsAfterLatestFiscalization($d5PaymentProductions, $d5FiscalProductions);

        return [
            'has_ads' => $workReport->Adsform !== null,
            'normal_fiscal_associated' => $normalFiscalProductions->isNotEmpty(),
            'normal_fiscal_finished' => $this->allFinished($normalFiscalProductions),
            'normal_payment_associated' => $normalPaymentProductions->isNotEmpty(),
            'normal_payment_finished' => $this->anyPaymentFinished($normalPaymentProductions),
            'd5_associated_to_note' => $fiveNote !== null,
            'd5_associated_to_production' => $d5Productions->isNotEmpty(),
            'd5_completed' => (bool) ($fiveNote?->is_completed ?? false),
            'd5_fiscal_associated' => $d5FiscalProductions->isNotEmpty(),
            'd5_fiscal_finished' => $this->allFinished($d5FiscalProductions),
            'd5_payment_associated' => $effectiveD5PaymentProductions->isNotEmpty(),
            'd5_payment_finished' => $this->anyPaymentFinished($effectiveD5PaymentProductions) || (bool) ($fiveNote?->is_archived ?? false),
            'letter_released' => (bool) ($fiveNote?->is_payed ?? false) || (bool) ($fiveNote?->is_archived ?? false),
        ];
    }

    private function productionsByService(Collection $productions, string $service): Collection
    {
        return $productions->filter(function ($production) use ($service) {
            return $this->normalizeService((string) ($production->Service?->service ?? '')) === $service;
        })->values();
    }

    private function finalProductions(Collection $productions): Collection
    {
        return $productions
            ->reject(fn ($production) => (bool) ($production->partial ?? false))
            ->values();
    }

    private function isD5Production(object $production, array $d5ProductionIds): bool
    {
        return (bool) ($production->d5 ?? false)
            || (bool) ($production->dfive ?? false)
            || in_array((int) ($production->id ?? 0), $d5ProductionIds, true);
    }

    private function paymentsAfterLatestFiscalization(Collection $payments, Collection $fiscalizations): Collection
    {
        $latestFiscalizationDate = $this->latestReferenceDate($fiscalizations, true);

        if (!$latestFiscalizationDate) {
            return $payments->values();
        }

        return $payments
            ->filter(function ($payment) use ($latestFiscalizationDate) {
                $paymentDate = $this->productionReferenceDate($payment, true);

                return $paymentDate && $paymentDate->greaterThanOrEqualTo($latestFiscalizationDate);
            })
            ->values();
    }

    private function allFinished(Collection $productions): bool
    {
        return $productions->isNotEmpty()
            && $productions->every(fn ($production) => (bool) ($production->completed ?? false));
    }

    private function anyPaymentFinished(Collection $productions): bool
    {
        return $productions->contains(fn ($production) => (bool) ($production->completed ?? false) || (bool) ($production->confirmed ?? false));
    }

    private function latestReferenceDate(Collection $productions, bool $preferCompleted): ?\Carbon\CarbonInterface
    {
        return $productions
            ->map(fn ($production) => $this->productionReferenceDate($production, $preferCompleted))
            ->filter()
            ->sort()
            ->last();
    }

    private function productionReferenceDate(object $production, bool $preferCompleted): ?\Carbon\CarbonInterface
    {
        $value = $preferCompleted
            ? ($production->completed_at ?? $production->confirmed_at ?? $production->att_at ?? $production->created_at ?? null)
            : ($production->att_at ?? $production->completed_at ?? $production->confirmed_at ?? $production->created_at ?? null);

        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    private function normalizeService(string $service): string
    {
        return Str::lower(trim(Str::ascii($service)));
    }

    private function status(string $key, string $label, string $class): array
    {
        return compact('key', 'label', 'class');
    }
}
