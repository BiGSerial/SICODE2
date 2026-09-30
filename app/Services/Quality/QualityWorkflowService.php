<?php

namespace App\Services\Quality;

use App\Enum\{QualityEventType, QualityProcessState, QualityProcessStatus, QualityStageKind, QualityStageLevel, QualityStageStatus, QualityStageType};
use App\Models\{Company, File, Note, Notetimeline, Production, QualityEvent, QualityMember, QualityProcess, QualityRejection, QualityRejectionCategory, QualityStage, QualityStageFile, Reclaim, User};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados da Qualidade. Toda regra de fluxo, papel e empresa é validada AQUI (backend);
 * controllers, policies e views apenas delegam.
 *
 * N2 ──despacha──▶ N1 ──despacha──▶ Usuário ──envia──▶ N1 ──aprova──▶ N2
 *                   ▲  ◀──rejeita (volta ao usuário)──┘               │
 *                   └─────────────── N2 devolve ao N1 ◀──────────────────┘
 */
class QualityWorkflowService
{
    public function __construct(
        private readonly QualityEligibilityService $eligibility,
        private readonly QualityRoles $roles,
        private readonly QualityDesignerResolver $designers,
        private readonly QualityActivities $activities,
        private readonly BudgetOrderRules $budget,
        private readonly SurveyInformRules $survey,
    ) {
    }

    // ------------------------------------------------------------------ N2 → N1

    /** Gestão despacha Notas do Pool ao N1 de uma empresa. Ainda NÃO existe Production: ela nasce no despacho do N1. */
    public function dispatch(Collection|array $notes, Company $company, User $actor): Collection
    {
        return DB::transaction(function () use ($notes, $company, $actor) {
            $result = collect();

            if (!$this->roles->canDispatch($actor)) {
                throw new QualityWorkflowException('Somente a gestão da Qualidade pode despachar Notas do Pool para o N1 da empresa.');
            }

            if ($missing = $this->activities->missing()) {
                throw new QualityWorkflowException('Defina a atividade de cada ciclo em Qualidade > Critérios e atividades (' . implode(', ', $missing) . ').');
            }

            if (!QualityMember::query()->where('company_id', $company->id)->where('role', QualityMember::N1)->where('active', true)->exists()) {
                throw new QualityWorkflowException("A empresa {$company->name} não possui N1 cadastrado em Qualidade > Equipe.");
            }

            foreach (collect($notes) as $item) {
                $note = Note::query()->lockForUpdate()->findOrFail($item instanceof Note ? $item->id : $item);

                if (!$this->eligibility->eligible($note)) {
                    throw new QualityWorkflowException("A Nota {$note->note} não é elegível para Qualidade.");
                }

                $process = QualityProcess::create([
                    'note_id'          => $note->id,
                    'company_id'       => $company->id,
                    'status'           => QualityProcessStatus::ACTIVE,
                    'state'            => QualityProcessState::AWAITING_N1_DISPATCH,
                    'state_changed_at' => now(),
                    'phase'            => QualityStageType::PROJECT,
                    'round_number'     => 1,
                    'current_stage'    => QualityStageLevel::N1,
                    'dispatched_by'    => $actor->id,
                    'dispatched_at'    => now(),
                ]);
                $stage = $this->createStage($process, QualityStageLevel::N1, QualityStageKind::DISPATCH, dispatchedBy: $actor);
                $this->event($process, QualityEventType::DISPATCHED, $actor, 'Gestão', $stage, from: null, payload: ['company_id' => $company->id, 'company' => $company->name]);
                $result->push($process->fresh());
            }

            return $result;
        });
    }

    // ------------------------------------------------------------------ N1 → Usuário

    /**
     * Despacha (ou, após devolução do N2, re-encaminha) a atividade a um usuário habilitado em DESENHO
     * da própria empresa do processo. Sem $designerId, mantém o último executor.
     */
    public function assignDesigner(QualityProcess $process, User $actor, ?string $designerId = null, ?string $observation = null): QualityProcess
    {
        return DB::transaction(function () use ($process, $actor, $designerId, $observation) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::AWAITING_N1_DISPATCH, QualityProcessState::N2_RETURNED]);
            $this->assertN1($actor, $process);

