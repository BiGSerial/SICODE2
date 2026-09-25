<?php

namespace App\Services\Dispatch;

use App\Models\Company;
use App\Models\Note;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use App\Services\D5\D5WorkflowService;
use App\Services\WorkReports\WorkReportFlowProductionLinker;
use App\Services\WorkReports\WorkReportFinalScopeOptions;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;

class DispatchWorkflowService
{
    public function __construct(
        private DispatchContextResolver $contextResolver,
        private DdAssignmentService $ddAssignment,
    )
    {
    }

    public function dispatchToCompanyStack(Note $note, Service $service, Company $company, User $actor, ?string $dd = null, array $finalScopes = [], ?int $workReportId = null, ?int $partialId = null): Production
    {
        if ($actor->contract) {
            throw new DispatchException('Usuario com contrato deve atribuir a atividade, nao enviar para pilha.');
        }

        if (!SicodeRules::allowsCompanyStackDispatch()) {
            throw new DispatchException('O envio para pilha da empresa nao esta habilitado para este ambiente.');
        }

        return $this->dispatch($note, $service, $company, null, $actor, $dd, $finalScopes, $workReportId, $partialId);
    }

    public function dispatchToUser(Note $note, Service $service, Company $company, User $targetUser, User $actor, ?string $dd = null, array $finalScopes = [], ?int $workReportId = null, ?int $partialId = null): Production
    {
        return $this->dispatch($note, $service, $company, $targetUser, $actor, $dd, $finalScopes, $workReportId, $partialId);
    }

    public function claimFromCompanyStack(Production $production, User $user): Production
    {
        if (!SicodeRules::partnerCanClaimCompanyStack()) {
            throw new DispatchException('A atribuicao pela pilha da empresa nao esta habilitada para este ambiente.');
        }

        return DB::transaction(function () use ($production, $user) {
            $production = Production::whereKey($production->id)->lockForUpdate()->firstOrFail();

            if ($production->completed) {
                throw new DispatchException('Esta atividade ja foi encerrada.');
            }

            if ($production->user_id) {
                throw new DispatchException('Esta atividade ja possui usuario atribuido.');
            }

            if (!SicodeRules::userCanAccessCompany($user, $production->company_id)) {
                throw new DispatchException('Usuario sem permissao para atribuir atividade desta empresa.');
            }

            $production->update([
                'user_id' => $user->id,
                'att_by' => $user->id,
                'att_at' => now(),
                'completed_at' => null,
                'completed' => false,
                'status' => 2,
            ]);

            $this->afterAssigned($production, $user, null);
            $this->timeline($production, $user, 'Assumiu a NOTA/OV da pilha da empresa', 2);

            return $production;
        });
    }

    public function assignProduction(Production $production, Company $company, User $targetUser, User $actor, bool $d5Return = false, array $finalScopes = [], ?int $workReportId = null, ?int $partialId = null): Production
    {
        return DB::transaction(function () use ($production, $company, $targetUser, $actor, $d5Return, $finalScopes, $workReportId, $partialId) {
            $production = Production::whereKey($production->id)->lockForUpdate()->firstOrFail();

            if ($production->completed) {
                throw new DispatchException('Esta atividade ja foi encerrada.');
            }

            if (!SicodeRules::userCanAccessCompany($actor, $production->company_id)) {
                throw new DispatchException('Usuario sem permissao para atribuir atividade desta empresa.');
            }

            if (!SicodeRules::userCanAccessCompany($actor, $company->id)) {
                throw new DispatchException('Usuario sem permissao para mover atividade para esta empresa.');
            }

            if (!$this->userBelongsToCompany($targetUser, $company->id)) {
                throw new DispatchException('Usuario destino nao pertence a empresa selecionada.');
            }

            $previousUserId = $production->user_id;
            $serviceKey = $this->contextResolver->serviceKey($production->Service);

            $updateData = [
                'user_id' => $targetUser->id,
                'company_id' => $company->id,
                'att_by' => $actor->id,
                'att_at' => now(),
                'completed_at' => null,
                'completed' => false,
                'status' => 2,
                'd5' => $d5Return,
            ];

            if ($serviceKey === 'payment' && !empty($finalScopes)) {
                $updateData['dfive'] = false;
            }

            $production->update($updateData);

            $this->afterAssigned($production, $actor, $previousUserId, $workReportId);
            $this->linkWorkReportFlow($production, $serviceKey, $finalScopes, $workReportId);
            $this->attachPartialContext($production, $partialId);
            $this->timeline($production, $actor, 'Atribuiu a NOTA/OV para: ' . $targetUser->name, 2);

            return $production;
        });
    }

