<?php

namespace Tests\Feature;

use App\Enum\QualityProcessState;
use App\Http\Livewire\Services\Desenho\Forms\QualityClosing;
use App\Models\{Company, File, Note, QualityMember, QualityPoolRule, QualityProcess, QualityRejectionCategory, QualitySetting, Service, ServiceUser, User};
use App\Services\Quality\QualityWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QualityScreensFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $n2;

    private User $n1;

    private User $designer;

    private User $outsider;

    private QualityProcess $process;

    private Note $note;

    private Service $extraService;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        \App\Models\Bancoupdate::unguarded(fn () => \App\Models\Bancoupdate::create(['inserts' => 0, 'updates' => 0]));

        $flags          = ['superadm' => false, 'admin' => false, 'management' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false];
        $home           = Company::create(['name' => 'Operadora', 'email' => 'op@example.test']);
        $this->company  = Company::create(['name' => 'Empreiteira Alfa', 'email' => 'alfa@example.test']);
        $service        = Service::create(['service' => 'Levantamento', 'project' => true, 'folder' => 'levantamento']);
        $service2       = Service::create(['service' => 'Desenho', 'project' => true, 'folder' => 'desenho']);
        $this->manager  = User::factory()->create(['management' => true, 'company_id' => $home->id] + $flags);
        $this->n2       = User::factory()->create(['company_id' => $this->company->id] + $flags);
        $this->n1       = User::factory()->create(['company_id' => $this->company->id] + $flags);
        $this->designer = User::factory()->create(['company_id' => $this->company->id] + $flags);
        $this->outsider = User::factory()->create(['company_id' => $home->id] + $flags);
        ServiceUser::create(['user_id' => $this->designer->id, 'service_id' => $service->uuid, 'service' => true, 'dispatch' => false]);
        QualityMember::create(['user_id' => $this->n1->id, 'company_id' => $this->company->id, 'role' => 'N1']);
        QualityMember::create(['user_id' => $this->n2->id, 'company_id' => $this->company->id, 'role' => 'N2']);
        QualitySetting::create(['key' => 'activities', 'value' => ['PROJECT' => $service->uuid, 'BUDGET' => $service2->uuid]]);
        QualityPoolRule::create(['column_search' => 'nstats', 'condition' => 'Exatamente', 'value' => '10']);

        $this->note    = Note::create(['note' => '100200300', 'nstats' => '10', 'type_note' => 1]);
        $this->process = app(QualityWorkflowService::class)->dispatch([$this->note->id], $this->company, $this->manager)->sole();
    }

    public function test_every_screen_renders_in_the_standard_layout_in_portuguese(): void
    {
        $manager = $this->manager;
        QualityRejectionCategory::create(['name' => 'Croqui', 'applies_project' => true, 'applies_budget' => true]);

        foreach (['quality.dashboard', 'quality.pool', 'quality.queue', 'quality.history', 'quality.categories', 'quality.settings', 'quality.team'] as $route) {
            $html = $this->ok($this->actingAs($manager)->get(route($route)))->getContent();
            $this->assertStringNotContainsString('OLA MUNDO', $html, $route);
            $this->assertStringContainsString('breadcrumb', $html, $route);
            $this->assertStringContainsString('id="sidebar"', $html, $route);
            $this->assertDoesNotMatchRegularExpression('/\b(PENDING|IN_PROGRESS|AWAITING_[A-Z_]+|PROJECT|BUDGET|DRAWING)\b/', strip_tags(preg_replace('/<script.*?<\/script>|<style.*?<\/style>/s', '', $html)), "{$route} exibe constante crua");
        }
    }

    public function test_process_page_shows_role_specific_actions_and_hides_them_from_others(): void
    {
        $this->ok($this->actingAs($this->n1)->get(route('quality.process', $this->process)))
            ->assertSee('Despachar ao usuário da atividade')->assertSee('Aguardando despacho do N1')->assertDontSee('Aprovar e encaminhar');

        $this->ok($this->actingAs($this->n2)->get(route('quality.process', $this->process)))->assertDontSee('Despachar ao usuário da atividade');
        $this->actingAs($this->outsider)->get(route('quality.process', $this->process))->assertForbidden();
    }

    public function test_each_level_has_its_own_home_and_menu_and_sees_nothing_of_the_other_levels(): void
    {
        // a entrada da Qualidade leva cada nível à sua própria página
        $this->actingAs($this->n1)->get(route('quality.index'))->assertRedirect(route('quality.n1'));
        $this->actingAs($this->n2)->get(route('quality.index'))->assertRedirect(route('quality.n2'));
        $this->actingAs($this->manager)->get(route('quality.index'))->assertRedirect(route('quality.dashboard'));
        $this->actingAs($this->n1)->get(route('quality.dashboard'))->assertRedirect(route('quality.index'));

        $n1 = $this->ok($this->actingAs($this->n1)->get(route('quality.n1')))->assertSee('1 · DESPACHAR')->assertSee('MEUS USUÁRIOS')->assertSee('Minhas obras')
            ->assertDontSee('POOL')->assertDontSee('1 · DECIDIR')->assertDontSee('CONFIGURAÇÃO')->assertDontSee('EQUIPE N1 / N2')->assertDontSee('SAP');
        $this->ok($this->actingAs($this->n2)->get(route('quality.n2')))->assertSee('1 · DECIDIR')->assertSee('Decisões')
            ->assertDontSee('POOL')->assertDontSee('1 · DESPACHAR')->assertDontSee('CONFIGURAÇÃO');

        $this->actingAs($this->n1)->get(route('quality.n2'))->assertForbidden();
        $this->actingAs($this->n2)->get(route('quality.n1'))->assertForbidden();
        $this->actingAs($this->n2)->get(route('quality.n1.users'))->assertForbidden();

        foreach (['quality.pool', 'quality.categories', 'quality.team', 'quality.settings'] as $route) {
            $this->actingAs($this->n1)->get(route($route))->assertForbidden();
            $this->actingAs($this->n2)->get(route($route))->assertForbidden();
        }
        $this->actingAs($this->designer)->get(route('quality.n1'))->assertForbidden();
        $this->actingAs($this->designer)->get(route('quality.index'))->assertForbidden();
    }

    public function test_n1_home_shows_what_to_do_how_long_and_dispatches_in_bulk(): void
    {
        $second = Note::create(['note' => '555666777', 'nstats' => '10', 'type_note' => 1]);
        $other  = app(QualityWorkflowService::class)->dispatch([$second->id], $this->company, $this->manager)->sole();
        $this->process->forceFill(['state_changed_at' => now()->subDays(6)])->save();

        $this->ok($this->actingAs($this->n1)->get(route('quality.n1')))
            ->assertSee('100200300')->assertSee('555666777')->assertSee('há 6 d')->assertSee('Despachar para…');

        $response = $this->actingAs($this->n1)->post(route('quality.n1.dispatch'), ['process_ids' => [$this->process->id, $other->id], 'designer_id' => $this->designer->id]);
        $response->assertSessionHasNoErrors()->assertSessionHas('success', '2 obra(s) despachada(s) ao usuário.');
        $this->assertSame(2, QualityProcess::where('state', QualityProcessState::AWAITING_DESIGNER->value)->count());
        $this->assertSame(2, \App\Models\Production::where('user_id', $this->designer->id)->count());

        $this->ok($this->actingAs($this->n1)->get(route('quality.n1', 'com-usuarios')))->assertSee('Com os usuários')->assertSee('Reatribuir para…');
    }

    public function test_n1_workspace_has_one_page_per_stage_with_search_pagination_and_select_all_filtered(): void
    {
        $workflow = app(QualityWorkflowService::class);
        $ids      = [$this->process->id];

        foreach (range(1, 30) as $i) {
            $note  = Note::create(['note' => sprintf('LOTE-%03d', $i), 'nstats' => '10', 'type_note' => 1, 'rubrica' => $i <= 24 ? 'R-LOTE' : 'R-OUTRA']);
            $ids[] = $workflow->dispatch([$note->id], $this->company, $this->manager)->sole()->id;
        }

        // cada etapa é uma página própria, com contagem nas abas
        $html = $this->ok($this->actingAs($this->n1)->get(route('quality.n1', 'despachar')))->getContent();
        $this->assertStringContainsString('31', $html);
        $this->assertMatchesRegularExpression('/Exibindo\\s*<span[^>]*>1<\\/span>\\s*a\\s*<span[^>]*>20<\\/span>\\s*de\\s*<span[^>]*>31<\\/span>/', $html);
        $this->ok($this->actingAs($this->n1)->get(route('quality.n1', 'analisar')))->assertSee('Nada nesta etapa');

        // busca e filtros por dados da Nota
        $this->ok($this->actingAs($this->n1)->get(route('quality.n1', ['tab' => 'despachar', 'q' => 'LOTE-007'])))->assertSee('LOTE-007')->assertDontSee('LOTE-008');
        $this->ok($this->actingAs($this->n1)->get(route('quality.n1', ['tab' => 'despachar', 'rubrica' => ['R-OUTRA']])))->assertSee('LOTE-030')->assertDontSee('LOTE-001');

        // "selecionar todas do filtro" despacha as 24 obras da rubrica, não só as 20 da página
        $this->actingAs($this->n1)->post(route('quality.n1.dispatch'), [
            'all_filtered' => 1, 'tab' => 'despachar', 'filter_query' => 'rubrica[0]=R-LOTE', 'designer_id' => $this->designer->id,
        ])->assertSessionHas('success', '24 obra(s) despachada(s) ao usuário.');
        $this->assertSame(24, QualityProcess::where('state', QualityProcessState::AWAITING_DESIGNER->value)->count());
        $this->assertSame(7, QualityProcess::where('state', QualityProcessState::AWAITING_N1_DISPATCH->value)->count());
        $this->assertCount(31, $ids);
    }

    public function test_user_pickers_list_only_company_users_enabled_in_the_cycle_activity(): void
    {
        $desenho    = Service::where('service', 'Desenho')->first();
        $onlyCycle2 = $this->plainUser($this->company->id);
        $onlyCycle2->update(['name' => 'Somente Desenho']);
        ServiceUser::create(['user_id' => $onlyCycle2->id, 'service_id' => $desenho->uuid, 'service' => true, 'dispatch' => false]);
        $this->plainUser($this->company->id)->update(['name' => 'Sem Nenhum Servico']);
        $otherCompany = $this->plainUser(Company::create(['name' => 'Gama', 'email' => 'g@example.test'])->id);
        $otherCompany->update(['name' => 'De Outra Empresa']);
        ServiceUser::create(['user_id' => $otherCompany->id, 'service_id' => Service::where('service', 'Levantamento')->first()->uuid, 'service' => true, 'dispatch' => false]);

        // Despachar (1º ciclo): só quem está habilitado na atividade do 1º ciclo
        $this->ok($this->actingAs($this->n1)->get(route('quality.n1', 'despachar')))
            ->assertSee($this->designer->name)->assertDontSee('Somente Desenho')->assertDontSee('Sem Nenhum Servico')->assertDontSee('De Outra Empresa');

        // Reatribuir: agrupado por ciclo, cada grupo com os habilitados naquela atividade
        app(QualityWorkflowService::class)->assignDesigner($this->process, $this->n1, $this->designer->id);
        $html = $this->ok($this->actingAs($this->n1)->get(route('quality.n1', ['tab' => 'com-usuarios', 'phase' => 'PROJECT'])))->getContent();
        $this->assertStringContainsString($this->designer->name, $html);
        $bar = substr($html, strpos($html, 'id="wk-bulkbar"'));
        $this->assertStringNotContainsString('Somente Desenho', $bar);
        $this->assertStringNotContainsString('Sem Nenhum Servico', $bar);
        $this->assertStringNotContainsString('De Outra Empresa', $html);

        $users = $this->ok($this->actingAs($this->n1)->get(route('quality.n1.users')))->getContent();
        $this->assertStringNotContainsString('Sem Nenhum Servico', $users);
        $this->assertStringNotContainsString('De Outra Empresa', $users);
    }

    public function test_process_page_answers_at_a_glance_whose_turn_it_is_and_shows_the_whole_journey(): void
    {
        $workflow = app(QualityWorkflowService::class);

        // aguardando despacho: a vez é do N1
        $this->ok($this->actingAs($this->n1)->get(route('quality.process', $this->process)))
            ->assertSee('É a sua vez')->assertSee('Escolha o usuário desta obra')->assertSee('Jornada da obra')->assertSee('1º ciclo')->assertSee('2º ciclo');
        // para o N2 a mesma obra é "aguardando N1"
        $this->ok($this->actingAs($this->n2)->get(route('quality.process', $this->process)))->assertSee('Aguardando N1')->assertDontSee('É a sua vez');

        // com o usuário: o N1 só acompanha e pode trocar; não é a vez dele
        $process = $workflow->assignDesigner($this->process, $this->n1, $this->designer->id);
        $this->ok($this->actingAs($this->n1)->get(route('quality.process', $process)))
            ->assertSee('Aguardando Usuário')->assertSee($this->designer->name)->assertSee('Trocar o usuário')->assertDontSee('É a sua vez');

        // finalizou: é a vez do N1 analisar
        $process = $workflow->submitExecution($process, $this->designer, [], null, ['survey' => ['conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 2, 'doe' => 'NAO', 'ma' => 'NAO']]);
        $this->ok($this->actingAs($this->n1)->get(route('quality.process', $process)))->assertSee('É a sua vez')->assertSee('Sua análise: aprovar ou rejeitar')->assertSee('Analisar');

        // aprovado pelo N1: é a vez do N2; o N1 vê "Aguardando N2" sem SAP
        $workflow->approve($process, $this->n1);
        $this->ok($this->actingAs($this->n2)->get(route('quality.process', $process)))->assertSee('Sua decisão: aprovar ou devolver ao N1');
        $this->ok($this->actingAs($this->n1)->get(route('quality.process', $process)))->assertSee('Aguardando N2')->assertDontSee('SAP');
    }

    public function test_bulk_note_search_filters_pool_and_n1_lists_and_explains_what_is_missing(): void
    {
        $workflow = app(QualityWorkflowService::class);
        $inPool   = collect(range(1, 25))->map(fn ($i) => Note::create(['note' => sprintf('MASSA-%03d', $i), 'nstats' => '10', 'type_note' => 1]));
        Note::create(['note' => 'FORA-CRITERIO', 'nstats' => '99', 'type_note' => 1]);

        // ---- Pool (Gestão)
        $paste = "MASSA-001, MASSA-002\nMASSA-003;MASSA-004 FORA-CRITERIO NAO-EXISTE 100200300";
        $this->actingAs($this->manager)->post(route('quality.bulk-search'), ['scope' => 'pool', 'notes' => $paste])->assertSessionHas('success', '7 Nota(s) na busca em massa.');
        $html = $this->ok($this->actingAs($this->manager)->withSession(['quality.bulk.pool' => app(\App\Services\Quality\QualityBulkSearch::class)->parse($paste)])->get(route('quality.pool')))->getContent();

        foreach (['MASSA-001', 'MASSA-002', 'MASSA-003', 'MASSA-004'] as $note) {
            $this->assertStringContainsString($note, $html);
        }
        $this->assertStringNotContainsString('MASSA-005', $html);
        $this->assertStringContainsString('4 de 7', $html);
        $this->assertStringContainsString('Fora dos critérios do Pool', $html);
        $this->assertStringContainsString('Nota não encontrada', $html);
        $this->assertStringContainsString('Já está na Qualidade', $html);

        // despacho de "todas do filtro" respeita a busca em massa
        $this->actingAs($this->manager)->withSession(['quality.bulk.pool' => ['MASSA-001', 'MASSA-002', 'MASSA-003']])
            ->post(route('quality.dispatch'), ['all_filtered' => 1, 'filter_query' => '', 'company_id' => $this->company->id])->assertSessionHas('success', '3 Nota(s) despachada(s) ao N1 da empresa.');
        $this->assertSame(3, QualityProcess::whereHas('Note', fn ($q) => $q->where('note', 'like', 'MASSA-%'))->count());

        // ---- N1 (aba despachar)
        $n1notes = ['MASSA-001', 'MASSA-003', '100200300', 'MASSA-010'];
        $page    = $this->ok($this->actingAs($this->n1)->withSession(['quality.bulk.n1.despachar' => $n1notes])->get(route('quality.n1', 'despachar')))->getContent();
        $this->assertStringContainsString('MASSA-001', $page);
        $this->assertStringNotContainsString('MASSA-002', $page);
        $this->assertStringContainsString('3 de 4', $page);
        $this->assertStringContainsString('Não está nas suas obras', $page);

        // limpar
        $this->actingAs($this->n1)->post(route('quality.bulk-search'), ['scope' => 'n1.despachar', 'clear' => 1])->assertSessionHas('success', 'Busca em massa limpa.');
        $this->assertSame([], session('quality.bulk.n1.despachar', []));

        // escopos e permissões
        $this->actingAs($this->n1)->post(route('quality.bulk-search'), ['scope' => 'pool', 'notes' => 'X'])->assertForbidden();
        $this->actingAs($this->n1)->post(route('quality.bulk-search'), ['scope' => 'n2.decidir', 'notes' => 'X'])->assertForbidden();
        $this->actingAs($this->n1)->post(route('quality.bulk-search'), ['scope' => 'qualquer', 'notes' => 'X'])->assertSessionHasErrors('scope');
        $this->assertCount(25, $inPool);
        $this->assertNotNull($workflow);
    }

    public function test_bulk_dispatch_reports_each_failure_without_stopping_the_rest(): void
    {
        $foreign = User::factory()->create(['company_id' => Company::create(['name' => 'Beta', 'email' => 'b@example.test'])->id]);
        $this->actingAs($this->n1)->post(route('quality.n1.dispatch'), ['process_ids' => [$this->process->id], 'designer_id' => $foreign->id])->assertSessionHasErrors('workflow');
        $this->assertSame(QualityProcessState::AWAITING_N1_DISPATCH, $this->process->fresh()->state);
        $this->actingAs($this->n1)->post(route('quality.n1.dispatch'), ['designer_id' => $this->designer->id])->assertSessionHasErrors('process_ids');
        $this->actingAs($this->n2)->post(route('quality.n1.dispatch'), ['process_ids' => [$this->process->id], 'designer_id' => $this->designer->id])->assertForbidden();
    }

    public function test_n1_users_page_lists_users_and_reassigns_in_bulk_only_n1(): void
    {
        $second = User::factory()->create(['company_id' => $this->company->id]);
        ServiceUser::create(['user_id' => $second->id, 'service_id' => Service::where('service', 'Levantamento')->first()->uuid, 'service' => true, 'dispatch' => false]);
        app(QualityWorkflowService::class)->assignDesigner($this->process, $this->n1, $this->designer->id);
        $this->process->forceFill(['state_changed_at' => now()->subDays(3)])->save();

        $this->ok($this->actingAs($this->n1)->get(route('quality.n1.users')))->assertSee($this->designer->name)->assertSee($second->name)->assertSee('Reatribuir as marcadas para')->assertSee('há 3 d');

        $this->actingAs($this->n2)->post(route('quality.n1.reassign'), ['process_ids' => [$this->process->id], 'designer_id' => $second->id])->assertForbidden();
        $this->actingAs($this->n1)->post(route('quality.n1.reassign'), ['process_ids' => [$this->process->id], 'designer_id' => $second->id, 'reason' => 'Parado'])->assertSessionHasNoErrors();

        $process = $this->process->fresh();
        $this->assertSame($second->id, $process->current_designer_id);
        $this->assertSame($second->id, \App\Models\Production::find($process->production_id)->user_id);
        $this->assertTrue($process->Events()->where('type', 'REASSIGNED')->exists());
    }

    public function test_n2_home_lists_decisions_with_time_and_hides_pending_closing_details_from_n1(): void
    {
        $workflow = app(QualityWorkflowService::class);
        $process  = $workflow->assignDesigner($this->process, $this->n1, $this->designer->id);
        $process  = $workflow->submitExecution($process, $this->designer, [], null, ['survey' => ['conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 1, 'doe' => 'NAO', 'ma' => 'NAO']]);
        $workflow->approve($process, $this->n1);
        $this->process->forceFill(['state_changed_at' => now()->subDays(2)])->save();

        $this->ok($this->actingAs($this->n2)->get(route('quality.n2')))->assertSee('100200300')->assertSee('Decidir')->assertSee('há 2 d');
        $this->ok($this->actingAs($this->n2)->get(route('quality.n2', 'empresas')))->assertSee('Empreiteira Alfa');
        $this->ok($this->actingAs($this->n1)->get(route('quality.n1', 'no-n2')))->assertSee('No N2')->assertSee('100200300');
    }

    public function test_impersonated_management_user_who_is_n1_sees_only_the_n1_area(): void
    {
        $dual = User::factory()->create(['management' => true, 'company_id' => $this->company->id, 'superadm' => false, 'admin' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false]);
        QualityMember::create(['user_id' => $dual->id, 'company_id' => $this->company->id, 'role' => 'N1']);

        $this->withSession(['impersonate' => true])->actingAs($dual)->get(route('quality.index'))->assertRedirect(route('quality.n1'));
        $this->ok($this->withSession(['impersonate' => true])->actingAs($dual)->get(route('quality.n1')))
            ->assertSee('1 · DESPACHAR')->assertDontSee('POOL')->assertDontSee('CONFIGURAÇÃO')->assertDontSee('Atuar como')->assertDontSee('EQUIPE N1 / N2');
        $this->withSession(['impersonate' => true])->actingAs($dual)->get(route('quality.pool'))->assertForbidden();
        $this->withSession(['impersonate' => true])->actingAs($dual)->get(route('quality.settings'))->assertForbidden();
    }

    public function test_http_flow_n1_assigns_and_rejects_with_multiple_reasons_and_backend_blocks_foreign_designer(): void
    {
        $foreign = User::factory()->create(['company_id' => Company::create(['name' => 'Beta', 'email' => 'b@example.test'])->id]);
        $this->actingAs($this->n1)->post(route('quality.process.assign', $this->process), ['designer_id' => $foreign->id])
            ->assertSessionHasErrors('workflow');
        $this->assertSame(QualityProcessState::AWAITING_N1_DISPATCH, $this->process->fresh()->state);

        $this->actingAs($this->n2)->post(route('quality.process.assign', $this->process), ['designer_id' => $this->designer->id])->assertForbidden();

        $this->actingAs($this->n1)->post(route('quality.process.assign', $this->process), ['designer_id' => $this->designer->id])->assertSessionHasNoErrors();
        $this->assertSame(QualityProcessState::AWAITING_DESIGNER, $this->process->fresh()->state);

        app(QualityWorkflowService::class)->submitExecution($this->process, $this->designer, [$this->file()->id], null, ['survey' => ['conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 1, 'doe' => 'NAO', 'ma' => 'NAO']]);
        $a     = QualityRejectionCategory::create(['name' => 'Croqui', 'applies_project' => true, 'applies_budget' => false]);
        $b     = QualityRejectionCategory::create(['name' => 'Documentação', 'applies_project' => true, 'applies_budget' => false]);
        $child = QualityRejectionCategory::create(['name' => 'Medida incorreta', 'parent_id' => $a->id, 'applies_project' => true, 'applies_budget' => false]);

        $this->actingAs($this->n1)->post(route('quality.process.reject', $this->process), ['reasons' => []])->assertSessionHasErrors('reasons');
        $this->actingAs($this->n1)->post(route('quality.process.reject', $this->process), ['observation' => 'Ajustar', 'reasons' => [
            ['category_id' => $a->id, 'subcategory_id' => $child->id, 'observation' => 'Vão A-B'],
            ['category_id' => $b->id],
        ]])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('quality_rejection_items', 2);
        $this->assertSame(QualityProcessState::AWAITING_DESIGNER, $this->process->fresh()->state);
    }

    public function test_dedicated_desenho_form_only_opens_for_the_assigned_designer_and_submits_to_n1(): void
    {
        app(QualityWorkflowService::class)->assignDesigner($this->process, $this->n1, $this->designer->id);
        $file = $this->file();

        Livewire::actingAs($this->outsider)->test(QualityClosing::class)->call('openQualityClosing', $this->process->fresh()->production_id)->assertSet('viewForm', false);

        Livewire::actingAs($this->designer)->test(QualityClosing::class)
            ->call('openQualityClosing', $this->process->fresh()->production_id)
            ->assertSet('viewForm', true)
            ->assertSee('Atividade da Qualidade')->assertSee('Informe de encerramento')->assertSee('Concluir Levantamento')
            ->call('submit')
            ->assertHasErrors('workflow')
            ->set('conclusion', 'EM CONTATO COM CLIENTE')->set('postes', '4')->set('doe', 'NAO')->set('ma', 'NAO')
            ->set('selectedFileIds', [(string) $file->id])
            ->call('submit')
            ->assertSet('awaitingFiles', true)
            ->call('afterFilesSaved') // o componente de arquivos devolve o evento após salvar os anexos
            ->assertSet('viewForm', false);

        $this->assertSame(QualityProcessState::AWAITING_N1_REVIEW, $this->process->fresh()->state);
    }

    public function test_levantamento_list_shows_quality_activity_and_hides_transfer_and_opens_dedicated_form(): void
    {
        app(QualityWorkflowService::class)->assignDesigner($this->process, $this->n1, $this->designer->id);
        $service = Service::where('service', 'Levantamento')->first();

        Livewire::actingAs($this->designer)->test(\App\Http\Livewire\Services\Levantamento\Main::class, ['service' => $service->uuid])
            ->assertSee('QUALIDADE')->assertSee('1º ciclo')->assertSee('Ver detalhes')
            ->call('getQualityDetails', $this->process->fresh()->production_id)
            ->assertDispatchedBrowserEvent('showModal', ['id' => 'quality_closing_form'])
            ->call('getAnalise', $this->process->fresh()->production_id, $this->note->id)
            ->assertDispatchedBrowserEvent('showModal', ['id' => 'quality_closing_form'])
            ->call('goTransferProd', $this->process->fresh()->production_id)
            ->assertDispatchedBrowserEvent('swal');
    }

    public function test_pagination_is_portuguese_and_uses_the_bootstrap_layout(): void
    {
        foreach (range(1, 25) as $i) {
            Note::create(['note' => "PAG-{$i}", 'nstats' => '10', 'type_note' => 1]);
        }
        $html = $this->ok($this->actingAs($this->manager)->get(route('quality.pool')))->getContent();
        $this->assertStringContainsString('Exibindo', $html);
        $this->assertStringContainsString('resultado(s)', $html);
        $this->assertStringContainsString('Próxima', $html);
        $this->assertStringNotContainsString('Showing', $html);
        $this->assertStringNotContainsString('Next', $html);
        $this->assertStringContainsString('pagination pagination-sm', $html);
    }

    public function test_timeline_is_paginated_filterable_and_hides_closing_events_from_n1(): void
    {
        $workflow = app(QualityWorkflowService::class);
        $process  = $workflow->assignDesigner($this->process, $this->n1, $this->designer->id);

        foreach (range(1, 14) as $i) {
            $process = $workflow->reassignDesigner($process, $this->n1, $i % 2 ? $this->extraDesigner()->id : $this->designer->id, "troca {$i}");
        }

        $component = Livewire::actingAs($this->n1)->test(\App\Http\Livewire\Quality\ProcessTimeline::class, ['processId' => $process->id]);
        $component->assertSee('Atividade reatribuída a outro usuário')->assertSee('Carregar mais')->assertSeeHtml('wire:poll.20s');
        $component->call('loadMore')->assertDontSee('Carregar mais');
        $component->call('setFilter', 'decisoes')->assertSee('Nenhum evento neste filtro')->call('setFilter', 'fluxo')->assertSee('reatribuída');

        // outra empresa não abre
        $foreign = $this->plainUser(Company::create(['name' => 'Zeta', 'email' => 'z@example.test'])->id);
        QualityMember::create(['user_id' => $foreign->id, 'company_id' => $foreign->company_id, 'role' => 'N1']);
        Livewire::actingAs($foreign)->test(\App\Http\Livewire\Quality\ProcessTimeline::class, ['processId' => $process->id])->assertForbidden();
    }

    public function test_chat_sends_messages_with_visibility_avatars_and_blocks_outsiders(): void
    {
        $chat = Livewire::actingAs($this->n1)->test(\App\Http\Livewire\Quality\ProcessChat::class, ['processId' => $this->process->id]);
        $chat->assertSeeHtml('wire:poll.8s')->assertSee('Nenhuma mensagem ainda')
            ->set('text', 'Preciso conferir o poste 7.')->set('visibility', 'INTERNAL')->call('send')
            ->assertSee('Preciso conferir o poste 7.')->assertSee('Você')->assertSet('text', '');

        $n2chat = Livewire::actingAs($this->n2)->test(\App\Http\Livewire\Quality\ProcessChat::class, ['processId' => $this->process->id]);
        $n2chat->assertSee('Preciso conferir o poste 7.')->set('text', 'Combinado, pode seguir.')->set('visibility', 'ALL')->call('send')->assertSee('visível ao usuário');
        $chat->call('$refresh')->assertSee('Combinado, pode seguir.');

        $chat->set('text', '   ')->call('send')->assertHasErrors('text');
        Livewire::actingAs($this->outsider)->test(\App\Http\Livewire\Quality\ProcessChat::class, ['processId' => $this->process->id])->assertForbidden();
        $this->assertSame(2, $this->process->Events()->where('type', 'COMMENT_ADDED')->count());
    }

    public function test_file_gallery_lists_files_and_n1_n2_can_download_but_others_cannot(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('quality-tests/croqui.pdf', 'PDFDATA');
        \Illuminate\Support\Facades\Storage::disk('public')->put('quality-tests/foto.png', 'PNGDATA');
        $pdf   = File::create(['note_id' => $this->note->id, 'user_id' => $this->designer->id, 'file_name' => 'croqui.pdf', 'path' => 'quality-tests/croqui.pdf', 'ext' => 'pdf', 'size' => 7, 'sha256' => 'a']);
        $img   = File::create(['note_id' => $this->note->id, 'user_id' => $this->designer->id, 'file_name' => 'foto.png', 'path' => 'quality-tests/foto.png', 'ext' => 'png', 'size' => 7, 'sha256' => 'b']);
        $other = File::create(['note_id' => Note::create(['note' => 'OUTRA'])->id, 'user_id' => $this->designer->id, 'file_name' => 'x.pdf', 'path' => 'quality-tests/x.pdf', 'ext' => 'pdf', 'sha256' => 'c']);

        $gallery = Livewire::actingAs($this->n2)->test(\App\Http\Livewire\Quality\ProcessFiles::class, ['processId' => $this->process->id]);
        $gallery->assertSee('croqui.pdf')->assertSee('foto.png')->assertSee('Baixar tudo')->assertDontSee('x.pdf')
            ->call('setKind', 'pdf')->assertSee('croqui.pdf')->assertDontSee('foto.png')
            ->call('setKind', 'todos')->set('search', 'foto')->assertSee('foto.png')->assertDontSee('croqui.pdf');

        foreach ([$this->n1, $this->n2, $this->manager] as $user) {
            $this->actingAs($user)->get(route('quality.process.file', [$this->process, $pdf]))->assertOk()->assertHeader('content-disposition');
            $this->actingAs($user)->get(route('quality.process.file.preview', [$this->process, $img]))->assertOk();
        }
        $this->actingAs($this->n1)->get(route('quality.process.files.zip', $this->process))->assertOk()->assertHeader('content-type', 'application/zip');

        // outra Nota → 404; quem não vê a obra → 403
        $this->actingAs($this->n1)->get(route('quality.process.file', [$this->process, $other]))->assertNotFound();
        $foreign = $this->plainUser(Company::create(['name' => 'Omega', 'email' => 'o@example.test'])->id);
        QualityMember::create(['user_id' => $foreign->id, 'company_id' => $foreign->company_id, 'role' => 'N1']);
        $this->actingAs($foreign)->get(route('quality.process.file', [$this->process, $pdf]))->assertForbidden();
        $this->actingAs($foreign)->get(route('quality.process.files.zip', $this->process))->assertForbidden();
        $this->actingAs($this->outsider)->get(route('quality.process.file', [$this->process, $pdf]))->assertForbidden();
    }

    public function test_sidebar_badges_and_stage_strip_poll_and_update_with_livewire(): void
    {
        $sidebar = Livewire::actingAs($this->n1)->test(\App\Http\Livewire\Quality\Sidebar::class, ['route' => 'quality.n1', 'tab' => 'despachar']);
        $sidebar->assertSeeHtml('wire:poll.15s')->assertSee('DESPACHAR')->assertSeeHtml('text-bg-warning ms-auto');
        $strip = Livewire::actingAs($this->n1)->test(\App\Http\Livewire\Quality\StageStrip::class, ['level' => 'n1', 'tab' => 'despachar']);
        $strip->assertSeeHtml('wire:poll.15s')->assertSee('Despachar');

        // muda o estado fora da tela: o próximo poll (refresh) já traz o novo estado
        $workflow = app(QualityWorkflowService::class);
        $process  = $workflow->assignDesigner($this->process, $this->n1, $this->designer->id);
        $workflow->submitExecution($process, $this->designer, [], null, ['survey' => ['conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 1, 'doe' => 'NAO', 'ma' => 'NAO']]);

        $html = $sidebar->call('$refresh')->lastRenderedDom;
        $this->assertMatchesRegularExpression('/ANALISAR<\/span>\s*<span class="badge[^>]*>1<\/span>/', $html);
        $this->assertDoesNotMatchRegularExpression('/DESPACHAR<\/span>\s*<span class="badge/', $html);
        $strip->call('$refresh')->assertSee('mais antiga');

        // o N2 também tem badges próprios
        $n2 = Livewire::actingAs($this->n2)->test(\App\Http\Livewire\Quality\Sidebar::class, ['route' => 'quality.n2', 'tab' => 'decidir']);
        $n2->assertSee('DECIDIR')->assertDontSee('ANALISAR');
    }

    public function test_decision_panel_offers_approve_or_reject_and_shows_one_form_at_a_time(): void
    {
        $workflow = app(QualityWorkflowService::class);
        $process  = $workflow->assignDesigner($this->process, $this->n1, $this->designer->id);
        $process  = $workflow->submitExecution($process, $this->designer, [], null, ['survey' => ['conclusion' => 'EM CONTATO COM CLIENTE', 'postes' => 1, 'doe' => 'NAO', 'ma' => 'NAO']]);

        $html = $this->ok($this->actingAs($this->n1)->get(route('quality.process', $process)))->assertSee('Escolha o que fazer')->assertSee('Aprovar')->assertSee('Rejeitar')->getContent();
        $this->assertMatchesRegularExpression('/id="q-approve" class="decision-pane d-none"/', $html, 'aprovar começa oculto');
        $this->assertMatchesRegularExpression('/id="q-reject" class="decision-pane d-none"/', $html, 'rejeitar começa oculto');
        $this->assertStringContainsString('data-pane="#q-approve"', $html);
        $this->assertStringContainsString('data-pane="#q-reject"', $html);
    }

    public function test_user_can_read_the_details_without_starting_the_finalization(): void
    {
        app(QualityWorkflowService::class)->assignDesigner($this->process, $this->n1, $this->designer->id);
        $this->note->update(['material' => 'PROJETO BT ZERO', 'rubrica' => 'BT Zero', 'lexp' => 'SERRA']);
        $productionId = $this->process->fresh()->production_id;

        // outro usuário não lê
        Livewire::actingAs($this->outsider)->test(QualityClosing::class)->call('openQualityDetails', $productionId)->assertSet('viewForm', false);

        Livewire::actingAs($this->designer)->test(QualityClosing::class)
            ->call('openQualityDetails', $productionId)
            ->assertSet('viewForm', true)->assertSet('detailsOnly', true)
            ->assertSee('Somente leitura')->assertSee('Dados da Nota/OV')->assertSee('PROJETO BT ZERO')->assertSee('BT Zero')->assertSee('SERRA')->assertSee('Iniciar finalização')
            ->assertDontSee('Informe de encerramento')->assertDontSee('Enviar ao N1')
            ->call('startFinalization')
            ->assertSet('detailsOnly', false)->assertSee('Informe de encerramento')->assertSee('Enviar ao N1');

        // nada foi iniciado nem enviado só por ler
        $this->assertSame(QualityProcessState::AWAITING_DESIGNER, $this->process->fresh()->state);
        $this->assertSame('PENDING', $this->process->Stages()->where('kind', 'EXECUTION')->first()->status->value);
        $this->assertFalse($this->process->Events()->where('type', 'STAGE_STARTED')->exists());
    }

    public function test_pool_filters_by_note_data(): void
    {
        Note::create(['note' => 'OUTRA-NOTA', 'nstats' => '10', 'type_note' => 1, 'rubrica' => 'R-X']);
        $this->note->update(['rubrica' => 'R-A']);
        $html = $this->ok($this->actingAs($this->manager)->get(route('quality.pool', ['rubrica' => ['R-X']])))->getContent();
        $this->assertStringContainsString('OUTRA-NOTA', $html);
        $this->assertStringNotContainsString('100200300', $html);
    }

    public function test_management_configures_team_pool_rules_and_activities_and_n1_cannot(): void
    {
        $candidate = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->manager)->post(route('quality.team.store'), ['user_id' => $candidate->id, 'company_id' => $this->company->id, 'role' => 'N1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quality_members', ['user_id' => $candidate->id, 'role' => 'N1', 'active' => 1]);

        $this->actingAs($this->manager)->post(route('quality.rules.store'), ['column_search' => 'rubrica', 'condition' => 'Em', 'value' => 'A1, B2'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quality_pool_rules', ['column_search' => 'rubrica', 'value' => '["A1","B2"]']);

        $service            = Service::create(['service' => 'Orçamento', 'project' => true, 'folder' => 'orc']);
        $this->extraService = Service::create(['service' => 'Outro', 'project' => true, 'folder' => 'outro']);
        $this->actingAs($this->manager)->put(route('quality.settings.save'), ['activity_project' => $service->uuid, 'activity_budget' => $this->extraService->uuid])->assertSessionHasNoErrors();
        $this->assertSame($service->uuid, QualitySetting::where('key', 'activities')->first()->value['PROJECT']);

        $this->actingAs($this->n1)->post(route('quality.team.store'), ['user_id' => $candidate->id, 'company_id' => $this->company->id, 'role' => 'N2'])->assertForbidden();
        $this->actingAs($this->n1)->post(route('quality.rules.store'), ['column_search' => 'rubrica', 'condition' => 'Em', 'value' => 'x'])->assertForbidden();
        $this->actingAs($this->n1)->post(route('quality.dispatch'), ['note_ids' => [1], 'company_id' => $this->company->id])->assertForbidden();
    }

    public function test_n1_and_n2_discuss_in_the_process_area_and_designer_sees_only_shared_messages(): void
    {
        $this->actingAs($this->n1)->post(route('quality.process.comment', $this->process), ['comment' => 'Segredo do N1', 'visibility' => 'INTERNAL'])->assertSessionHasNoErrors();
        $this->actingAs($this->n2)->post(route('quality.process.comment', $this->process), ['comment' => 'Resposta do N2', 'visibility' => 'INTERNAL'])->assertSessionHasNoErrors();
        $this->actingAs($this->n1)->post(route('quality.process.comment', $this->process), ['comment' => 'Orientação pública', 'visibility' => 'ALL'])->assertSessionHasNoErrors();

        $this->ok($this->actingAs($this->n2)->get(route('quality.process', $this->process)))->assertSee('Segredo do N1')->assertSee('Resposta do N2')->assertSee('Discussão');

        app(QualityWorkflowService::class)->assignDesigner($this->process, $this->n1, $this->designer->id);
        Livewire::actingAs($this->designer)->test(QualityClosing::class)->call('openQualityClosing', $this->process->fresh()->production_id)
            ->assertSee('Orientação pública')->assertDontSee('Segredo do N1')->assertDontSee('Resposta do N2');
    }

    private function ok(\Illuminate\Testing\TestResponse $response): \Illuminate\Testing\TestResponse
    {
        if ($response->status() >= 500) {
            $this->fail('HTTP ' . $response->status() . ': ' . ($response->exception?->getMessage() ?? 'sem detalhe'));
        }

        return $response->assertOk();
    }

    private function plainUser(string $companyId): User
    {
        return User::factory()->create(['company_id' => $companyId, 'superadm' => false, 'admin' => false, 'management' => false, 'can_dispatch' => false, 'analyst' => false, 'contract' => false]);
    }

    private function extraDesigner(): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        ServiceUser::create(['user_id' => $user->id, 'service_id' => Service::where('service', 'Levantamento')->first()->uuid, 'service' => true, 'dispatch' => false]);

        return $user;
    }

    private function file(): File
    {
        return File::create(['note_id' => $this->note->id, 'user_id' => $this->designer->id, 'file_name' => 'croqui.pdf', 'path' => 'quality/croqui.pdf', 'sha256' => sha1(microtime())]);
    }
}
