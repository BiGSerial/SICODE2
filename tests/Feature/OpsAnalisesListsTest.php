<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Analises\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Analises\Main as ServiceMain;
use App\Models\{Company, Note, Production, Service, User};
use App\Services\Analises\AnalysisListRowPresenter;
use App\Support\Ops\RowState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Análises (Despacho, Parceira, Controle e Serviços) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsAnalisesListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'dispatchs/analises/main',
        'dispatchs/analises/stack',
        'dispatchs/analises/partials/stack-mass-actions',
        'services/analises/main',
    ];

    private Service $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        $this->user    = User::factory()->create(['contract' => false]);
        $this->service = Service::create(['service' => 'Analise', 'folder' => 'analises', 'status' => 2]);
        $this->actingAs($this->user);
    }

    private function note(string $number, array $extra = []): Note
    {
        return Note::create($extra + [
            'note'       => $number,
            'dt_status'  => now()->subDays(2),
            'dt_created' => now()->subDays(5),
            'nstats'     => 2,
            'type_note'  => 2,
            'rubrica'    => 'Mercado',
            'group2'     => 'G2',
            'material'   => 'PROJETO NOVO',
        ]);
    }

    private function production(Note $note, array $extra = []): Production
    {
        return Production::create($extra + [
            'company_id'  => \App\Models\Company::query()->firstOrCreate(['name' => 'Empresa de teste'], ['email' => 'teste@example.com'])->id,
            'note_id'     => $note->id,
            'service_id'  => $this->service->uuid,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->nstats,
            'dispatch_at' => now()->subDays(3),
            'status'      => 1,
        ]);
    }

    public function test_dispatch_list_renders_in_the_pattern_and_shows_the_note(): void
    {
        $this->note('4000099001');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Despacho · Analise')
            ->assertSee('4000099001')
            ->assertSee('Atribuir')
            ->assertSee('ops-list');
    }

    public function test_partner_list_reuses_the_dispatch_screen_without_material_filter_or_mmgd(): void
    {
        $this->note('4000099002');

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Parceira · Analise')
            ->assertSee('4000099002')
            ->assertSee('Dt Status')
            ->assertDontSee('Ocultar MMGD');
    }

    public function test_dispatch_filters_apply_clear_and_search(): void
    {
        $this->note('4000099010', ['rubrica' => 'Mercado', 'material' => 'MAT A']);
        $this->note('4000099011', ['rubrica' => 'Outra', 'material' => 'MAT B']);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['Mercado']])
            ->assertSet('filters', ['rubrica' => ['Mercado']])
            ->assertSee('4000099010')
            ->assertDontSee('4000099011');

        $test->call('clearAllCriteria')->assertSet('filters', [])->assertSee('4000099011');

        $test->call('applyListFilters', ['material' => ['MAT B']])
            ->assertSee('4000099011')
            ->assertDontSee('4000099010');

        $test->call('clearAllCriteria')
            ->set('search', '4000099011')
            ->assertSee('4000099011')
            ->assertDontSee('4000099010');
    }

    public function test_dispatch_list_ignores_unknown_filter_values_and_keys(): void
    {
        $this->note('4000099020');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['inexistente'], 'hack' => ['1']])
            ->assertSet('filters', []);
    }

    public function test_dispatch_list_select_all_marks_only_dispatchable_rows_and_clears(): void
    {
        $blocked = $this->note('4000099030');
        $this->production($blocked, ['user_id' => $this->user->id, 'status' => 2]);

        $free = $this->note('4000099031');

        $test      = Livewire::test(Main::class, ['service' => $this->service->uuid]);
        $component = $test->instance();

        // No navegador o mestre e o clique viajam juntos; aqui simulamos o estado já marcado.
        $component->selectall = true;
        $component->setSelectAll();

        $this->assertSame([(string) $free->id], $component->selected);

        $test->call('clearSelection')->assertSet('selected', []);
    }

    public function test_row_state_maps_every_block_level_of_the_analysis_evaluator(): void
    {
        $map = [
            0 => RowState::FREE,
            1 => RowState::ASSIGNED,
            2 => RowState::STACK,
            3 => RowState::DONE,
            4 => RowState::LATE,
            5 => RowState::ASSIGNED,
        ];

        foreach ($map as $block => $state) {
            $this->assertSame($state, AnalysisListRowPresenter::stateFromBlock($block));
        }

        $this->assertSame(RowState::DONE, AnalysisListRowPresenter::stateFromBlock(5, 'production_completed_waiting_confirmation'));
    }

    public function test_real_deadline_keeps_the_original_30_day_limits(): void
    {
        $tone = fn (int $elapsed) => AnalysisListRowPresenter::deadline(Carbon::now()->subDays($elapsed))['tone'];

        // Restante = 30 - dias corridos desde a criação: 10+ verde; 6–9 âmbar; 0–5 vermelho; vencido cinza.
        $this->assertSame('green', $tone(20));
        $this->assertSame('amber', $tone(21));
        $this->assertSame('amber', $tone(24));
        $this->assertSame('red', $tone(25));
        $this->assertSame('red', $tone(30));
        $this->assertSame('muted', $tone(31));
        $this->assertSame('muted', AnalysisListRowPresenter::deadline(null)['tone']);
        $this->assertSame('Vence hoje', AnalysisListRowPresenter::deadline(Carbon::now()->subDays(30))['sub']);
    }

    public function test_control_list_renders_filters_by_user_and_has_no_per_row_component(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $target  = User::factory()->create(['contract' => false]);
        $other   = User::factory()->create(['contract' => false]);

        $targetProduction = $this->production($this->note('4000099040'), ['user_id' => $target->id, 'company_id' => $company->id, 'status' => 2]);
        $this->production($this->note('4000099041'), ['user_id' => $other->id, 'company_id' => $company->id, 'status' => 2]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Analise')
            ->assertSee('4000099040')
            ->set('selected', [$targetProduction->id + 1000])
            ->call('filterUser', $target->id)
            ->assertSet('filters', ['user' => [(string) $target->id]])
            ->assertSet('selected', [])
            ->assertDontSee('4000099041');

        $this->assertSame([$targetProduction->id], $test->instance()->getListsProperty()->pluck('id')->all());

        $test->call('clearAllCriteria')->assertSet('filters', [])->assertSee('4000099041');
    }

    public function test_control_status_cards_filter_by_status_and_partner_control_renders(): void
    {
        $this->production($this->note('4000099050'), ['status' => 1]);
        $this->production($this->note('4000099051'), ['status' => 2, 'user_id' => $this->user->id]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 2)
            ->assertSet('filters', ['status' => ['2']])
            ->assertSee('4000099051')
            ->assertDontSee('4000099050')
            ->call('toggleStatus', 2)
            ->assertSet('filters', []);

        Livewire::test(PartnerStack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000099050');
    }

    public function test_control_export_query_follows_the_same_filters_as_the_list(): void
    {
        $this->production($this->note('4000099060'), ['status' => 1]);
        $this->production($this->note('4000099061'), ['status' => 2, 'user_id' => $this->user->id]);

        $component = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 2)
            ->instance();

        $this->assertCount(1, $component->getExportsProperty()->get());
        $this->assertSame(['2' => 1, '1' => 1], collect($component->statusCounts)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->sortKeysDesc()->all());
    }

    public function test_control_reattribute_and_remove_confirm_only_with_the_pending_key(): void
    {
        $production = $this->production($this->note('4000099070'), ['status' => 2, 'user_id' => $this->user->id]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('askRemove', $production->id)
            ->assertSet('pendingProductionId', $production->id)
            ->call('confirmRemove', 'chave-errada');

        $this->assertNotNull(Production::find($production->id), 'chave errada não remove');

        $test->call('confirmRemove', hash('sha512', (string) $production->id))->assertSet('pendingProductionId', null);

        $this->assertNull(Production::find($production->id));
    }

    public function test_services_list_renders_and_filters(): void
    {
        $this->note('4000099080', ['rubrica' => 'Mercado']);
        $this->note('4000099081', ['rubrica' => 'Outra']);

        $test = Livewire::test(ServiceMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Serviços · Analise')
            ->assertSee('Assumir');

        $test->set('search', '4000099081')->assertDontSee('4000099080');
        $test->call('clearAllCriteria')->assertSet('search', '');
        $test->call('applyListFilters', ['rubrica' => ['Mercado']])->assertSet('filters', ['rubrica' => ['Mercado']]);
    }

    public function test_views_have_no_inline_css_literal_colors_or_legacy_classes(): void
    {
        foreach (self::VIEWS as $view) {
            $content = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

            $this->assertStringNotContainsString('<style', $content, "{$view}: <style>");
            $this->assertStringNotContainsString('<script', $content, "{$view}: <script>");
            $this->assertStringNotContainsString('text-bg-', $content, "{$view}: text-bg-*");
            $this->assertStringNotContainsString('components.filter.filter', $content, "{$view}: filtro legado");
            $this->assertStringNotContainsString('.bounce.', $content, "{$view}: debounce inexistente");
            $this->assertStringNotContainsString("@livewire('production.actions", $content, "{$view}: subcomponente por linha");
            $this->assertDoesNotMatchRegularExpression('/\btable-(primary|warning|success|danger|secondary|dark|striped)\b/', $content, "{$view}: table-*");
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{6}\b|rgba?\(/', $content, "{$view}: cor literal");
            $this->assertDoesNotMatchRegularExpression('/style="[^"]*(color|cursor|background)/', $content, "{$view}: style de cor/cursor");
        }
    }
}