    public function moveProductionToCompanyStack(Production $production, Company $company, User $actor, array $finalScopes = [], ?int $workReportId = null, ?int $partialId = null): Production
    {
        return DB::transaction(function () use ($production, $company, $actor, $finalScopes, $workReportId, $partialId) {
            $production = Production::whereKey($production->id)->lockForUpdate()->firstOrFail();

            if ($production->completed) {
                throw new DispatchException('Esta atividade ja foi encerrada.');
            }

            if (!SicodeRules::userCanAccessCompany($actor, $production->company_id)) {
                throw new DispatchException('Usuario sem permissao para mover atividade desta empresa.');
            }

            if (!SicodeRules::userCanAccessCompany($actor, $company->id)) {
                throw new DispatchException('Usuario sem permissao para mover atividade para esta empresa.');
            }

            $previousUserId = $production->user_id;
            $serviceKey = $this->contextResolver->serviceKey($production->Service);

            $updateData = [
                'user_id' => null,
                'company_id' => $company->id,
                'att_by' => null,
                'att_at' => null,
                'completed_at' => null,
                'completed' => false,
                'status' => 1,
            ];

            if ($serviceKey === 'payment' && !empty($finalScopes)) {
                $updateData['dfive'] = false;
            }

            $production->update($updateData);

            if ($previousUserId) {
                $five = $workReportId
                    ? WorkReport::query()->with('FiveNote')->whereKey($workReportId)->where('note_id', $production->note_id)->first()?->FiveNote
                    : $production->note?->LegacyFiveNote()->first();
                if ($five) {
                    app(D5WorkflowService::class)->onProductionUnassigned(
                        $five,
                        $production,
                        $actor->id,
                        $previousUserId
                    );
                }
            }

            $this->linkWorkReportFlow($production, $serviceKey, $finalScopes, $workReportId);
            $this->attachPartialContext($production, $partialId);
            $this->timeline($production, $actor, 'Moveu a NOTA/OV para a pilha da empresa: ' . $company->name, 1);

            return $production;
        });
    }

    public function unassignProduction(Production $production, User $actor): Production
    {
        return DB::transaction(function () use ($production, $actor) {
            $production = Production::whereKey($production->id)->lockForUpdate()->firstOrFail();

            if ($production->completed) {
                throw new DispatchException('Esta atividade ja foi encerrada.');
            }

            if (!SicodeRules::userCanAccessCompany($actor, $production->company_id)) {
                throw new DispatchException('Usuario sem permissao para desatribuir atividade desta empresa.');
            }

            $previousUserId = $production->user_id;

            $production->update([
                'user_id' => null,
                'status' => 1,
            ]);

            $five = $production->note?->FiveNote;
            if ($five && $previousUserId) {
                app(D5WorkflowService::class)->onProductionUnassigned(
                    $five,
                    $production,
                    $actor->id,
                    $previousUserId
                );
            }

            $this->timeline($production, $actor, 'Desatribuiu a NOTA/OV da pilha individual', 1);

            return $production;
        });
    }

