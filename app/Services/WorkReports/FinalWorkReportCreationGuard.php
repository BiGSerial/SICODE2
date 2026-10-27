<?php

namespace App\Services\WorkReports;

use App\Models\{Note, Partial};
use Illuminate\Support\Collection;

/**
 * Convivência entre Parcial e Informe Final no mesmo escopo.
 *
 * - Parcial já aprovada (allow) e ainda aberta: o Final do mesmo escopo fica
 *   bloqueado até a parcial ser cancelada.
 * - Parcial ainda em aprovação do engenheiro (allow = false): o Final a cancela.
 * - Escopos distintos coexistem; 'general' cobre qualquer escopo.
 */
class FinalWorkReportCreationGuard
{
    /** @param array<int, string>|null $scopes null = qualquer escopo */
    public function openPartialFor(Note $note, ?array $scopes = null): ?Partial
    {
        return $this->partialsInScopes($note, $scopes)
            ->where('allow', true)
            ->first();
    }

    /** @param array<int, string>|null $scopes */
    public function canCreateFinalFor(Note $note, ?array $scopes = null): bool
    {
        return $this->openPartialFor($note, $scopes) === null;
    }

    /** Parcial aprovada que cobre todos os escopos resolvidos da Nota (bloqueio já na escolha da obra). */
    public function openPartialCoveringAllScopes(Note $note): ?Partial
    {
        $scopes = collect(app(WorkReportFinalScopeResolver::class)->resolve($note->type_note, $note->Orders))
            ->pluck('scope')
            ->all();

        foreach ($scopes as $scope) {
            if (!$this->openPartialFor($note, [$scope])) {
                return null;
            }
        }

        return $this->openPartialFor($note, $scopes);
    }

    /** @param array<int, string> $scopes */
    public function pendingPartialsFor(Note $note, array $scopes): Collection
    {
        return $this->partialsInScopes($note, $scopes)->where('allow', false)->values();
    }

    /**
     * Cancela as parciais ainda em aprovação do engenheiro nos escopos do Final.
     *
     * @param array<int, string> $scopes
     */
    public function cancelPendingPartialsFor(Note $note, array $scopes): int
    {
        return $this->pendingPartialsFor($note, $scopes)
            ->each(fn (Partial $partial) => $partial->forceFill([
                'complete'      => true,
                'allow'         => false,
                'deny'          => true,
                'decision_at'   => now(),
                'engineer_info' => 'Parcial cancelada automaticamente devido a entrada do informe final. (System Info)',
            ])->save())
            ->count();
    }

    /** @param array<int, string>|null $scopes */
    private function partialsInScopes(Note $note, ?array $scopes): Collection
    {
        $scopes = $scopes === null ? null : array_values(array_filter($scopes));

        return $note->Partials()
            ->where('complete', false)
            ->where('deny', false)
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (Partial $partial) use ($scopes) {
                if ($scopes === null || empty($scopes)) {
                    return true;
                }

                $partialScope = $partial->scope ?: WorkReportFinalScopeResolver::SCOPE_GENERAL;

                return $partialScope === WorkReportFinalScopeResolver::SCOPE_GENERAL
                    || in_array(WorkReportFinalScopeResolver::SCOPE_GENERAL, $scopes, true)
                    || in_array($partialScope, $scopes, true);
            })
            ->values();
    }
}
