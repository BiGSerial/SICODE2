<?php

namespace App\Services\Partner;

use App\Models\{Note, Pep};
use App\Support\SicodeRules;
use Illuminate\Support\Collection;

/**
 * Bloqueio de orçamento do Informe de Obra (parcial e final): consulta o saldo (`peps.disponivel`, sincronizado do SQL Server)
 * dos PEPs das ordens abertas. Saldo zerado ou negativo bloqueia. Ordem sem PEP, ou PEP que não veio na sincronização, não bloqueia
 * (não há saldo para afirmar que acabou). Só contam as ordens cujo PEP termina em -2 (o que carrega o orçamento; costumam ser as 170/180); os demais sufixos não entram. Obras com `notes.type_note` = 2 nunca bloqueiam. Desligável por ruleset: `work_report.blocks_without_budget`.
 */
class WorkReportBudgetGuard
{
    /** Tipo de nota isento do bloqueio de orçamento. */
    private const EXEMPT_TYPE_NOTE = 2;

    /** Sufixo do PEP que controla o orçamento do informe. */
    private const BUDGET_PEP_SUFFIX = '-2';

    /**
     * PEPs sem saldo entre as ordens informadas (ordens fechadas ENT/ENC não contam).
     *
     * @param iterable<\App\Models\Order> $orders
     *
     * @return array<int, string> códigos dos PEPs bloqueantes, sem repetição
     */
    public function blockedPeps(iterable $orders): array
    {
        if (!SicodeRules::workReportBlocksWithoutBudget()) {
            return [];
        }

        return array_keys(array_filter($this->balances($orders), fn (float $available) => $available <= 0));
    }

    /**
     * PEPs da parcial quando o valor pedido (ADS) passa do saldo somado deles; vazio se couber (ou se não há saldo para comparar).
     * O saldo é lido aqui, no servidor, e nunca vai para o estado do componente nem para a tela do parceiro.
     *
     * @param iterable<\App\Models\Order> $orders
     *
     * @return array<int, string>
     */
    public function exceededPeps(iterable $orders, float $amount): array
    {
        if (!SicodeRules::workReportBlocksWithoutBudget()) {
            return [];
        }

        $balances = $this->balances($orders);

        return $balances !== [] && $amount - array_sum($balances) > 0.004 ? array_keys($balances) : [];
    }

    /**
     * Conteúdo do SweetAlert2 de bloqueio. Não revela o PEP nem o saldo ao parceiro: só a obra e o encaminhamento.
     */
    public function swal(string $note): array
    {
        $note = e($note);

        return [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'OBRA BLOQUEADA',
            'html'     => "<div class='text-center'>
                <span class='badge rounded-pill bg-light text-dark border fs-6 px-3 py-2 mb-3'>
                    <i class='ri-building-line me-1'></i>Obra <strong>{$note}</strong>
                </span>
                <div class='alert alert-danger d-flex align-items-start gap-3 text-start mb-3' role='alert'>
                    <i class='ri-wallet-3-line fs-1 lh-1'></i>
                    <div>
                        <h5 class='alert-heading fw-bold mb-1'>Sem orçamento disponível</h5>
                        <p class='mb-0'>Esta obra não possui orçamento disponível para receber o informe.</p>
                    </div>
                </div>
                <p class='text-muted small mb-0'>
                    <i class='ri-customer-service-2-line me-1'></i>
                    Entre em contato com o <strong>engenheiro ou especialista responsável pela sua região</strong>.
                </p>
            </div>",
        ];
    }

    /**
     * Saldo por PEP das ordens abertas (só PEPs sincronizados e com saldo informado), lido direto da tabela
     * para não pendurar o saldo nos models que o componente guarda.
     *
     * @return array<string, float>
     */
    private function balances(iterable $orders): array
    {
        $orders = Collection::make($orders)
            ->filter(fn ($order) => !$this->isClosed((string) $order->statusSist))
            ->filter(fn ($order) => $this->carriesBudget((string) $order->pep));

        $exemptNoteIds = Note::query()
            ->whereIn('id', $orders->pluck('note_id')->filter()->unique()->all())
            ->where('type_note', self::EXEMPT_TYPE_NOTE)
            ->pluck('id')
            ->all();

        $codes = $orders
            ->reject(fn ($order) => in_array($order->note_id, $exemptNoteIds, false))
            ->map(fn ($order) => trim((string) $order->pep))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($codes === []) {
            return [];
        }

        return Pep::query()->whereIn('objeto', $codes)->whereNotNull('disponivel')->pluck('disponivel', 'objeto')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function carriesBudget(string $pep): bool
    {
        return str_ends_with(trim($pep), self::BUDGET_PEP_SUFFIX);
    }

    private function isClosed(string $statusSist): bool
    {
        return str_starts_with($statusSist, 'ENT') || str_starts_with($statusSist, 'ENC');
    }
}