    private function dispatch(Note $note, Service $service, Company $company, ?User $targetUser, User $actor, ?string $dd, array $finalScopes, ?int $explicitWorkReportId = null, ?int $partialId = null): Production
    {
        return DB::transaction(function () use ($note, $service, $company, $targetUser, $actor, $dd, $finalScopes, $explicitWorkReportId, $partialId) {
            $note = Note::whereKey($note->id)->lockForUpdate()->firstOrFail();
            $note->loadMissing(['FiveNote', 'Partials', 'WorkForm', 'Productions']);

            if (!SicodeRules::userCanAccessCompany($actor, $company->id)) {
                throw new DispatchException('Usuario sem permissao para despachar para esta empresa.');
            }

            if ($targetUser && !$this->userBelongsToCompany($targetUser, $company->id)) {
                throw new DispatchException('Usuario destino nao pertence a empresa selecionada.');
            }

            $serviceKey = $this->contextResolver->serviceKey($service);
            $targetWorkReportIds = [];
            $explicitWorkReport = null;

            if ($partialId) {
                $partial = $note->Partials->firstWhere('id', $partialId);
                $partialIsAvailable = $partial
                    && $partial->allow
                    && !$partial->deny
                    && match ($serviceKey) {
                        'supervision' => !$partial->supervision,
                        'payment' => $partial->supervision && !$partial->payment,
                        default => true,
                    };

                if (!$partialIsAvailable) {
                    throw new DispatchException('O informe parcial selecionado nao esta disponivel para este servico.');
                }
            }

            if ($explicitWorkReportId) {
                $explicitWorkReport = WorkReport::query()
                    ->whereKey($explicitWorkReportId)
                    ->where('note_id', $note->id)
                    ->where('canceled', false)
                    ->with(['Note', 'Orders', 'FiveNote'])
                    ->first();

                if (!$explicitWorkReport) {
                    throw new DispatchException('O informe selecionado nao pertence a esta Nota/OV ou esta cancelado.');
                }

                // O contexto selecionado precisa continuar disponível para o
                // BlockEvaluator depois que a Note é recarregada na transação.
                $note->setAttribute('dispatch_work_report_id', $explicitWorkReportId);
                $note->setRelation('WorkForm', $explicitWorkReport);
                $targetWorkReportIds = [$explicitWorkReportId];
            }

            if (!$explicitWorkReportId && !$this->isD5Dispatch($note, $serviceKey, $finalScopes)) {
                $targetWorkReportIds = $this->targetWorkReportIdsForDispatch($note, $serviceKey, $finalScopes);

                if (count($targetWorkReportIds) === 1) {
                    $note->setAttribute('dispatch_work_report_id', $targetWorkReportIds[0]);
                }
            }

            if (!$explicitWorkReportId && count($targetWorkReportIds) > 1 && !$this->isD5Dispatch($note, $serviceKey, $finalScopes)) {
                $productions = collect($targetWorkReportIds)
                    ->map(function (int $targetWorkReportId) use ($note, $service, $company, $targetUser, $actor, $dd, $finalScopes, $partialId) {
                        return $this->dispatch(
                            $note,
                            $service,
                            $company,
                            $targetUser,
                            $actor,
                            $dd,
                            $this->finalScopesForWorkReport($note, $targetWorkReportId, $finalScopes),
                            $targetWorkReportId,
                            $partialId
                        );
                    });

                return $productions->first();
            }

            $context = $this->contextResolver->for($note, $service);
            $isScopedPaymentDispatch = $serviceKey === 'payment' && !empty($targetWorkReportIds);

            if ($explicitWorkReportId && $this->isD5Dispatch($note, $serviceKey, $finalScopes, $explicitWorkReportId)) {
                $legacyFive = $note->LegacyFiveNote()->first();
                $legacyFive?->update(['work_report_id' => $explicitWorkReportId]);
            }

            $dd = $this->normalizeDd($dd);
            if ($context['requires_dd'] && !$dd) {
                throw new DispatchException('Todas as Notas/OVs precisam estar associadas a uma Nota DD.');
            }

            if ($actor->contract) {
                if (!$targetUser) {
                    throw new DispatchException('Usuario com contrato deve atribuir a atividade, nao enviar para pilha.');
                }

                $stackProduction = $this->openCompanyStackProduction($note, $service, $company, $explicitWorkReportId, $partialId);

                if (!$stackProduction && !($context['service_key'] === 'payment' && (string) $targetUser->id === (string) $actor->id)) {
                    throw new DispatchException('Nao existe atividade aberta na pilha desta empresa para atribuir.');
                }

                if ($stackProduction) {
                    return $this->dispatchFromExistingCompanyStack($stackProduction, $note, $targetUser, $actor, $dd, $finalScopes, $explicitWorkReportId, $partialId);
                }
            }

            if (!$context['can_dispatch']) {
                throw new DispatchException('Existe uma atividade em andamento para uma ou mais Notas/OVs.');
            }

            $this->assertNoOpenDispatch($note, $service, $context['service_key'], $finalScopes, $explicitWorkReportId, $partialId);

            $production = Production::create([
                'note_id' => $note->id,
                'service_id' => $service->uuid,
                'user_id' => $targetUser?->id,
                'company_id' => $company->id,
                'dispatch_by' => $actor->id,
                'att_by' => $targetUser ? $actor->id : null,
                'dt_note' => $note->dt_status,
                'status_note' => $this->productionStatusNote($note),
                'dispatch_at' => now(),
                'att_at' => $targetUser ? now() : null,
                'status' => $targetUser ? 2 : 1,
                'centroTrab' => $note->centerjob,
                'partial' => (bool) ($partialId || ($context['is_partial'] ?? false)),
                'dfive' => !$isScopedPaymentDispatch
                    && ($this->isD5Dispatch($note, $serviceKey, $finalScopes, $explicitWorkReportId)
                        || (bool) ($context['is_d5_fiscalization'] ?? false)),
            ]);

            $this->attachPartialContext($production, $partialId);

            if ($dd) {
                $this->attachDd($note, $production, $dd);
            }

            $this->linkWorkReportFlow($production, $context['service_key'], $finalScopes, $explicitWorkReportId);

            if ($targetUser) {
                $this->afterAssigned($production, $actor, null, $explicitWorkReportId);
            }

            $this->timeline(
                $production,
                $actor,
                $targetUser
                    ? 'Atribuiu a NOTA/OV para: ' . $targetUser->name
                    : 'Despachou a NOTA/OV para: ' . $company->name,
                $targetUser ? 2 : 1
            );

            return $production;
        });
    }