            $designerId ??= $process->current_designer_id;

            if (!$this->designers->isEligible($process, $designerId)) {
                throw new QualityWorkflowException('O usuário precisa ser da mesma empresa do processo e estar habilitado na atividade configurada para este ciclo.');
            }

            $from     = $process->state;
            $isReturn = $from === QualityProcessState::N2_RETURNED;
            $current  = $this->currentStage($process, [QualityStageKind::DISPATCH, QualityStageKind::RETURN]);
            $current->update(['status' => QualityStageStatus::COMPLETED, 'completed_at' => now(), 'observation' => $observation]);

            $designer   = User::findOrFail($designerId);
            $production = $this->productionForProcess($process, $designer, $actor);
            $process->update(['production_id' => $production->id]);
            $stage    = $this->createStage($process, QualityStageLevel::DRAWING, QualityStageKind::EXECUTION, assigned: $designerId, dispatchedBy: $actor);
            $previous = $production->wasRecentlyCreated ? null : $production->user_id;
            $reopened = !$production->wasRecentlyCreated;

            if ($reopened) {
                // Mesma atividade: volta para a pilha de quem o N1 escolheu, sem criar outra Production.
                $production->update(['user_id' => $designer->id, 'company_id' => $designer->company_id, 'dispatch_by' => $actor->id, 'dispatch_at' => now(), 'status' => 2, 'completed' => false, 'completed_at' => null, 'confirmed' => false]);
            }

            $process->update(['n1_user_id' => $process->n1_user_id ?? $actor->id, 'current_designer_id' => $designer->id]);
            $this->move($process, QualityProcessState::AWAITING_DESIGNER);
            $this->event($process, $isReturn ? QualityEventType::RETURN_FORWARDED : QualityEventType::DESIGNER_ASSIGNED, $actor, 'N1', $stage, from: $from, observation: $observation, target: $designer->id, payload: ['previous_production_user_id' => $previous, 'production_id' => $production->id, 'reopened' => $reopened]);
            $this->timeline($production, $actor, 'Qualidade: atividade ' . ($reopened ? 'reaberta' : 'criada') . " na pilha de {$designer->name} ({$process->phase->short()}, rodada {$process->round_number}).");

