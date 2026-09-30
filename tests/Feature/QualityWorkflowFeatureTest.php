<?php

namespace Tests\Feature;

use App\Enum\{QualityEventType, QualityProcessState, QualityProcessStatus, QualityStageKind, QualityStageLevel, QualityStageType};
use App\Models\{Company, File, Note, Production, QualityMember, QualityPoolRule, QualityProcess, QualityRejectionCategory, QualitySetting, Service, ServiceUser, User};
use App\Services\Quality\{QualityEligibilityService, QualityWorkflowException, QualityWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Company $otherCompany;

    private User $n2;

    private User $manager;

    private User $n1;

    private User $designerA;

    private User $designerB;

    private Service $service;

    private Service $service2;

    private Note $note;

    private QualityWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company      = Company::create(['name' => 'Empreiteira Alfa', 'email' => 'alfa@example.test']);
        $this->otherCompany = Company::create(['name' => 'Empreiteira Beta', 'email' => 'beta@example.test']);
        $this->service      = Service::create(['service' => 'Levantamento', 'project' => true, 'folder' => 'levantamento']);
        $this->service2     = Service::create(['service' => 'Desenho', 'project' => true, 'folder' => 'desenho']);
        $flags              = ['superadm' => false, 'admin' => false, 'management' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false];
        $this->n2           = User::factory()->create(['can_dispatch' => true, 'company_id' => $this->company->id] + $flags);
        $this->manager      = User::factory()->create(['management' => true] + $flags);
        $this->n1           = User::factory()->create(['analyst' => true, 'company_id' => $this->company->id] + $flags);
        $this->designerA    = $this->designer($this->company, 'Desenhista A');
        $this->designerB    = $this->designer($this->company, 'Desenhista B');

        QualitySetting::create(['key' => 'activities', 'value' => ['PROJECT' => $this->service->uuid, 'BUDGET' => $this->service2->uuid]]);
        QualityPoolRule::create(['column_search' => 'nstats', 'condition' => 'Exatamente', 'value' => '10']);
        QualityMember::create(['user_id' => $this->n1->id, 'company_id' => $this->company->id, 'role' => 'N1']);
        QualityMember::create(['user_id' => $this->n2->id, 'company_id' => $this->company->id, 'role' => 'N2']);
        $this->note     = Note::create(['note' => '100200300', 'nstats' => '10', 'type_note' => 1]);
        $this->workflow = app(QualityWorkflowService::class);
    }

    // ---------------------------------------------------------------- fluxo completo

    public function test_full_flow_n2_n1_designer_two_passes_with_rejections_and_production_closing(): void
    {
        $process = $this->dispatchToN1();
        $this->assertSame(QualityProcessState::AWAITING_N1_DISPATCH, $process->state);
        $this->assertSame(QualityStageType::PROJECT, $process->phase);

        // ===== 1º ciclo (Levantamento) =====
        $process         = $this->workflow->assignDesigner($process, $this->n1, $this->designerA->id);
        $firstProduction = $process->production_id;
        $this->assertNotNull($firstProduction, 'a Production nasce no despacho do N1');
        $this->assertSame($this->designerA->id, Production::find($firstProduction)->user_id);
        $process = $this->submit($process, $this->designerA, [$this->file('croqui-r1.pdf')->id]);
        $this->assertSame(QualityProcessState::AWAITING_N1_REVIEW, $process->state);

        // N1 rejeita (2 motivos) → volta ao desenhista A, rodada 2
        $process = $this->workflow->reject($process, $this->n1, [$this->reason('Croqui'), $this->reason('Documentação')], 'Ajustar medidas.');
        $this->assertSame(QualityProcessState::AWAITING_DESIGNER, $process->state);
        $this->assertSame(2, $process->round_number);
        $this->assertDatabaseCount('quality_rejection_items', 2);

        $process = $this->submit($process, $this->designerA, [$this->file('croqui-r2.pdf')->id]);
        $process = $this->workflow->approve($process, $this->n1);
        $this->assertSame(QualityProcessState::AWAITING_N2_REVIEW, $process->state);

        // N2 devolve → vai ao N1 (NÃO ao desenhista), rodada 3
        $process = $this->workflow->reject($process, $this->n2, [$this->reason('Croqui')], 'Revisar simbologia.');
        $this->assertSame(QualityProcessState::N2_RETURNED, $process->state);
        $this->assertSame(QualityStageLevel::N1, $process->current_stage);
        $this->assertSame(3, $process->round_number);

        // N1 encaminha ao desenhista B (troca permitida, mesma empresa)
        $process = $this->workflow->assignDesigner($process, $this->n1, $this->designerB->id, 'Corrigir simbologia.');
        $this->assertSame($this->designerB->id, Production::find($process->production_id)->user_id);
        $this->assertSame($firstProduction, $process->production_id, 'devolução do N2 reaproveita a mesma Production');
        $process = $this->submit($process, $this->designerB, [$this->file('croqui-r3.pdf')->id]);
        $process = $this->workflow->approve($process, $this->n1);
        $this->assertSame(QualityProcessState::AWAITING_N2_REVIEW, $process->state);

        $submittedAt = $process->Stages()->reorder('id', 'desc')->where('kind', QualityStageKind::EXECUTION->value)->where('status', 'COMPLETED')->first()->completed_at;
        $process     = $this->workflow->approve($process, $this->n2);

        // N2 aprovou o 1º ciclo: encerra a atividade e abre a do 2º ciclo sozinha, para o MESMO usuário e empresa
        $this->assertSame(QualityProcessState::AWAITING_DESIGNER, $process->state, 'sem novo despacho do N1');
        $this->assertSame(QualityStageType::BUDGET, $process->phase);
        $this->assertSame(1, $process->round_number);
        $old = Production::find($firstProduction);
        $this->assertTrue((bool) $old->completed);
        $this->assertSame(5, (int) $old->status);
        $this->assertSame($submittedAt->format('Y-m-d H:i:s'), $old->completed_at->format('Y-m-d H:i:s'), 'completed_at = data em que o usuário enviou ao N1');
        $new = Production::find($process->production_id);
        $this->assertNotSame($firstProduction, $new->id);
        $this->assertSame($this->service2->uuid, $new->service_id);
        $this->assertSame($this->designerB->id, $new->user_id);
        $this->assertSame($this->designerB->company_id, $new->company_id);
        $this->assertFalse((bool) $new->completed);
        $this->assertSame(2, Production::where('note_id', $this->note->id)->count());

        // ===== 2º ciclo =====
        $process = $this->submit($process, $this->designerB, [], 'Orçamento', ['orders' => [$this->order()]]);
        $process = $this->workflow->reject($process, $this->n1, [$this->reason('Orçamento', 'BUDGET')]);
        $this->assertSame(2, $process->round_number);
        $process = $this->submit($process, $this->designerB, [], null, ['orders' => [$this->order()]]);
        $process = $this->workflow->approve($process, $this->n1);
        $process = $this->workflow->reject($process, $this->n2, [$this->reason('Orçamento', 'BUDGET')], 'Quantidade divergente.');
        $this->assertSame(QualityProcessState::N2_RETURNED, $process->state);
        $process = $this->workflow->contest($process, $this->n1, 'A quantidade está conforme o levantamento.');
        $this->assertSame(QualityProcessState::AWAITING_N2_REVIEW, $process->state, 'N1 questionou: volta ao N2 sem acionar o usuário');
        $process = $this->workflow->reject($process, $this->n2, [$this->reason('Orçamento', 'BUDGET')], 'Mantenho.');
        $process = $this->workflow->assignDesigner($process, $this->n1, $this->designerA->id);
        $this->assertSame($new->id, $process->production_id, 'rodadas do ciclo reaproveitam a Production do ciclo');
        $this->assertSame($this->designerA->id, Production::find($new->id)->user_id, 'só o N1 troca o usuário');
        $process = $this->submit($process, $this->designerA, [], null, ['orders' => [$this->order()]]);
        $process = $this->workflow->approve($process, $this->n1);
        $process = $this->workflow->approve($process, $this->n2, 'Aprovado.');

        // ===== encerramento =====
        $this->assertSame(QualityProcessState::COMPLETED, $process->state);
        $this->assertSame(QualityProcessStatus::COMPLETED, $process->status);
        $this->assertSame($this->n2->id, $process->completed_by);

        $production = Production::find($process->production_id);
        $this->assertSame($new->id, $production->id);
        $this->assertTrue($production->completed);
        $this->assertSame(5, (int) $production->status);
        $this->assertSame('10', (string) $process->Note->fresh()->nstats);

        // histórico: uma rodada nunca é sobrescrita e cada evento carrega estado anterior/posterior
        $types = $process->Events()->pluck('type')->map->value->all();

        foreach ([QualityEventType::DISPATCHED, QualityEventType::DESIGNER_ASSIGNED, QualityEventType::STAGE_SUBMITTED, QualityEventType::REJECTED, QualityEventType::RETURNED_TO_DESIGNER, QualityEventType::RETURNED_TO_N1, QualityEventType::RETURN_FORWARDED, QualityEventType::FORWARDED_TO_N2, QualityEventType::BUDGET_RELEASED, QualityEventType::CONTESTED, QualityEventType::PRODUCTION_CLOSED, QualityEventType::COMPLETED] as $expected) {
            $this->assertContains($expected->value, $types, "evento {$expected->value} ausente");
        }
        $this->assertSame(2, $process->Stages()->where('type', 'PROJECT')->where('kind', QualityStageKind::EXECUTION->value)->where('assigned_user_id', $this->designerA->id)->count());
        $this->assertSame(1, $process->Stages()->where('type', 'PROJECT')->where('kind', QualityStageKind::EXECUTION->value)->where('assigned_user_id', $this->designerB->id)->count());
        $this->assertTrue($process->Events()->whereNotNull('to_state')->whereNotNull('actor_role')->count() === $process->Events()->count());
        $this->assertSame([1, 2, 3], $process->Stages()->where('type', 'PROJECT')->where('kind', QualityStageKind::EXECUTION->value)->pluck('round_number')->all());
    }

    // ---------------------------------------------------------------- despacho e empresa

    public function test_pool_is_a_query_and_dispatch_persists_only_when_n2_dispatches(): void
    {
        $this->assertTrue(app(QualityEligibilityService::class)->query()->get()->contains('id', $this->note->id));
        $this->assertDatabaseCount('quality_processes', 0);
        $this->dispatchToN1();
        $this->assertDatabaseCount('quality_processes', 1);
        $this->assertFalse(app(QualityEligibilityService::class)->query()->get()->contains('id', $this->note->id));
    }

    public function test_duplicate_dispatch_is_blocked(): void
    {
        $this->dispatchToN1();
        $this->expectException(QualityWorkflowException::class);
        $this->workflow->dispatch([$this->note->id], $this->company, $this->manager);
    }

    public function test_only_management_can_dispatch_and_company_needs_an_n1(): void
    {
        try {
            $this->workflow->dispatch([$this->note->id], $this->company, $this->n2);
            $this->fail('N2 não acessa o Pool nem despacha.');
        } catch (QualityWorkflowException) {
            $this->assertDatabaseCount('quality_processes', 0);
        }

        $this->expectExceptionMessage('não possui N1 cadastrado');
        $this->workflow->dispatch([$this->note->id], $this->otherCompany, $this->manager);
    }

    public function test_n1_of_another_company_cannot_act(): void
    {
        $process = $this->dispatchToN1();
        $foreign = User::factory()->create(['analyst' => true, 'company_id' => $this->otherCompany->id, 'superadm' => false, 'admin' => false, 'management' => false, 'can_dispatch' => false, 'contract' => false]);
        QualityMember::create(['user_id' => $foreign->id, 'company_id' => $this->otherCompany->id, 'role' => 'N1']);
        $this->expectExceptionMessage('N1 da empresa');
        $this->workflow->assignDesigner($process, $foreign, $this->designerA->id);
    }

    public function test_n1_cannot_dispatch_to_designer_of_another_company(): void
    {
        $process = $this->dispatchToN1();
        $foreign = $this->designer($this->otherCompany, 'De outra empresa');
        $this->expectExceptionMessage('mesma empresa');
        $this->workflow->assignDesigner($process, $this->n1, $foreign->id);
    }

    public function test_n1_cannot_dispatch_to_user_without_desenho_service(): void
    {
        $process = $this->dispatchToN1();
        $plain   = User::factory()->create(['company_id' => $this->company->id]);
        $this->expectException(QualityWorkflowException::class);
        $this->workflow->assignDesigner($process, $this->n1, $plain->id);
    }

    public function test_service_flag_false_is_not_enabled_in_desenho(): void
    {
        $process  = $this->dispatchToN1();
        $disabled = User::factory()->create(['company_id' => $this->company->id]);
        ServiceUser::create(['user_id' => $disabled->id, 'service_id' => $this->service->uuid, 'service' => false, 'dispatch' => false]);
        $this->expectException(QualityWorkflowException::class);
        $this->workflow->assignDesigner($process, $this->n1, $disabled->id);
    }

    public function test_n2_of_another_company_cannot_act_or_see(): void
    {
        $process = $this->toN2Review();
        $foreign = User::factory()->create(['can_dispatch' => true, 'company_id' => $this->otherCompany->id, 'superadm' => false, 'admin' => false, 'management' => false, 'analyst' => false, 'contract' => false]);
        QualityMember::create(['user_id' => $foreign->id, 'company_id' => $this->otherCompany->id, 'role' => 'N2']);
        $this->assertSame(0, QualityProcess::visibleTo($foreign)->count());
        $this->assertSame(1, QualityProcess::visibleTo($this->n2)->count());
        $this->expectExceptionMessage('Somente o N2');
        $this->workflow->approve($process, $foreign);
    }

    public function test_pool_needs_rules_lists_only_type_1_and_honours_exclusion(): void
    {
        $query = app(QualityEligibilityService::class);
        $type2 = Note::create(['note' => 'T2', 'nstats' => '10', 'type_note' => 2]);
        $this->assertTrue($query->query()->pluck('id')->contains($this->note->id));
        $this->assertFalse($query->query()->pluck('id')->contains($type2->id), 'somente type_note = 1');

        QualityPoolRule::create(['column_search' => 'note', 'condition' => 'Exatamente', 'value' => '100200300', 'exclusion' => true]);
        $this->assertFalse($query->query()->pluck('id')->contains($this->note->id), 'regra de exclusão remove a Nota');

        QualityPoolRule::query()->delete();
        $this->assertSame(0, $query->query()->count(), 'sem critérios o Pool fica vazio');
    }

    public function test_dispatch_requires_configured_activities_and_creates_no_production(): void
    {
        $before  = Production::count();
        $process = $this->dispatchToN1();
        $this->assertNull($process->production_id);
        $this->assertSame($before, Production::count());
        $this->assertSame($process->id, QualityProcess::visibleTo($this->n1)->sole()->id, 'N1 vê a obra da sua empresa antes de existir Production');

        QualitySetting::where('key', 'activities')->delete();
        $other = Note::create(['note' => 'N-2', 'nstats' => '10', 'type_note' => 1]);
        $this->expectExceptionMessage('Defina a atividade');
        $this->workflow->dispatch([$other->id], $this->company, $this->manager);
    }

    public function test_designer_must_be_enabled_in_the_activity_configured_for_the_phase(): void
    {
        $other = Service::create(['service' => 'Orçamento', 'project' => true, 'folder' => 'orc']);
        QualitySetting::where('key', 'activities')->update(['value' => ['PROJECT' => $other->uuid, 'BUDGET' => $this->service2->uuid]]);
        $process = $this->dispatchToN1();
        $this->expectExceptionMessage('habilitado na atividade');
        $this->workflow->assignDesigner($process, $this->n1, $this->designerA->id);
    }

    public function test_roles_come_from_members_per_company_and_inactive_members_lose_access(): void
    {
        $process = $this->dispatchToN1();
        $roles   = app(\App\Services\Quality\QualityRoles::class);
        $this->assertTrue($roles->canActAsN1($this->n1, $process));
        $this->assertFalse($roles->canActAsN2($this->n1, $process));
        $this->assertTrue($roles->canActAsN2($this->n2, $process));
        $this->assertFalse($roles->canActAsN1($this->n2, $process));
        $this->assertFalse($roles->canDispatch($this->n1) || $roles->canDispatch($this->n2));

        QualityMember::where('user_id', $this->n1->id)->update(['active' => false]);
        $this->assertFalse($roles->canActAsN1($this->n1, $process));
        $this->assertSame(0, QualityProcess::visibleTo($this->n1)->count());
    }

    public function test_management_user_who_is_also_member_sees_the_member_view_when_impersonating_and_can_switch(): void
    {
        $dual = User::factory()->create(['management' => true, 'company_id' => $this->company->id, 'superadm' => false, 'admin' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false]);
        QualityMember::create(['user_id' => $dual->id, 'company_id' => $this->company->id, 'role' => 'N1']);
        $roles = app(\App\Services\Quality\QualityRoles::class);

        $this->assertTrue($roles->canManage($dual), 'sem impersonate: visão de Gestão');

        session(['impersonate' => true]);
        $this->assertFalse($roles->canManage($dual), 'impersonate: enxerga como o N1');
        $this->assertFalse($roles->canDispatch($dual));
        $this->assertSame('N1', $roles->roleLabel($dual));

        session(['quality.view_as' => 'manager']);
        $this->assertFalse($roles->canManage($dual), 'impersonate: a Gestão fica sempre oculta, mesmo com escolha anterior');
        $this->assertFalse($roles->isDualMode($dual), 'sem alternância em visão de outro usuário');

        session()->forget('impersonate');
        $this->assertTrue($roles->canManage($dual), 'fora do impersonate a escolha explícita vale');
        $this->assertSame('Gestão', $roles->roleLabel($dual));

        $onlyManager = User::factory()->create(['management' => true, 'superadm' => false, 'admin' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false]);
        session(['impersonate' => true, 'quality.view_as' => null]);
        $this->assertTrue($roles->canManage($onlyManager), 'gestor sem vínculo continua Gestão');
    }

    public function test_n1_n2_discussion_is_internal_unless_shared_with_the_designer(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $this->workflow->addComment($process, $this->n1, 'Dúvida sobre o poste 4.');
        $this->workflow->addComment($process, $this->n2, 'Confirmar medida.', 'ALL');
        $this->workflow->addComment($process, $this->designerA, 'Ok, vou ajustar.', 'INTERNAL');

        $visibilities = $process->Events()->where('type', QualityEventType::COMMENT_ADDED->value)->get()->map(fn ($e) => [$e->actor_role, $e->payload['visibility']])->all();
        $this->assertSame([['N1', 'INTERNAL'], ['N2', 'ALL'], ['DESIGNER', 'ALL']], $visibilities);
    }

    // ---------------------------------------------------------------- hierarquia

    public function test_n2_cannot_skip_n1_and_designer_cannot_send_directly_to_n2(): void
    {
        $process = $this->dispatchToN1();

        try {
            $this->workflow->assignDesigner($process, $this->n2, $this->designerA->id);
            $this->fail('N2 não despacha direto ao desenhista.');
        } catch (QualityWorkflowException) {
        }

        $process = $this->workflow->assignDesigner($process, $this->n1, $this->designerA->id);

        try {
            $this->workflow->approve($process, $this->n2);
            $this->fail('N2 não aprova antes do desenhista e do N1.');
        } catch (QualityWorkflowException) {
        }

        $process = $this->submit($process, $this->designerA, [$this->file('a.pdf')->id]);
        $this->assertSame(QualityProcessState::AWAITING_N1_REVIEW, $process->state, 'desenhista sempre volta ao N1');

        try {
            $this->workflow->approve($process, $this->n2);
            $this->fail('N2 não pode aprovar na etapa do N1.');
        } catch (QualityWorkflowException) {
        }
    }

    public function test_only_the_assigned_designer_can_submit(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $this->expectExceptionMessage('outro usuário');
        $this->submit($process, $this->designerB, [$this->file('a.pdf')->id]);
    }

    public function test_stale_action_on_already_handled_round_is_rejected(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $this->submit($process, $this->designerA, [$this->file('a.pdf')->id]);
        $this->expectExceptionMessage('Ação indisponível');
        $this->submit($process, $this->designerA, [$this->file('b.pdf')->id]);
    }

    // ---------------------------------------------------------------- rejeições

    public function test_n1_rejection_requires_a_reason_and_reason_must_apply_to_the_pass(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $process = $this->submit($process, $this->designerA, [$this->file('a.pdf')->id]);

        try {
            $this->workflow->reject($process, $this->n1, [], 'sem motivo');
            $this->fail('Rejeição sem motivo deve falhar.');
        } catch (QualityWorkflowException $e) {
            $this->assertStringContainsString('categoria e subcategoria', $e->getMessage());
        }

        $this->expectExceptionMessage('não se aplica');
        $this->workflow->reject($process, $this->n1, [$this->reason('Só orçamento', 'BUDGET')]);
    }

    public function test_n2_return_requires_category_and_subcategory(): void
    {
        $process = $this->toN2Review();

        foreach ([[], null] as $reasons) {
            try {
                $this->workflow->reject($process, $this->n2, $reasons ?? [], 'Só comentário não basta.');
                $this->fail('Devolução sem categoria deve falhar.');
            } catch (QualityWorkflowException $e) {
                $this->assertStringContainsString('categoria e subcategoria', $e->getMessage());
            }
        }

        $croqui = QualityRejectionCategory::whereNull('parent_id')->where('name', 'Croqui')->firstOrFail();

        try {
            $this->workflow->reject($process, $this->n2, [['category_id' => $croqui->id]]);
            $this->fail('Categoria com subcategorias exige a subcategoria.');
        } catch (QualityWorkflowException $e) {
            $this->assertStringContainsString('Informe a subcategoria', $e->getMessage());
        }

        $process = $this->workflow->reject($process, $this->n2, [$this->reason('Croqui')]);
        $this->assertSame(QualityProcessState::N2_RETURNED, $process->state);
        $this->assertDatabaseHas('quality_rejections', ['quality_process_id' => $process->id, 'level' => 'N2', 'returned_to_level' => 'N1']);
        $this->assertDatabaseCount('quality_rejection_items', 1);
    }

    public function test_only_n1_can_change_the_designer_and_n2_cannot_open_a_new_request_while_quality_is_open(): void
    {
        $process = $this->toN2Review();
        $process = $this->workflow->reject($process, $this->n2, [$this->reason('Croqui')]);

        try {
            $this->workflow->assignDesigner($process, $this->n2, $this->designerB->id);
            $this->fail('N2 não pode trocar o usuário.');
        } catch (QualityWorkflowException) {
        }

        // novo pedido (nova Production) na atividade da Qualidade, para a mesma Nota, é barrado
        try {
            Production::create(['note_id' => $this->note->id, 'service_id' => $this->service->uuid, 'user_id' => $this->designerB->id, 'company_id' => $this->company->id, 'status' => 2]);
            $this->fail('Nota com Qualidade aberta não aceita novo pedido.');
        } catch (QualityWorkflowException $e) {
            $this->assertStringContainsString('processo de Qualidade', $e->getMessage());
        }
        $this->assertSame(1, Production::where('note_id', $this->note->id)->count());

        $process = $this->workflow->assignDesigner($process, $this->n1, $this->designerB->id);
        $this->assertSame($this->designerB->id, Production::find($process->production_id)->user_id, 'só o N1 troca o usuário');
        $this->assertSame(1, Production::where('note_id', $this->note->id)->count());
    }

    public function test_note_with_open_production_in_the_activity_is_not_in_the_pool(): void
    {
        Production::create(['note_id' => $this->note->id, 'service_id' => $this->service->uuid, 'user_id' => $this->designerA->id, 'company_id' => $this->company->id, 'status' => 2, 'completed' => false]);
        $this->assertFalse(app(QualityEligibilityService::class)->query()->pluck('id')->contains($this->note->id));
    }

    public function test_first_cycle_requires_the_survey_closing_inform(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);

        foreach ([['conclusion' => ''], ['postes' => ''], ['doe' => 'TALVEZ'], ['ma' => ''], ['cadastro' => true, 'postes_c' => '']] as $override) {
            try {
                $this->workflow->submitExecution($process, $this->designerA, [], null, ['survey' => $override + $this->survey()]);
                $this->fail('Informe inválido deve falhar: ' . json_encode($override));
            } catch (QualityWorkflowException) {
            }
        }
        $this->assertSame(QualityProcessState::AWAITING_DESIGNER, $process->fresh()->state);

        $process = $this->workflow->submitExecution($process, $this->designerA, [], null, ['survey' => $this->survey()]);
        $this->assertSame(QualityProcessState::AWAITING_N1_REVIEW, $process->state);
        $production = Production::find($process->production_id);
        $this->assertSame(3, (int) $production->postes_u, 'o informe grava na atividade, como no encerramento normal');
        $this->assertDatabaseHas('analises', ['production_id' => $production->id, 'conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 3]);
    }

    public function test_second_pass_requires_valid_orders(): void
    {
        $process = $this->passToBudgetDesigner();

        try {
            $this->submit($process, $this->designerA, [], null, ['orders' => [['order_number' => '123', 'total_cost' => 1, 'company_cost' => 1, 'client_cost' => 0]]]);
            $this->fail('Ordem inválida deve falhar.');
        } catch (QualityWorkflowException $e) {
            $this->assertStringContainsString('12 dígitos', $e->getMessage());
        }
    }

    public function test_file_from_another_note_is_refused(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $foreign = File::create(['note_id' => Note::create(['note' => 'OUTRA'])->id, 'user_id' => $this->designerA->id, 'file_name' => 'x.pdf', 'path' => 'x.pdf', 'sha256' => 'x']);
        $this->expectExceptionMessage('não pertence à Nota');
        $this->submit($process, $this->designerA, [$foreign->id]);
    }

    // ---------------------------------------------------------------- encerramento

    public function test_final_n2_approval_closes_the_activity_and_completes_without_any_sap_step(): void
    {
        $process    = $this->toFinalN2Review();
        $production = Production::find($process->production_id);
        $this->assertFalse((bool) $production->completed);

        $process = $this->workflow->approve($process, $this->n2);

        $this->assertSame(QualityProcessState::COMPLETED, $process->state);
        $this->assertTrue((bool) Production::find($process->production_id)->completed);
        $this->assertSame('10', (string) $process->Note->fresh()->nstats, 'a Nota não é alterada: não há status SAP a aplicar');
        $types = $process->Events()->pluck('type')->map->value->all();
        $this->assertNotContains('SAP_REQUESTED', $types);
        $this->assertContains('PRODUCTION_CLOSED', $types);
        $this->assertContains('COMPLETED', $types);
    }

    public function test_legacy_pending_closing_can_be_concluded_only_by_n2(): void
    {
        $process = $this->toFinalN2Review();
        $process->forceFill(['state' => QualityProcessState::SAP_FAILED])->save();

        try {
            $this->workflow->retryClosing($process, $this->n1);
            $this->fail('N1 não conclui o encerramento.');
        } catch (QualityWorkflowException $e) {
            $this->assertStringContainsString('Somente o N2', $e->getMessage());
        }

        $process = $this->workflow->retryClosing($process, $this->n2);
        $this->assertSame(QualityProcessState::COMPLETED, $process->state);
        $this->assertTrue((bool) Production::find($process->production_id)->completed);
    }

    // ---------------------------------------------------------------- comentários / visibilidade

    public function test_comments_are_recorded_for_participants_only(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $event   = $this->workflow->addComment($process, $this->n2, 'Atenção ao poste 12.');
        $this->assertSame('N2', $event->actor_role);
        $this->workflow->addComment($process, $this->designerA, 'Ok.');
        $this->expectExceptionMessage('sem participação');
        $this->workflow->addComment($process, $this->designerB, 'Intruso.');
    }

    public function test_visibility_scope_isolates_companies_and_designers(): void
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $foreign = User::factory()->create(['analyst' => true, 'company_id' => $this->otherCompany->id, 'superadm' => false, 'admin' => false, 'management' => false, 'can_dispatch' => false, 'contract' => false]);
        QualityMember::create(['user_id' => $foreign->id, 'company_id' => $this->otherCompany->id, 'role' => 'N1']);

        $this->assertSame(1, QualityProcess::visibleTo($this->n1)->count());
        $this->assertSame(0, QualityProcess::visibleTo($foreign)->count());
        $this->assertSame(1, QualityProcess::visibleTo($this->designerA)->count());
        $this->assertSame(0, QualityProcess::visibleTo($this->designerB)->count());
        $this->assertTrue($this->n1->can('view', $process));
        $this->assertFalse($foreign->can('view', $process));
    }

    // ---------------------------------------------------------------- helpers

    private function designer(Company $company, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'company_id' => $company->id, 'superadm' => false, 'admin' => false, 'management' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false]);
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $this->service->uuid, 'service' => true, 'dispatch' => false]);
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $this->service2->uuid, 'service' => true, 'dispatch' => false]);

        return $user;
    }

    private function survey(): array
    {
        return ['conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 3, 'doe' => 'NAO', 'ma' => 'NAO', 'cadastro' => false, 'info' => 'ok'];
    }

    /** Usuário finaliza a rodada: 1º ciclo exige o informe do Levantamento; 2º ciclo, as ordens. */
    private function submit(QualityProcess $process, User $user, array $files = [], ?string $observation = null, array $data = []): QualityProcess
    {
        if ($process->phase === QualityStageType::PROJECT && !isset($data['survey'])) {
            $data['survey'] = $this->survey();
        }

        return $this->workflow->submitExecution($process, $user, $files, $observation, $data);
    }

    private function dispatchToN1(): QualityProcess
    {
        return $this->workflow->dispatch([$this->note->id], $this->company, $this->manager)->sole();
    }

    private function file(string $name): File
    {
        return File::create(['note_id' => $this->note->id, 'user_id' => $this->designerA->id, 'file_name' => $name, 'path' => "quality/{$name}", 'sha256' => sha1($name . microtime())]);
    }

    /** Motivo válido para o ciclo: categoria existente do catálogo + subcategoria. */
    private function reason(string $name, string $applies = 'PROJECT'): array
    {
        $column   = $applies === 'PROJECT' ? 'applies_project' : 'applies_budget';
        $category = QualityRejectionCategory::query()->whereNull('parent_id')->where('name', $name)->first()
            ?? QualityRejectionCategory::create(['name' => $name, 'applies_project' => $column === 'applies_project', 'applies_budget' => $column === 'applies_budget', 'active' => true]);
        $sub = QualityRejectionCategory::query()->where('parent_id', $category->id)->where($column, true)->orderBy('id')->first();

        return ['category_id' => $category->id] + ($sub ? ['subcategory_id' => $sub->id] : []);
    }

    private function order(): array
    {
        return ['order_number' => '200123456789', 'total_cost' => '100,00', 'company_cost' => '60,00', 'client_cost' => '40,00'];
    }

    private function toN2Review(): QualityProcess
    {
        $process = $this->workflow->assignDesigner($this->dispatchToN1(), $this->n1, $this->designerA->id);
        $process = $this->submit($process, $this->designerA, [$this->file('c.pdf')->id]);

        return $this->workflow->approve($process, $this->n1);
    }

    private function passToBudgetDesigner(): QualityProcess
    {
        // aprovação do N2 no 1º ciclo já abre a atividade do 2º ciclo para o mesmo usuário
        return $this->workflow->approve($this->toN2Review(), $this->n2);
    }

    private function toFinalN2Review(): QualityProcess
    {
        $process = $this->submit($this->passToBudgetDesigner(), $this->designerA, [], null, ['orders' => [$this->order()]]);

        return $this->workflow->approve($process, $this->n1);
    }
}