    private function assertNoOpenDispatch(Note $note, Service $service, string $serviceKey, array $finalScopes, ?int $explicitWorkReportId = null, ?int $partialId = null): void
    {
        $query = Production::where('note_id', $note->id)
            ->where('service_id', $service->uuid)
            ->where('completed', false)
            ->where('confirmed', false)
            ->lockForUpdate();

        $stage = $this->workReportFlowStageForService($serviceKey);
        $workReportIds = $explicitWorkReportId
            ? [$explicitWorkReportId]
            : $this->targetWorkReportIdsForDispatch($note, $serviceKey, $finalScopes);

        if ($partialId) {
            $query->whereHas('partialInforms', fn ($q) => $q->whereKey($partialId));
        } elseif ($stage && !empty($workReportIds)) {
            $query->whereHas('WorkReportFlowProductions', function ($q) use ($stage, $workReportIds) {
                $q->where('stage', $stage)
                    ->where('is_current', true)
                    ->whereIn('work_report_id', $workReportIds);
            });
        }

        $hasOpen = $query->exists();

        if ($hasOpen) {
            throw new DispatchException('Ja existe atividade aberta para esta Nota/OV neste servico.');
        }
    }

    private function attachPartialContext(Production $production, ?int $partialId): void
    {
        if (!$partialId) {
            return;
        }

        $production->update(['partial' => true]);
        $production->partialInforms()->syncWithoutDetaching([$partialId]);
    }

    private function workReportFlowStageForService(string $serviceKey): ?string
    {
        return match ($serviceKey) {
            'supervision' => WorkReportFlowProduction::STAGE_FISCALIZATION,
            'payment' => WorkReportFlowProduction::STAGE_PAYMENT,
            default => null,
        };
    }

    private function finalScopesForWorkReport(Note $note, int $workReportId, array $requestedScopes): array
    {
        $workReport = $note->relationLoaded("WorkForms")
            ? $note->WorkForms->firstWhere("id", $workReportId)
            : WorkReport::query()->whereKey($workReportId)->where("note_id", $note->id)->with("Orders")->first();

        if (!$workReport) {
            return $requestedScopes;
        }

        $reportScopes = collect($workReport->finalScopePayloads())
            ->pluck("scope")
            ->filter()
            ->values();
        $requested = collect($requestedScopes)->filter()->values();
        $scopes = $requested->intersect($reportScopes)->values();

        return ($scopes->isNotEmpty() ? $scopes : $reportScopes)->all();
    }


