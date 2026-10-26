<?php

namespace App\Services\Partner;

use App\Models\Pep;
use App\Support\SicodeRules;
use Illuminate\Support\Collection;

/**
 * Bloqueio de orçamento do Informe de Obra (parcial e final): consulta o saldo (`peps.disponivel`, sincronizado do SQL Server)
 * dos PEPs das ordens abertas. Saldo zerado ou negativo bloqueia. Ordem sem PEP, ou PEP que não veio na sincronização, não bloqueia
 * (não há saldo para afirmar que acabou). Desligável por ruleset: `work_report.blocks_without_budget`.
 */
class WorkReportBudgetGuard
{
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

    /** Conteúdo do SweetAlert2 de bloqueio (mesmo formato dos demais avisos de bloqueio do informe). */
    public function swal(string $note, array $peps): array
    {
        $note = e($note);
        $list = e(implode(', ', $peps));

        return [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'OBRA BLOQUEADA',
            'html'     => "<div class='card'>
                <div class='card-body'>
                    <h5 class='card-title'>Obra: <strong>{$note}</strong></h5>
                    <div class='alert alert-danger'>
                        <h4 class='alert-heading fw-bold mb-2'>OBRA NÃO POSSUI ORÇAMENTO DISPONÍVEL</h4>
                        <strong>FAVOR ENTRAR EM CONTATO COM O ENGENHEIRO OU ESPECIALISTA RESPONSÁVEL PELA SUA REGIÃO.</strong>
                    </div>
                    <div class='small text-muted'>PEP sem saldo: {$list}</div>
                </div>
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
        $codes = Collection::make($orders)
            ->filter(fn ($order) => !$this->isClosed((string) $order->statusSist))
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

    private function isClosed(string $statusSist): bool
    {
        return str_starts_with($statusSist, 'ENT') || str_starts_with($statusSist, 'ENC');
    }
}
