<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, User};
use Illuminate\Support\{Collection, Str};
use Throwable;

/**
 * Orquestra operações em massa sem reproduzir a máquina de estados. Cada item
 * é entregue ao workflow em chamada própria: uma recusa não desfaz sucessos
 * anteriores e todos os eventos bem-sucedidos recebem o mesmo batch_id.
 */
class ClosureOrderBatchWorkflowService
{
    public const MAX_ITEMS = 100;

    public const ACTION_CLAIM = 'claim';

    public const ACTION_ASSIGN = 'assign';

    public const ACTION_TRANSFER = 'transfer';

    public const ACTION_RETURN_TO_POOL = 'return_to_pool';

    public const ACTION_START = 'start';

    public const ACTION_WAIT_FOR_RETURN = 'wait_for_return';

    public const ACTION_SET_PRIORITY = 'set_priority';

    /**
     * @var array<int, string>
     */
    private const ACTIONS = [
        self::ACTION_CLAIM,
        self::ACTION_ASSIGN,
        self::ACTION_TRANSFER,
        self::ACTION_RETURN_TO_POOL,
        self::ACTION_START,
        self::ACTION_WAIT_FOR_RETURN,
        self::ACTION_SET_PRIORITY,
    ];

    public function __construct(private ClosureOrderWorkflowService $workflow)
    {
    }

    /**
     * @param array<int, int|string> $stateIds
     * @param array<string, mixed> $payload
     */
    public function preview(array $stateIds, string $action, array $payload, User $actor): ClosureOrderBatchPreview
    {
        $stateIds     = $this->normalizeStateIds($stateIds);
        $action       = $this->normalizeAction($action);
        $payloadError = $this->payloadError($action, $payload);

        $states = ClosureOrderState::query()
            ->whereIn('id', $stateIds)
            ->get()
            ->keyBy('id');

        $items = $stateIds->map(function (int $stateId) use ($states, $payloadError) {
            /** @var ClosureOrderState|null $state */
            $state = $states->get($stateId);

            if (!$state) {
                return new ClosureOrderBatchItemResult(
                    stateId: $stateId,
                    orderId: null,
                    status: ClosureOrderBatchItemResult::STATUS_REFUSED,
                    reason: 'Item de Encerramento não encontrado.',
                );
            }

            if ($payloadError) {
                return new ClosureOrderBatchItemResult(
                    stateId: $stateId,
                    orderId: $state->order_id,
                    status: ClosureOrderBatchItemResult::STATUS_REFUSED,
                    reason: $payloadError,
                );
            }

            if (in_array($state->operational_status, ['CLOSED', 'CANCELLED'], true)) {
                return new ClosureOrderBatchItemResult(
                    stateId: $stateId,
                    orderId: $state->order_id,
                    status: ClosureOrderBatchItemResult::STATUS_REFUSED,
                    reason: 'Item de Encerramento já está terminal.',
                );
            }

            return new ClosureOrderBatchItemResult(
                stateId: $stateId,
                orderId: $state->order_id,
                status: ClosureOrderBatchItemResult::STATUS_ELIGIBLE,
            );
        });

        return new ClosureOrderBatchPreview($action, $items);
    }

    /**
     * @param array<int, int|string> $stateIds
     * @param array<string, mixed> $payload
     */
    public function execute(array $stateIds, string $action, array $payload, User $actor): ClosureOrderBatchResult
    {
        $preview = $this->preview($stateIds, $action, $payload, $actor);
        $batchId = (string) Str::uuid();
        $states  = ClosureOrderState::query()
            ->whereIn('id', $preview->eligible()->pluck('stateId'))
            ->get()
            ->keyBy('id');
        $items = collect();

        foreach ($preview->items as $previewItem) {
            if (!$previewItem->isEligible()) {
                $items->push($previewItem);

                continue;
            }

            /** @var ClosureOrderState|null $state */
            $state = $states->get($previewItem->stateId);

            if (!$state) {
                $items->push(new ClosureOrderBatchItemResult(
                    stateId: $previewItem->stateId,
                    orderId: $previewItem->orderId,
                    status: ClosureOrderBatchItemResult::STATUS_REFUSED,
                    reason: 'Item de Encerramento não encontrado durante a execução.',
                ));

                continue;
            }

            try {
                $this->executeState($state, $preview->action, $payload, $actor, $batchId);

                $items->push(new ClosureOrderBatchItemResult(
                    stateId: $state->id,
                    orderId: $state->order_id,
                    status: ClosureOrderBatchItemResult::STATUS_SUCCESS,
                ));
            } catch (Throwable $exception) {
                report($exception);

                $items->push(new ClosureOrderBatchItemResult(
                    stateId: $state->id,
                    orderId: $state->order_id,
                    status: ClosureOrderBatchItemResult::STATUS_REFUSED,
                    reason: $exception->getMessage(),
                ));
            }
        }

        return new ClosureOrderBatchResult($batchId, $preview->action, $items);
    }