    private function targetWorkReportIdsForDispatch(Note $note, string $serviceKey, array $finalScopes): array
    {
        if (!$this->workReportFlowStageForService($serviceKey)) {
            return [];
        }

        if ($this->isD5Dispatch($note, $serviceKey, $finalScopes)) {
            return [];
        }

        $scopes = collect($finalScopes)
            ->map(fn ($scope) => (string) $scope)
            ->filter()
            ->unique()
            ->values();

        if ($scopes->isEmpty()) {
            $availableScopes = app(WorkReportFinalScopeOptions::class)->forNote($note);

            if (count($availableScopes) === 1) {
                $scopes = collect([$availableScopes[0]['scope']]);
            }
        }

        return $scopes
            ->map(fn (string $scope) => app(WorkReportFlowProductionLinker::class)
                ->resolveCurrentFinalWorkReport((int) $note->id, $scope)?->id)
            ->filter()
            ->map(fn ($workReportId) => (int) $workReportId)
            ->unique()
            ->values()
            ->all();
    }

    private function isD5Dispatch(Note $note, string $serviceKey, array $finalScopes = [], ?int $workReportId = null): bool
    {
        $fiveNote = $workReportId
            ? (WorkReport::query()->with('FiveNote')->whereKey($workReportId)->where('note_id', $note->id)->first()?->FiveNote ?? $note->LegacyFiveNote()->first())
            : $note->LegacyFiveNote()->first();

        return match ($serviceKey) {
            'supervision' => (bool) (
                $fiveNote
                && $fiveNote->is_completed
                && !$fiveNote->is_supervisioned
            ),
            'payment' => (bool) $fiveNote && empty($finalScopes),
            default => false,
        };
    }

    private function productionStatusNote(Note $note): ?int
    {
        $status = trim((string) $note->nstats);

        if ($status === '' || !is_numeric($status)) {
            return null;
        }

        return (int) $status;
    }

    private function openCompanyStackProduction(Note $note, Service $service, Company $company, ?int $workReportId = null, ?int $partialId = null): ?Production
    {
        return Production::where('note_id', $note->id)
            ->where('service_id', $service->uuid)
            ->where('company_id', $company->id)
            ->whereNull('user_id')
            ->where('completed', false)
            ->where('confirmed', false)
            ->when($workReportId, function ($query) use ($service, $workReportId) {
                $stage = $this->workReportFlowStageForService($this->contextResolver->serviceKey($service));

                return $stage
                    ? $query->whereHas("WorkReportFlowProductions", fn ($link) => $link
                        ->where("work_report_id", $workReportId)
                        ->where("stage", $stage)
                        ->where("is_current", true))
                    : $query;
            })
            ->when($partialId, fn ($query) => $query->whereHas("partialInforms", fn ($partial) => $partial->whereKey($partialId)))
            ->lockForUpdate()
            ->first();
    }

    private function dispatchFromExistingCompanyStack(
        Production $production,
        Note $note,
        ?User $targetUser,
        User $actor,
        ?string $dd,
        array $finalScopes = [],
        ?int $workReportId = null,
        ?int $partialId = null
    ): Production {
        if ($dd) {
            $this->attachDd($note, $production, $dd);
        }

        if (!$targetUser) {
            $this->attachPartialContext($production, $partialId);
            $this->linkWorkReportFlow($production, $this->contextResolver->serviceKey($production->Service), $finalScopes, $workReportId);
            $this->timeline($production, $actor, 'Manteve a NOTA/OV na pilha da empresa', 1);

            return $production;
        }

        $production->update([
            'user_id' => $targetUser->id,
            'att_by' => $actor->id,
            'att_at' => now(),
            'completed_at' => null,
            'completed' => false,
            'status' => 2,
        ]);

        $this->afterAssigned($production, $actor, null, $workReportId);
        $this->attachPartialContext($production, $partialId);
        $this->linkWorkReportFlow($production, $this->contextResolver->serviceKey($production->Service), $finalScopes, $workReportId);
        $this->timeline($production, $actor, 'Atribuiu a NOTA/OV para: ' . $targetUser->name, 2);

        return $production;
    }

