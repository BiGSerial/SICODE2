<?php

namespace App\Services\Partner;

use App\Models\{Note, Partial};
use App\Services\WorkReports\WorkReportFinalScopeResolver;
use Illuminate\Support\Collection;

class PartialScopeAvailabilityService
{
    public function for(Note $note): array
    {
        $note->loadMissing(['Orders', 'WorkForms.Note', 'WorkForms.Orders', 'Partials.Orders']);

        $scopePayloads   = app(WorkReportFinalScopeResolver::class)->resolve($note->type_note, $note->Orders);
        $completedScopes = $note->WorkForms
            ->flatMap(fn ($workReport) => collect($workReport->finalScopePayloads())->pluck('scope'))
            ->unique()
            ->values();

        return collect($scopePayloads)
            ->map(function (array $payload) use ($note, $completedScopes): array {
                $scope  = (string) $payload['scope'];
                $orders = collect($payload['orders'])
                    ->map(fn (array $order) => $note->Orders->firstWhere('id', $order['id']))
                    ->filter()
                    ->values();

                $openOrders = $orders
                    ->filter(fn ($order) => !$this->isClosedOrder($order->statusSist))
                    ->values();

                $activePartial = $this->activePartialForScope($note->Partials, $scope);
                $completed     = $completedScopes->contains($scope);

                $reason = null;

                if ($completed) {
                    $reason = 'Informe de conclusão já emitido para este escopo';
                } elseif ($openOrders->isEmpty()) {
                    $reason = 'Não há ordens abertas neste escopo';
                } elseif ($activePartial) {
                    $reason = 'Parcial #' . $activePartial->id . ' ainda está em andamento';
                }

                return [
                    'scope'         => $scope,
                    'label'         => $this->label($scope),
                    'orders'        => $orders,
                    'open_orders'   => $openOrders,
                    'partial_count' => $note->Partials->where('scope', $scope)->count(),
                    'blocked'       => $reason !== null,
                    'reason'        => $reason,
                ];
            })
            ->values()
            ->all();
    }

    public function findAvailableScope(Note $note, string $scope): ?array
    {
        return collect($this->for($note))
            ->first(fn (array $row) => $row['scope'] === $scope && !$row['blocked']);
    }

    public function label(string $scope): string
    {
        return match ($scope) {
            WorkReportFinalScopeResolver::SCOPE_NETWORK    => 'Rede',
            WorkReportFinalScopeResolver::SCOPE_CONNECTION => 'Ligação',
            default                                        => 'Geral',
        };
    }

    private function activePartialForScope(Collection $partials, string $scope): ?Partial
    {
        return $partials
            ->sortByDesc('created_at')
            ->first(fn (Partial $partial) => !$partial->deny
                && !$partial->complete
                && ($partial->scope === 'general' || $partial->scope === $scope));
    }

    private function isClosedOrder(?string $status): bool
    {
        return str_starts_with((string) $status, 'ENT')
            || str_starts_with((string) $status, 'ENC');
    }
}
