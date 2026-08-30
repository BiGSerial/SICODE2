<?php

namespace App\Services\Dispatch;

use App\Models\Company;
use App\Models\Note;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkReportFlowProduction;
use App\Models\Wpa;
use App\Services\D5\D5WorkflowService;
use App\Services\WorkReports\WorkReportFlowProductionLinker;
use App\Services\WorkReports\WorkReportFinalScopeOptions;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;

class DispatchWorkflowService
{
    public function __construct(private DispatchContextResolver $contextResolver)
    {
    }

    public function dispatchToCompanyStack(Note $note, Service $service, Company $company, User $actor, ?string $dd = null, array $finalScopes = []): Production
    {
        if ($actor->contract) {
            throw new DispatchException('Usuario com contrato deve atribuir a atividade, nao enviar para pilha.');
        }

        if (!SicodeRules::allowsCompanyStackDispatch()) {
            throw new DispatchException('O envio para pilha da empresa nao esta habilitado para este ambiente.');
        }

        return $this->dispatch($note, $service, $company, null, $actor, $dd, $finalScopes);
    }

    public function dispatchToUser(Note $note, Service $service, Company $company, User $targetUser, User $actor, ?string $dd = null, array $finalScopes = []): Production
    {
        return $this->dispatch($note, $service, $company, $targetUser, $actor, $dd, $finalScopes);
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

    public function assignProduction(Production $production, Company $company, User $targetUser, User $actor, bool $d5Return = false): Production
    {
        return DB::transaction(function () use ($production, $company, $targetUser, $actor, $d5Return) {
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

            $production->update([
                'user_id' => $targetUser->id,
                'company_id' => $company->id,
                'att_by' => $actor->id,
                'att_at' => now(),
                'completed_at' => null,
                'completed' => false,
                'status' => 2,
                'd5' => $d5Return,
            ]);

            $this->afterAssigned($production, $actor, $previousUserId);
            $this->timeline($production, $actor, 'Atribuiu a NOTA/OV para: ' . $targetUser->name, 2);

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

    private function dispatch(Note $note, Service $service, Company $company, ?User $targetUser, User $actor, ?string $dd, array $finalScopes): Production
    {
        return DB::transaction(function () use ($note, $service, $company, $targetUser, $actor, $dd, $finalScopes) {
            $note = Note::whereKey($note->id)->lockForUpdate()->firstOrFail();
            $note->loadMissing(['FiveNote', 'Partials', 'WorkForm', 'Productions']);

            if (!SicodeRules::userCanAccessCompany($actor, $company->id)) {
                throw new DispatchException('Usuario sem permissao para despachar para esta empresa.');
            }

            if ($targetUser && !$this->userBelongsToCompany($targetUser, $company->id)) {
                throw new DispatchException('Usuario destino nao pertence a empresa selecionada.');
            }

            $context = $this->contextResolver->for($note, $service);

            $dd = $this->normalizeDd($dd);
            if ($context['requires_dd'] && !$dd) {
                throw new DispatchException('Todas as Notas/OVs precisam estar associadas a uma Nota DD.');
            }

            if ($actor->contract) {
                if (!$targetUser) {
                    throw new DispatchException('Usuario com contrato deve atribuir a atividade, nao enviar para pilha.');
                }

                $stackProduction = $this->openCompanyStackProduction($note, $service, $company);

                if (!$stackProduction) {
                    throw new DispatchException('Nao existe atividade aberta na pilha desta empresa para atribuir.');
                }

                return $this->dispatchFromExistingCompanyStack($stackProduction, $note, $targetUser, $actor, $dd);
            }

            if (!$context['can_dispatch']) {
                throw new DispatchException('Existe uma atividade em andamento para uma ou mais Notas/OVs.');
            }

            $this->assertNoOpenDispatch($note, $service);

            $production = Production::create([
                'note_id' => $note->id,
                'service_id' => $service->uuid,
                'user_id' => $targetUser?->id,
                'company_id' => $company->id,
                'dispatch_by' => $actor->id,
                'att_by' => $targetUser ? $actor->id : null,
                'dt_note' => $note->dt_status,
                'status_note' => $note->nstats,
                'dispatch_at' => now(),
                'att_at' => $targetUser ? now() : null,
                'status' => $targetUser ? 2 : 1,
                'centroTrab' => $note->centerjob,
                'partial' => (bool) ($context['is_partial'] ?? false),
                'dfive' => (bool) ($context['is_d5_fiscalization'] ?? false),
            ]);

            if ($dd) {
                $this->attachDd($note, $production, $dd);
            }

            $this->linkWorkReportFlow($production, $context['service_key'], $finalScopes);

            if ($targetUser) {
                $this->afterAssigned($production, $actor, null);
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

    private function assertNoOpenDispatch(Note $note, Service $service): void
    {
        $hasOpen = Production::where('note_id', $note->id)
            ->where('service_id', $service->uuid)
            ->where('completed', false)
            ->where('confirmed', false)
            ->lockForUpdate()
            ->exists();

        if ($hasOpen) {
            throw new DispatchException('Ja existe atividade aberta para esta Nota/OV neste servico.');
        }
    }

    private function openCompanyStackProduction(Note $note, Service $service, Company $company): ?Production
    {
        return Production::where('note_id', $note->id)
            ->where('service_id', $service->uuid)
            ->where('company_id', $company->id)
            ->whereNull('user_id')
            ->where('completed', false)
            ->where('confirmed', false)
            ->lockForUpdate()
            ->first();
    }

    private function dispatchFromExistingCompanyStack(
        Production $production,
        Note $note,
        ?User $targetUser,
        User $actor,
        ?string $dd
    ): Production {
        if ($dd) {
            $this->attachDd($note, $production, $dd);
        }

        if (!$targetUser) {
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

        $this->afterAssigned($production, $actor, null);
        $this->timeline($production, $actor, 'Atribuiu a NOTA/OV para: ' . $targetUser->name, 2);

        return $production;
    }

    private function attachDd(Note $note, Production $production, string $dd): void
    {
        $existing = Wpa::where('dd', $dd)->lockForUpdate()->first();

        if ($existing && (string) $existing->note_id !== (string) $note->id) {
            throw new DispatchException("DD {$dd} ja foi associada a outra Nota/OV.");
        }

        if ($existing) {
            $existing->update([
                'production_id' => $production->id,
                'service_id' => $production->service_id,
            ]);

            return;
        }

        Wpa::create([
            'production_id' => $production->id,
            'note_id' => $note->id,
            'service_id' => $production->service_id,
            'dd' => $dd,
        ]);
    }

    private function linkWorkReportFlow(Production $production, string $serviceKey, array $finalScopes = []): void
    {
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
                app(WorkReportFlowProductionLinker::class)->linkFiscalization($production, 'dispatch_workflow', [], $finalScope);
            }
        }
    }

    private function afterAssigned(Production $production, User $actor, ?string $previousUserId): void
    {
        $five = $production->note?->FiveNote;
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
        $dd = trim((string) $dd);

        return $dd !== '' ? $dd : null;
    }

    private function userBelongsToCompany(User $user, string $companyId): bool
    {
        if ((string) $user->company_id === $companyId) {
            return true;
        }

        if ((string) ($user->Employee?->Contract?->company_id ?? '') === $companyId) {
            return true;
        }

        return $user->Companies()->where('companies.id', $companyId)->exists();
    }
}
