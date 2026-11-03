<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\AnalisesPre\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Analises_pre\Main as ServiceMain;
use App\Models\{Company, Note, Production, Service, User};
use App\Services\Dispatch\PreAnalysisListRowPresenter;
use App\Support\Ops\RowState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Análises Pré (Despacho, Parceira, Controle e Serviços) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsAnalisesPreListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'dispatchs/analises-pre/main',
        'dispatchs/analises-pre/stack',
        'dispatchs/analises-pre/partials/stack-mass-actions',
        'services/analises_pre/main',
    ];

    private Service $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        $this->user    = User::factory()->create(['contract' => false]);
        $this->service = Service::create(['service' => 'Pré-análise', 'folder' => 'analises_pre', 'status' => 2]);
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
        $this->note('4000098001');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Despacho · Pré-análise')
            ->assertSee('4000098001')
            ->assertSee('Atribuir')
            ->assertSee('ops-list');
    }

    public function test_partner_list_reuses_the_dispatch_screen(): void
    {
        $this->note('4000098002');

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000098002')
            ->assertSee('ops-list');
    }

    public function test_dispatch_filters_apply_clear_and_search(): void
    {
        $this->note('4000098010', ['rubrica' => 'Mercado']);
        $this->note('4000098011', ['rubrica' => 'Outra']);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['Mercado']])
            ->assertSet('filters', ['rubrica' => ['Mercado']])
            ->assertSee('4000098010')
            ->assertDontSee('4000098011');

        $test->call('clearAllCriteria')->assertSet('filters', [])->assertSee('4000098011');

        $test->set('search', '4000098011')
            ->assertSee('4000098011')
            ->assertDontSee('4000098010');

        $test->call('clearAllCriteria')->assertSet('search', '');
    }

    public function test_dispatch_list_ignores_unknown_filter_values_and_keys(): void
    {
        $this->note('4000098020');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['inexistente'], 'hack' => ['1']])
            ->assertSet('filters', []);
    }

    public function test_dispatch_list_select_all_marks_only_dispatchable_rows_and_clears(): void
    {
        $blocked = $this->note('4000098030');
        $this->production($blocked, ['user_id' => $this->user->id, 'status' => 2]);

        $free = $this->note('4000098031');

        $test                 = Livewire::test(Main::class, ['service' => $this->service->uuid]);
        $component            = $test->instance();
        $component->selectall = true;
        $component->setSelectAll();
        $this->assertSame([(string) $free->id], $component->selected);
        $test->call('clearSelection')->assertSet('selected', []);
    }

    public function test_only_free_toggle_hides_notes_already_assigned(): void
    {
        $assigned = $this->note('4000098040');
        $this->production($assigned, ['user_id' => $this->user->id, 'status' => 2]);
        $this->note('4000098041');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('filterStatus')
            ->assertSet('not_assigned', true)
            ->assertSee('4000098041')
            ->assertDontSee('4000098040');
    }

    public function test_row_state_maps_every_block_level_of_the_evaluator(): void
    {
        $presenter = new PreAnalysisListRowPresenter();
        $map       = [0 => RowState::FREE, 1 => RowState::ASSIGNED, 2 => RowState::STACK, 3 => RowState::DONE, 4 => RowState::LATE, 5 => RowState::ASSIGNED];

        foreach ($map as $block => $state) {
            $this->assertSame($state, $presenter->stateFromBlock($block));
        }
    }

    public function test_real_deadline_keeps_the_original_30_day_limits(): void
    {
        $presenter = new PreAnalysisListRowPresenter();

        // Restante = 30 - dias corridos desde a criação: 10+ verde; 6–9 âmbar; 0–5 vermelho; vencido cinza.
        $this->assertSame('green', $presenter->deadlineTone(10));
        $this->assertSame('amber', $presenter->deadlineTone(9));
        $this->assertSame('amber', $presenter->deadlineTone(6));
        $this->assertSame('red', $presenter->deadlineTone(5));
        $this->assertSame('red', $presenter->deadlineTone(0));
        $this->assertSame('muted', $presenter->deadlineTone(-1));
        $this->assertSame('muted', $presenter->deadlineTone(null));
    }

    public function test_control_list_renders_filters_by_user_and_has_no_per_row_component(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $target  = User::factory()->create(['contract' => false]);
        $other   = User::factory()->create(['contract' => false]);

        $targetProduction = $this->production($this->note('4000098050'), ['user_id' => $target->id, 'company_id' => $company->id, 'status' => 2]);
        $this->production($this->note('4000098051'), ['user_id' => $other->id, 'company_id' => $company->id, 'status' => 2]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Pré-análise')
            ->assertSee('4000098050')
            ->set('selected', [$targetProduction->id + 1000])
            ->call('filterUser', $target->id)
            ->assertSet('filters', ['user' => [(string) $target->id]])
            ->assertSet('selected', [])
            ->assertSet('selectAll', false)
            ->assertDontSee('4000098051');

        $this->assertSame([$targetProduction->id], $test->instance()->getListsProperty()->pluck('id')->all());

        $test->call('clearAllCriteria')->assertSet('filters', [])->assertSee('4000098051');
    }

    public function test_control_status_cards_filter_by_status_and_partner_control_renders(): void
    {
        $this->production($this->note('4000098060'), ['status' => 1]);
        $this->production($this->note('4000098061'), ['status' => 2, 'user_id' => $this->user->id]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 2)
            ->assertSet('filters', ['status' => ['2']])
            ->assertSee('4000098061')
            ->assertDontSee('4000098060')
            ->call('toggleStatus', 2)
            ->assertSet('filters', []);

        Livewire::test(PartnerStack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000098060');
    }

    public function test_control_export_query_follows_the_same_filters_as_the_list(): void
    {
        $this->production($this->note('4000098070'), ['status' => 1]);
        $this->production($this->note('4000098071'), ['status' => 2, 'user_id' => $this->user->id]);

        $component = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 2)
            ->instance();

        $this->assertCount(1, $component->getExportsProperty()->get());
        $this->assertSame([1, 2], collect($component->statusCounts)->keys()->map(fn ($k) => (int) $k)->sort()->values()->all());
    }

    public function test_control_reattribute_and_remove_confirm_only_with_the_pending_key(): void
    {
        $production = $this->production($this->note('4000098080'), ['status' => 2, 'user_id' => $this->user->id]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('askRemove', $production->id)
            ->assertSet('pendingProductionId', $production->id)
            ->call('confirmRemove', 'chave-errada');

        $this->assertNotNull(Production::find($production->id), 'chave errada não remove');

        $test->call('confirmRemove', hash('sha512', (string) $production->id))->assertSet('pendingProductionId', null);

        $this->assertNull(Production::find($production->id));
    }

    public function test_control_mass_assign_opens_the_modal_with_the_selected_notes(): void
    {
        $production = $this->production($this->note('4000098090'), ['status' => 1]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->set('selected', [$production->id])
            ->call('go_att_mass')
            ->assertSee('Atribuir Pré-análise')
            ->assertSee('4000098090');
    }

    public function test_services_list_renders_and_filters(): void
    {
        $this->note('4000098100', ['rubrica' => 'Mercado']);
        $this->note('4000098101', ['rubrica' => 'Outra']);

        $test = Livewire::test(ServiceMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Serviços · Pré-análise')
            ->assertSee('Assumir');

        $test->set('search', '4000098101')->assertDontSee('4000098100');
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