            return $process->fresh();
        });
    }

    /**
     * Despacho EM MASSA do N1: cada processo é tratado em sua própria transação (um erro não derruba os demais).
     * Serve ao despacho inicial e ao reencaminhamento de devoluções do N2 (mesmo usuário ou outro).
     *
     * @param  array<int, int|string>  $processIds
     * @return array{done: int, failed: array<int, string>}
     */
    public function assignMany(array $processIds, User $actor, ?string $designerId, ?string $observation = null): array
    {
        $result = ['done' => 0, 'failed' => []];

        foreach (array_unique(array_map('intval', $processIds)) as $id) {
            try {
                $this->assignDesigner(QualityProcess::findOrFail($id), $actor, $designerId, $observation);
                $result['done']++;
            } catch (QualityWorkflowException $e) {
                $result['failed'][$id] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Só o N1 troca o usuário de uma atividade que já está com um usuário (ex.: parada). A rodada e a Production
     * são as mesmas; a troca fica registrada (evento `REASSIGNED` com o usuário anterior).
     */
    public function reassignDesigner(QualityProcess $process, User $actor, string $designerId, ?string $reason = null): QualityProcess
    {
        return DB::transaction(function () use ($process, $actor, $designerId, $reason) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::AWAITING_DESIGNER]);
            $this->assertN1($actor, $process);

            if (!$this->designers->isEligible($process, $designerId)) {
                throw new QualityWorkflowException('O usuário precisa ser da mesma empresa do processo e estar habilitado na atividade configurada para este ciclo.');
            }

            $stage    = $this->currentStage($process, [QualityStageKind::EXECUTION]);
            $previous = $stage->assigned_user_id;

            if ((string) $previous === $designerId) {
                throw new QualityWorkflowException('A atividade já está com este usuário.');
            }

            $designer = User::findOrFail($designerId);
            $stage->update(['assigned_user_id' => $designerId, 'dispatched_by' => $actor->id, 'dispatched_at' => now(), 'status' => QualityStageStatus::PENDING, 'started_at' => null]);
            Production::query()->lockForUpdate()->findOrFail($process->production_id)
                ->update(['user_id' => $designer->id, 'company_id' => $designer->company_id, 'dispatch_by' => $actor->id, 'dispatch_at' => now(), 'status' => 2]);
            $process->update(['current_designer_id' => $designer->id, 'state_changed_at' => now()]);
            $this->event($process, QualityEventType::REASSIGNED, $actor, 'N1', $stage, from: $process->state, observation: $reason, target: $designer->id, payload: ['previous_user_id' => $previous]);
            $this->timeline(Production::findOrFail($process->production_id), $actor, "Qualidade: atividade reatribuída a {$designer->name}.");

            return $process->fresh();
        });
    }

    // ------------------------------------------------------------------ Usuário

    public function startExecution(QualityProcess $process, User $actor): QualityStage
    {
        return DB::transaction(function () use ($process, $actor) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::AWAITING_DESIGNER]);
            $stage = $this->currentStage($process, [QualityStageKind::EXECUTION]);
            $this->assertDesigner($actor, $stage);

            if ($stage->status === QualityStageStatus::IN_PROGRESS) {
                return $stage;
            }
            $stage->update(['status' => QualityStageStatus::IN_PROGRESS, 'started_at' => now()]);
            $this->event($process, QualityEventType::STAGE_STARTED, $actor, 'DESIGNER', $stage, from: $process->state);

            return $stage->fresh();
        });
    }

    /**
     * O usuário devolve a rodada ao N1. Nunca vai direto ao N2.
     *
     * @param  array<int, int|string>  $fileIds
     * @param  array<string, mixed>    $submissionData  1º ciclo: ['survey' => informe do Levantamento]; 2º ciclo: ['orders' => [['order_number','total_cost','company_cost','client_cost'], ...]]
     */
    public function submitExecution(QualityProcess $process, User $actor, array $fileIds = [], ?string $observation = null, array $submissionData = []): QualityProcess
    {
        return DB::transaction(function () use ($process, $actor, $fileIds, $observation, $submissionData) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::AWAITING_DESIGNER]);
            $stage = $this->currentStage($process, [QualityStageKind::EXECUTION]);
            $this->assertDesigner($actor, $stage);

            $fileIds = array_values(array_unique(array_map('intval', $fileIds)));

            if ($process->phase === QualityStageType::PROJECT) {
                $submissionData['survey'] = $this->survey->validate($submissionData['survey'] ?? []);
                $this->survey->persist(Production::findOrFail($process->production_id), $submissionData['survey']);
            } else {
                $submissionData['orders'] = $this->budget->validate($submissionData['orders'] ?? [], $process->Note?->note);
            }

            $this->recordStageFiles($stage, $process, $actor, $fileIds);
            $stage->update([
                'status'          => QualityStageStatus::COMPLETED,
                'started_at'      => $stage->started_at ?: now(),
                'completed_at'    => now(),
                'executed_by'     => $actor->id,
                'observation'     => $observation,
                'submission_data' => $submissionData ?: null,
            ]);

            $from = $process->state;
            $next = $this->createStage($process, QualityStageLevel::N1, QualityStageKind::REVIEW);
            $this->move($process, QualityProcessState::AWAITING_N1_REVIEW);
            $this->event($process, QualityEventType::STAGE_SUBMITTED, $actor, 'DESIGNER', $stage, from: $from, observation: $observation, payload: ['file_ids' => $fileIds, 'next_stage_id' => $next->id]);

            return $process->fresh();
        });
    }

    // ------------------------------------------------------------------ N1 / N2: aprovar

    /**
     * Aprovação por quem detém o processo: N1 encaminha ao N2; N2 abre o 2º ciclo (1º ciclo) ou conclui a Qualidade
     * (2º ciclo): encerra a atividade do usuário e o processo, sem volta.
     */
    public function approve(QualityProcess $process, User $actor, ?string $observation = null, array $fileIds = []): QualityProcess
    {
        return DB::transaction(function () use ($process, $actor, $observation, $fileIds) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::AWAITING_N1_REVIEW, QualityProcessState::AWAITING_N2_REVIEW]);

            $isN1 = $process->state === QualityProcessState::AWAITING_N1_REVIEW;
            $isN1 ? $this->assertN1($actor, $process) : $this->assertN2($actor, $process);

            $stage = $this->currentStage($process, [QualityStageKind::REVIEW]);
            $this->recordStageFiles($stage, $process, $actor, $fileIds);
            $stage->update(['status' => QualityStageStatus::APPROVED, 'approved_by' => $actor->id, 'approved_at' => now(), 'completed_at' => now(), 'observation' => $observation]);
            $from = $process->state;

            if ($isN1) {
                $process->update(['n1_user_id' => $process->n1_user_id ?? $actor->id]);
                $this->createStage($process, QualityStageLevel::N2, QualityStageKind::REVIEW);
                $this->move($process, QualityProcessState::AWAITING_N2_REVIEW);
                $this->event($process, QualityEventType::APPROVED, $actor, 'N1', $stage, from: $from, observation: $observation);
                $this->event($process, QualityEventType::FORWARDED_TO_N2, $actor, 'N1', $stage, from: $from, observation: $observation);

                return $process->fresh();
            }

            $this->event($process, QualityEventType::APPROVED, $actor, 'N2', $stage, from: $from, observation: $observation);

            if ($process->phase === QualityStageType::PROJECT) {
                $this->startSecondCycle($process, $actor, $stage, $from, $observation);

                return $process->fresh();
            }

            $this->finishProcess($process, $actor, $stage, $from);

            return $process->fresh();
        });
    }

    /** N2: conclui um encerramento que ficou pendente (processos anteriores à remoção da etapa de SAP). */
    public function retryClosing(QualityProcess $process, User $actor): QualityProcess
    {
        return DB::transaction(function () use ($process, $actor) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::SAP_FAILED, QualityProcessState::CLOSING]);
            $this->assertN2($actor, $process);
            $stage = QualityStage::query()->where('quality_process_id', $process->id)->where('level', QualityStageLevel::N2)->where('status', QualityStageStatus::APPROVED)->latest('id')->first();
            $this->finishProcess($process, $actor, $stage, $process->state);

            return $process->fresh();
        });
    }

    // ------------------------------------------------------------------ N1 / N2: rejeitar

    /**
     * N1 rejeita → volta ao usuário (nova rodada). N2 rejeita → volta ao N1 (nova rodada), que
     * precisa encaminhar ao usuário. N2 nunca devolve direto ao usuário.
     *
     * @param  array<int, array{category_id:int, subcategory_id?:int|null, observation?:string|null}>  $reasons
     */
    public function reject(QualityProcess $process, User $actor, array $reasons, ?string $observation = null, array $fileIds = []): QualityProcess
    {
        return DB::transaction(function () use ($process, $actor, $reasons, $observation, $fileIds) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::AWAITING_N1_REVIEW, QualityProcessState::AWAITING_N2_REVIEW]);

            $isN1 = $process->state === QualityProcessState::AWAITING_N1_REVIEW;
            $isN1 ? $this->assertN1($actor, $process) : $this->assertN2($actor, $process);

            $observation = trim((string) $observation) ?: null;

            if (!$reasons) {
                throw new QualityWorkflowException('Informe ao menos um motivo (categoria e subcategoria) da ' . ($isN1 ? 'rejeição.' : 'devolução.'));
            }
            $this->validateRejectionReasons($process->phase, $reasons);

            $stage = $this->currentStage($process, [QualityStageKind::REVIEW]);
            $this->recordStageFiles($stage, $process, $actor, $fileIds);
            $stage->update(['status' => QualityStageStatus::REJECTED, 'completed_at' => now(), 'observation' => $observation]);

            $from      = $process->state;
            $role      = $isN1 ? 'N1' : 'N2';
            $designer  = $process->current_designer_id;
            $rejection = QualityRejection::create([
                'quality_process_id' => $process->id, 'quality_stage_id' => $stage->id, 'production_id' => $process->production_id,
                'type'               => $process->phase, 'level' => $stage->level, 'returned_to_level' => $isN1 ? QualityStageLevel::DRAWING : QualityStageLevel::N1,
                'round_number'       => $process->round_number, 'author_id' => $actor->id, 'designer_id' => $designer,
                'company_id'         => $process->company_id, 'observation' => $observation,
            ]);

            foreach ($reasons as $reason) {
                $rejection->Items()->create(['category_id' => $reason['category_id'], 'subcategory_id' => $reason['subcategory_id'] ?? null, 'observation' => $reason['observation'] ?? null]);
            }

            $process->update(['round_number' => $process->round_number + 1]);
            $this->event($process, QualityEventType::REJECTED, $actor, $role, $stage, from: $from, observation: $observation, payload: ['rejection_id' => $rejection->id, 'file_ids' => $fileIds, 'reasons' => count($reasons)]);

            if ($isN1) {
                $execution = $this->createStage($process, QualityStageLevel::DRAWING, QualityStageKind::EXECUTION, assigned: $designer, dispatchedBy: $actor);
                $this->move($process, QualityProcessState::AWAITING_DESIGNER);
                $this->event($process, QualityEventType::RETURNED_TO_DESIGNER, $actor, 'N1', $execution, from: $from, target: $designer, observation: $observation);
            } else {
                $return = $this->createStage($process, QualityStageLevel::N1, QualityStageKind::RETURN, dispatchedBy: $actor);
                $this->move($process, QualityProcessState::N2_RETURNED);
                $this->event($process, QualityEventType::RETURNED_TO_N1, $actor, 'N2', $return, from: $from, observation: $observation);
            }

            return $process->fresh();
        });
    }

    /**
     * N2 aprovou o 1º ciclo: encerra a atividade do usuário (completed_at = data em que ele enviou ao N1) e abre
     * AUTOMATICAMENTE a atividade do 2º ciclo para o mesmo usuário e empresa. Sem novo despacho do N1.
     */
    private function startSecondCycle(QualityProcess $process, User $actor, QualityStage $approvedStage, QualityProcessState $from, ?string $observation): void
    {
        $service = $this->activities->serviceFor(QualityStageType::BUDGET);

        if (!$service) {
            throw new QualityWorkflowException('A atividade do 2º ciclo não está configurada em Qualidade > Critérios e atividades.');
        }

        $designer = User::findOrFail($process->current_designer_id);

        if (Production::query()->where('note_id', $process->note_id)->where('service_id', $service->uuid)->where('completed', false)->exists()) {
            throw new QualityWorkflowException("A Nota já possui atividade aberta no serviço {$service->service}.");
        }

        $this->closeProduction($process, $actor, 'Qualidade: 1º ciclo aprovado pelo N2; atividade encerrada.', applySurveyToNote: true);
        $note = $process->Note()->first();

        $production = Production::withoutQualityGuard(fn () => Production::create([
            'note_id'     => $process->note_id, 'service_id' => $service->uuid, 'user_id' => $designer->id, 'company_id' => $designer->company_id,
            'dispatch_by' => $actor->id, 'att_by' => $actor->id, 'dt_note' => $note?->dt_status, 'status_note' => $note?->nstats,
            'dispatch_at' => now(), 'att_at' => now(), 'status' => 2, 'completed' => false, 'confirmed' => false,
        ]));

        $process->update(['phase' => QualityStageType::BUDGET, 'round_number' => 1, 'production_id' => $production->id]);
        $execution = $this->createStage($process, QualityStageLevel::DRAWING, QualityStageKind::EXECUTION, assigned: $designer->id, dispatchedBy: $actor);
        $this->move($process, QualityProcessState::AWAITING_DESIGNER);
        $this->event($process, QualityEventType::BUDGET_RELEASED, $actor, 'N2', $execution, from: $from, target: $designer->id, observation: $observation, payload: ['production_id' => $production->id, 'service' => $service->service, 'automatic' => true]);
        $this->timeline($production, $actor, "Qualidade: 2º ciclo iniciado; atividade {$service->service} aberta automaticamente para {$designer->name}.");
    }

    /** N1 questiona a devolução do N2 (com justificativa) e devolve a decisão ao N2, sem acionar o usuário. */
    public function contest(QualityProcess $process, User $actor, string $justification): QualityProcess
    {
        $justification = trim($justification);

        if ($justification === '') {
            throw new QualityWorkflowException('Explique por que a devolução do N2 está sendo questionada.');
        }

        return DB::transaction(function () use ($process, $actor, $justification) {
            $process = $this->lock($process);
            $this->assertState($process, [QualityProcessState::N2_RETURNED]);
            $this->assertN1($actor, $process);

            $current = $this->currentStage($process, [QualityStageKind::RETURN]);
            $current->update(['status' => QualityStageStatus::COMPLETED, 'completed_at' => now(), 'observation' => $justification]);
            $from  = $process->state;
            $stage = $this->createStage($process, QualityStageLevel::N2, QualityStageKind::REVIEW);
            $this->move($process, QualityProcessState::AWAITING_N2_REVIEW);
            $this->event($process, QualityEventType::CONTESTED, $actor, 'N1', $stage, from: $from, observation: $justification);
            $this->event($process, QualityEventType::COMMENT_ADDED, $actor, 'N1', $stage, from: $from, observation: $justification, payload: ['visibility' => 'INTERNAL']);

            return $process->fresh();
        });
    }

    // ------------------------------------------------------------------ Comentários

    /**
     * Comentário do processo. N1/N2 escrevem por padrão em `INTERNAL` (só N1/N2/Gestão veem: é a discussão
     * entre eles). `ALL` fica visível também ao usuário no formulário da Qualidade.
     */
    public function addComment(QualityProcess $process, User $actor, string $comment, string $visibility = 'INTERNAL'): QualityEvent
    {
        $comment = trim($comment);

        if ($comment === '') {
            throw new QualityWorkflowException('Escreva o comentário.');
        }

        return DB::transaction(function () use ($process, $actor, $comment, $visibility) {
            $process = $this->lock($process);

            if ($process->status !== QualityProcessStatus::ACTIVE) {
                throw new QualityWorkflowException('Processo encerrado não aceita comentários.');
            }

            $stage = QualityStage::query()->where('quality_process_id', $process->id)->latest('id')->first();
            $role  = match (true) {
                $this->roles->canActAsN2($actor, $process)               => 'N2',
                $this->roles->canActAsN1($actor, $process)               => 'N1',
                $stage && $this->roles->canActAsDesigner($actor, $stage) => 'DESIGNER',
                default                                                  => throw new QualityWorkflowException('Usuário sem participação neste processo.'),
            };

            if ($role === 'DESIGNER') {
                $visibility = 'ALL';
            }

            return $this->event($process, QualityEventType::COMMENT_ADDED, $actor, $role, $stage, from: $process->state, observation: $comment, payload: ['visibility' => $visibility === 'ALL' ? 'ALL' : 'INTERNAL']);
        });
    }

    // ------------------------------------------------------------------ Encerramento

    /** Encerra a atividade do usuário e conclui a Qualidade. Tudo na mesma transação do chamador. */
    private function finishProcess(QualityProcess $process, User $actor, ?QualityStage $stage, QualityProcessState $from): void
    {
        $this->closeProduction($process, $actor);
        $process->update(['status' => QualityProcessStatus::COMPLETED, 'state' => QualityProcessState::COMPLETED, 'state_changed_at' => now(), 'current_stage' => null, 'completed_by' => $actor->id, 'completed_at' => now()]);
        $this->event($process, QualityEventType::COMPLETED, $actor, 'N2', $stage, from: $from, to: QualityProcessState::COMPLETED, payload: ['approved_by' => $actor->id]);
    }

    /**
     * Mesmo efeito do encerramento normal: status 5, completed, status_note. `completed_at` segue a data em que o usuário
     * enviou a última rodada ao N1 (não a data da aprovação do N2).
     */
    private function closeProduction(QualityProcess $process, User $actor, string $info = 'Qualidade concluída: aprovada pelo N2, atividade encerrada.', bool $applySurveyToNote = false): void
    {
        $production = Production::query()->lockForUpdate()->findOrFail($process->production_id);
        $note       = $process->Note()->first();
        $submitted  = QualityStage::query()->where('quality_process_id', $process->id)->where('production_id', $production->id)->where('kind', QualityStageKind::EXECUTION->value)
            ->where('status', QualityStageStatus::COMPLETED->value)->latest('id')->first();

        $production->update([
            'status'       => 5,
            'completed'    => true,
            'completed_at' => $submitted?->completed_at ?? now(),
            'confirmed'    => false,
            'priority'     => false,
            'status_note'  => $note?->nstats ?? $production->status_note,
        ]);

        if ($applySurveyToNote && $note && !empty($submitted?->submission_data['survey'])) {
            $this->survey->applyToNote($note, $submitted->submission_data['survey']);
        }

        Reclaim::query()->where('production_id', $production->id)->where('completed', false)->get()->each(function (Reclaim $reclaim): void {
            $reclaim->update(['completed' => true, 'completed_at' => now()]);
            $reclaim->Viabilities()->update(['status' => 13]);
        });

        Notetimeline::create(['note_id' => $production->note_id, 'service_id' => $production->service_id, 'production_id' => $production->id, 'user_id' => $actor->id, 'info' => $info, 'status' => 5, 'system' => true]);
        $this->event($process, QualityEventType::PRODUCTION_CLOSED, $actor, 'N2', null, payload: ['production_id' => $production->id, 'completed_at' => (string) $production->completed_at]);
    }

    /** A Production é UMA por processo: criada no primeiro despacho do N1 e reaberta nas rodadas/ciclos seguintes. */
    private function productionForProcess(QualityProcess $process, User $designer, User $actor): Production
    {
        $service = $this->activities->serviceFor($process->phase);

        if (!$service) {
            throw new QualityWorkflowException('A atividade do ' . $process->phase->short() . ' não está configurada em Qualidade > Critérios e atividades.');
        }

        if ($process->production_id) {
            return Production::query()->lockForUpdate()->findOrFail($process->production_id);
        }

        if (Production::query()->where('note_id', $process->note_id)->where('service_id', $service->uuid)->where('completed', false)->exists()) {
            throw new QualityWorkflowException("A Nota já possui atividade aberta no serviço {$service->service}. Encerre ou transfira antes de despachar pela Qualidade.");
        }

        $note = $process->Note()->first();

        return Production::withoutQualityGuard(fn () => Production::create([
            'note_id'     => $process->note_id,
            'service_id'  => $service->uuid,
            'user_id'     => $designer->id,
            'company_id'  => $designer->company_id,
            'dispatch_by' => $actor->id,
            'att_by'      => $actor->id,
            'dt_note'     => $note?->dt_status,
            'status_note' => $note?->nstats,
            'dispatch_at' => now(),
            'att_at'      => now(),
            'status'      => 2,
            'completed'   => false,
            'confirmed'   => false,
        ]));
    }

    // ------------------------------------------------------------------ Infra

    private function lock(QualityProcess $process): QualityProcess
    {
        return QualityProcess::query()->with(['Note', 'Production'])->lockForUpdate()->findOrFail($process->id);
    }

    private function assertState(QualityProcess $process, array $allowed): void
    {
        if ($process->status !== QualityProcessStatus::ACTIVE || !in_array($process->state, $allowed, true)) {
            throw new QualityWorkflowException('Ação indisponível: o processo está em "' . $process->state->label() . '".');
        }
    }

    private function assertN1(User $actor, QualityProcess $process): void
    {
        if (!$this->roles->canActAsN1($actor, $process)) {
            throw new QualityWorkflowException('Somente o N1 da empresa do processo pode executar esta ação.');
        }
    }

    private function assertN2(User $actor, QualityProcess $process): void
    {
        if (!$this->roles->canActAsN2($actor, $process)) {
            throw new QualityWorkflowException('Somente o N2 pode executar esta ação.');
        }
    }

    private function assertDesigner(User $actor, QualityStage $stage): void
    {
        if (!$this->roles->canActAsDesigner($actor, $stage)) {
            throw new QualityWorkflowException('Esta atividade foi despachada a outro usuário.');
        }
    }

    /** A etapa aberta do tipo esperado; falha se não houver (etapa antiga / já tratada). */
    private function currentStage(QualityProcess $process, array $kinds): QualityStage
    {
        $stage = QualityStage::query()
            ->where('quality_process_id', $process->id)
            ->whereIn('kind', array_map(fn (QualityStageKind $kind) => $kind->value, $kinds))
            ->whereIn('status', [QualityStageStatus::PENDING->value, QualityStageStatus::IN_PROGRESS->value])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (!$stage) {
            throw new QualityWorkflowException('Etapa antiga ou já tratada.');
        }

        return $stage;
    }

    private function createStage(QualityProcess $process, QualityStageLevel $level, QualityStageKind $kind, ?string $assigned = null, ?User $dispatchedBy = null): QualityStage
    {
        return QualityStage::create([
            'quality_process_id' => $process->id, 'production_id' => $process->production_id, 'type' => $process->phase, 'level' => $level, 'kind' => $kind,
            'status'             => QualityStageStatus::PENDING, 'round_number' => $process->round_number,
            'assigned_user_id'   => $assigned, 'dispatched_by' => $dispatchedBy?->id, 'dispatched_at' => $dispatchedBy ? now() : null,
        ]);
    }

    private function move(QualityProcess $process, QualityProcessState $to): void
    {
        $process->update(['state' => $to, 'state_changed_at' => now(), 'current_stage' => $to->holder()]);
    }

    /** @param  array<int, int|string>  $fileIds */
    private function recordStageFiles(QualityStage $stage, QualityProcess $process, User $actor, array $fileIds): void
    {
        foreach (array_unique(array_map('intval', $fileIds)) as $fileId) {
            $file = File::findOrFail($fileId);

            if ((int) $file->note_id !== (int) $process->note_id) {
                throw new QualityWorkflowException('Arquivo não pertence à Nota da Production.');
            }

            QualityStageFile::firstOrCreate(['quality_stage_id' => $stage->id, 'file_id' => $file->id], [
                'checksum' => (string) ($file->sha256 ?: sha1((string) ($file->path ?: $file->file_name))), 'submitted_by' => $actor->id, 'submitted_at' => now(),
            ]);
        }
    }

    private function validateRejectionReasons(QualityStageType $phase, array $reasons): void
    {
        $column     = $phase === QualityStageType::PROJECT ? 'applies_project' : 'applies_budget';
        $categories = QualityRejectionCategory::query()->whereIn('id', collect($reasons)->pluck('category_id')->map(fn ($id) => (int) $id))->get()->keyBy('id');

        foreach ($reasons as $reason) {
            $category = $categories->get((int) ($reason['category_id'] ?? 0));

            if (!$category || $category->parent_id || !$category->active || !$category->{$column}) {
                throw new QualityWorkflowException('O motivo selecionado está inativo ou não se aplica a este ciclo.');
            }

            $hasChildren = QualityRejectionCategory::query()->where('parent_id', $category->id)->where('active', true)->where($column, true)->exists();

            if ($hasChildren && empty($reason['subcategory_id'])) {
                throw new QualityWorkflowException("Informe a subcategoria do motivo \"{$category->name}\".");
            }

            if (!empty($reason['subcategory_id'])) {
                $sub = QualityRejectionCategory::find($reason['subcategory_id']);

                if (!$sub || !$sub->active || (int) $sub->parent_id !== (int) $category->id || !$sub->{$column}) {
                    throw new QualityWorkflowException('A subcategoria selecionada não pertence ao motivo informado ou não se aplica a este ciclo.');
                }
            }
        }
    }

    private function event(QualityProcess $process, QualityEventType $type, User $actor, string $role, ?QualityStage $stage, ?QualityProcessState $from = null, ?QualityProcessState $to = null, ?string $observation = null, ?string $target = null, array $payload = []): QualityEvent
    {
        $process->refresh();

        return $process->Events()->create([
            'quality_stage_id' => $stage?->id, 'production_id' => $process->production_id, 'actor_id' => $actor->id, 'actor_role' => $role,
            'company_id'       => $process->company_id, 'designer_id' => $process->current_designer_id ?? $process->original_designer_id, 'target_user_id' => $target,
            'type'             => $type, 'from_state' => $from?->value, 'to_state' => ($to ?? $process->state)->value,
            'stage_type'       => $process->phase, 'stage_level' => $stage?->level, 'round_number' => $stage?->round_number ?? $process->round_number,
            'observation'      => $observation, 'payload' => $payload,
        ]);
    }

    private function timeline(Production $production, User $actor, string $info): void
    {
        Notetimeline::create(['note_id' => $production->note_id, 'service_id' => $production->service_id, 'production_id' => $production->id, 'user_id' => $actor->id, 'info' => $info, 'status' => 1, 'system' => true]);
    }
}