    private function attachDd(Note $note, Production $production, string $dd): void
    {
        $this->ddAssignment->assign($note, $production, $dd);
    }

    private function linkWorkReportFlow(Production $production, string $serviceKey, array $finalScopes = [], ?int $workReportId = null): void
    {
        if ($production->dfive) {
            if ($serviceKey === 'supervision') {
                app(WorkReportFlowProductionLinker::class)->linkD5FiscalizationToNetwork(
                    $production,
                    'dispatch_d5_fiscalization',
                    [],
                    $workReportId
                );
            }

            return;
        }

        if ($serviceKey === 'supervision') {
            if (empty($finalScopes)) {
                $production->loadMissing('Note');
                $availableScopes = $production->Note
                    ? app(WorkReportFinalScopeOptions::class)->forNote($production->Note)
                    : [];
                if (count($availableScopes) > 1) {
                    return;
                }

                $finalScopes = count($availableScopes) === 1
                    ? [$availableScopes[0]['scope']]
                    : [WorkReportFlowProduction::SCOPE_GENERAL];
            }

            foreach ($finalScopes as $finalScope) {
                if ($workReportId) {
                    app(WorkReportFlowProductionLinker::class)->linkFiscalizationForWorkReport($production, $workReportId, 'dispatch_workflow', [], $finalScope);
                } else {
                    app(WorkReportFlowProductionLinker::class)->linkFiscalization($production, 'dispatch_workflow', [], $finalScope);
                }
            }

            return;
        }

        if ($serviceKey === 'payment') {
            $linker = app(WorkReportFlowProductionLinker::class);

            if ($workReportId) {
                $linker->linkPaymentForWorkReport(
                    $production,
                    $workReportId,
                    $finalScopes,
                    'dispatch_workflow'
                );
            } else {
                $linker->linkPaymentForScopes($production, $finalScopes, 'dispatch_workflow');
            }

            return;
        }

        if ($serviceKey === 'publication') {
            $production->loadMissing('Note');
            $finalScopes = !empty($finalScopes)
                ? $finalScopes
                : collect($production->Note ? app(WorkReportFinalScopeOptions::class)->forNote($production->Note, true) : [])
                    ->pluck('scope')
                    ->all();

            foreach ($finalScopes as $finalScope) {
                $workReport = $workReportId
                    ? WorkReport::query()
                        ->whereKey($workReportId)
                        ->where('note_id', $production->note_id)
                        ->where('canceled', false)
                        ->first()
                    : app(WorkReportFlowProductionLinker::class)
                        ->resolveCurrentFinalWorkReport((int) $production->note_id, $finalScope);

                if (!$workReport) {
                    continue;
                }

                app(WorkReportFlowProductionLinker::class)->linkPublicationForWorkReport(
                    $production,
                    $workReport,
                    'dispatch_workflow',
                    [],
                    $finalScope
                );
            }
        }
    }

    private function afterAssigned(Production $production, User $actor, ?string $previousUserId, ?int $workReportId = null): void
    {
        $five = $workReportId
            ? WorkReport::query()->with('FiveNote')->whereKey($workReportId)->where('note_id', $production->note_id)->first()?->FiveNote
            : $production->note?->LegacyFiveNote()->first();
        if (!$five) {
            return;
        }

        $five->productions()->syncWithoutDetaching([$production->id]);

        app(D5WorkflowService::class)->onProductionAssigned(
            $five,
            $production,
            $actor->id,
            $previousUserId
        );
    }

    private function timeline(Production $production, User $actor, string $info, int $status): void
    {
        Notetimeline::create([
            'note_id' => $production->id,
            'service_id' => $production->service_id,
            'user_id' => $actor->id,
            'info' => "Usuario {$actor->name} {$info}",
            'status' => $status,
            'productionId' => $production->id,
        ]);
    }

    private function normalizeDd(?string $dd): ?string
    {
        return $this->ddAssignment->normalize($dd);
    }

    private function userBelongsToCompany(User $user, string $companyId): bool
    {
        return collect(SicodeRules::visibleCompanyIdsFor($user))->contains((string) $companyId)
            || $user->Companies()->where('companies.id', $companyId)->exists();
    }
}
