<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Models\Closure\ClosureOrderState;
use App\Models\Order;
use App\Services\Closure\{ClosureDispatchDirectory, ClosureDispatchException, ClosureDispatchService, ClosureMultiDispatchResult, ClosureNotifier};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\Component;

/**
 * Modal de despacho de ORDENS do Encerramento.
 *
 * A abertura é 100% no cliente (Alpine + Bootstrap): resumo montado dos data-attributes das linhas e selects
 * populados pelo JSON embutido (ClosureDispatchDirectory). Este componente só atende duas chamadas ($wire.call),
 * ambas sem re-renderizar: `preview()` (elegibilidade, em segundo plano) e `confirm()` (valida TUDO de novo).
 */
class DispatchModal extends Component
{
    public const MODAL_ID = 'closure_dispatch_modal';

    public const MAX_ORDERS = 1000;

    public function mount(): void
    {
        $this->authorizeDispatch();
    }

    /**
     * Prévia de elegibilidade. Empresa/usuário inválidos não quebram a prévia: ela só avalia as recusas
     * independentes do destino (a confirmação é que exige destino válido).
     *
     * @param array<int, int|string> $orderIds
     * @return array{rows: array<int, array>, error: ?string}
     */
    public function preview(array $orderIds, ?string $companyId = null, ?string $assigneeId = null): array
    {
        $this->authorizeDispatch();
        $this->skipRender();

        $ids = $this->normalizeIds($orderIds);

        if (count($ids) > self::MAX_ORDERS) {
            return ['rows' => [], 'error' => 'Selecione no máximo ' . self::MAX_ORDERS . ' Ordens por despacho.'];
        }

        $company  = app(ClosureDispatchDirectory::class)->company($companyId);
        $assignee = $company ? app(ClosureDispatchDirectory::class)->assignee($assigneeId, $company) : null;

        return ['rows' => $this->listQuery()->eligibility($ids, $company, $assignee), 'error' => null];
    }

    /**
     * Confirma o despacho. Nada do cliente é confiável: gate, empresa/usuário habilitados e elegibilidade
     * são revalidados aqui; só Ordens elegíveis chegam ao ClosureDispatchService.
     *
     * @param array<int, int|string> $orderIds
     * @return array{processed: bool, error: ?string, receipt: array<int, array>, rows: array<int, array>}
     */
    public function confirm(array $orderIds, ?string $companyId, ?string $assigneeId, bool $onlyEligible = false): array
    {
        $this->authorizeDispatch();
        $this->skipRender();

        $ids = $this->normalizeIds($orderIds);

        if ($ids === []) {
            return $this->failure('Nenhuma Ordem selecionada.');
        }

        if (count($ids) > self::MAX_ORDERS) {
            return $this->failure('Selecione no máximo ' . self::MAX_ORDERS . ' Ordens por despacho.');
        }

        $directory = app(ClosureDispatchDirectory::class);

        if (!$companyId) {
            return $this->failure('Informe a empresa de destino antes de continuar.');
        }

        $company = $directory->company($companyId);

        if (!$company) {
            return $this->failure('A empresa de destino não está habilitada no Encerramento. Escolha outra empresa.');
        }

        $assignee = null;

        if ($assigneeId) {
            $assignee = $directory->assignee($assigneeId, $company);

            if (!$assignee) {
                return $this->failure('O usuário escolhido não está habilitado na empresa de destino.');
            }
        }

        $rows = $this->listQuery()->eligibility($ids, $company, $assignee);

        if (count($rows) !== count($ids)) {
            return $this->failure('Há Ordens inexistentes na seleção. Feche e selecione novamente.', $rows);
        }

        $ineligible = collect($rows)->where('eligible', false);

        if ($ineligible->isNotEmpty() && !$onlyEligible) {
            return $this->failure('Há Ordens recusadas na prévia. Marque "Despachar só as elegíveis" ou remova-as da seleção.', $rows);
        }

        $dispatchIds = collect($rows)->where('eligible', true)->pluck('id')->all();

        if ($dispatchIds === []) {
            return $this->failure('Nenhuma Ordem elegível para este destino.', $rows);
        }

        try {
            $result = app(ClosureDispatchService::class)->dispatchOrders($dispatchIds, $company, $assignee, auth()->user());
        } catch (ClosureDispatchException $exception) {
            return $this->failure($exception->getMessage(), $rows);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->failure('Não foi possível concluir o despacho agora. Tente novamente em alguns minutos.', $rows);
        }

        if ($assignee) {
            $this->notifyAssigned($result->dispatchedOrderIds()->all());
        }

        $done    = $result->dispatchedOrderIds()->count();
        $already = $result->alreadyDispatchedOrderIds()->count();
        $refused = $result->refusedOrderIds()->count();

        $this->dispatchBrowserEvent('notification:toast', [
            'icon'  => $refused === 0 ? 'success' : ($done + $already > 0 ? 'warning' : 'error'),
            'title' => $refused === 0 ? 'Despacho concluído' : 'Despacho concluído com recusas',
            'text'  => "{$done} despachada(s), {$already} já despachada(s), {$refused} recusada(s).",
        ]);

        // A lista mantém selecionadas apenas as recusadas para nova tentativa.
        $this->emit('closureOrdersDispatched', $result->refusedOrderIds()->all());

        return ['processed' => true, 'error' => null, 'receipt' => $this->buildReceipt($result), 'rows' => $rows];
    }

    public function render()
    {
        return view('livewire.closure.dispatch.dispatch-modal', [
            'directory' => app(ClosureDispatchDirectory::class)->all(),
        ]);
    }

    /** @return array<int, int> */
    private function normalizeIds(array $orderIds): array
    {
        return collect($orderIds)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->filter(fn (int $id) => $id > 0)->unique()->values()->all();
    }

    private function failure(string $message, array $rows = []): array
    {
        return ['processed' => false, 'error' => $message, 'receipt' => [], 'rows' => $rows];
    }

    private function buildReceipt(ClosureMultiDispatchResult $result): array
    {
        $ordens = Order::query()->with('Note:id,note')->whereIn('id', collect($result->orders)->pluck('order_id'))->get()->keyBy('id');

        return collect($result->orders)->map(fn (array $item) => [
            'order_id' => $item['order_id'],
            'ordem'    => $ordens->get($item['order_id'])?->ordem,
            'note'     => $ordens->get($item['order_id'])?->Note?->note,
            'type'     => $item['meta_type'],
            'status'   => $item['status'],
            'message'  => $item['message'],
        ])->values()->all();
    }

    /** Uma notificação por Ordem atribuída com sucesso (o service de despacho não notifica). */
    private function notifyAssigned(array $orderIds): void
    {
        if ($orderIds === []) {
            return;
        }

        $notifier = app(ClosureNotifier::class);

        foreach (ClosureOrderState::query()->whereIn('order_id', $orderIds)->get() as $state) {
            try {
                $notifier->orderAssigned($state, auth()->user());
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    private function listQuery(): ClosureDispatchListQuery
    {
        return app(ClosureDispatchListQuery::class);
    }

    private function authorizeDispatch(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }
}