    /**
     * @param array<int, int|string> $stateIds
     * @return Collection<int, int>
     */
    private function normalizeStateIds(array $stateIds): Collection
    {
        $stateIds = collect($stateIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($stateIds->isEmpty()) {
            throw new ClosureOrderBatchException('Selecione ao menos um item de Encerramento.');
        }

        if ($stateIds->count() > self::MAX_ITEMS) {
            throw new ClosureOrderBatchException('O lote de Encerramento aceita no máximo 100 itens.');
        }

        return $stateIds;
    }

    private function normalizeAction(string $action): string
    {
        $action = trim(strtolower($action));

        if (!in_array($action, self::ACTIONS, true)) {
            throw new ClosureOrderBatchException('Ação em massa de Encerramento inválida.');
        }

        return $action;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadError(string $action, array $payload): ?string
    {
        if ($action === self::ACTION_ASSIGN && empty($payload['company_id'])) {
            return 'Informe a empresa de destino para atribuir.';
        }

        if ($action === self::ACTION_TRANSFER && empty($payload['company_id'])) {
            return 'Informe a empresa de destino para transferir.';
        }

        if (in_array($action, [self::ACTION_TRANSFER, self::ACTION_RETURN_TO_POOL, self::ACTION_WAIT_FOR_RETURN], true)
            && empty(trim((string) ($payload['reason'] ?? '')))) {
            return 'Informe o motivo da ação.';
        }

        if ($action === self::ACTION_SET_PRIORITY && !array_key_exists('priority', $payload)) {
            return 'Informe a prioridade desejada.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function executeState(
        ClosureOrderState $state,
        string $action,
        array $payload,
        User $actor,
        string $batchId,
    ): void {
        match ($action) {
            self::ACTION_CLAIM  => $this->workflow->claim($state, $actor, $batchId),
            self::ACTION_ASSIGN => $this->workflow->assign(
                $state,
                $this->companyFromPayload($payload),
                !empty($payload['assignee_id']) ? $this->userFromPayload($payload, 'assignee_id') : null,
                $actor,
                $this->reasonFromPayload($payload),
                $batchId,
            ),
            self::ACTION_TRANSFER => $this->workflow->transfer(
                $state,
                $this->companyFromPayload($payload),
                $payload['assignee_id'] ?? null ? $this->userFromPayload($payload, 'assignee_id') : null,
                $actor,
                $this->reasonFromPayload($payload),
                $batchId,
            ),
            self::ACTION_RETURN_TO_POOL => $this->workflow->returnToPool(
                $state,
                $actor,
                $this->requiredReasonFromPayload($payload),
                $batchId,
            ),
            self::ACTION_START           => $this->workflow->start($state, $actor, $batchId),
            self::ACTION_WAIT_FOR_RETURN => $this->workflow->waitForReturn(
                $state,
                $actor,
                $this->requiredReasonFromPayload($payload),
                $batchId,
            ),
            self::ACTION_SET_PRIORITY => $this->workflow->setPriority(
                $state,
                (bool) $payload['priority'],
                $actor,
                $batchId,
            ),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function userFromPayload(array $payload, string $key): User
    {
        $user = User::query()->find($payload[$key] ?? null);

        if (!$user) {
            throw new ClosureOrderBatchException('Usuário de destino não encontrado.');
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function companyFromPayload(array $payload): Company
    {
        $company = Company::query()->find($payload['company_id'] ?? null);

        if (!$company) {
            throw new ClosureOrderBatchException('Empresa de destino não encontrada.');
        }

        return $company;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function reasonFromPayload(array $payload): ?string
    {
        $reason = trim((string) ($payload['reason'] ?? ''));

        return $reason === '' ? null : $reason;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredReasonFromPayload(array $payload): string
    {
        return (string) $this->reasonFromPayload($payload);
    }
}
