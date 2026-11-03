<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Desenho\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Desenho\Main as ServiceMain;
use App\Models\{Company, Note, Production, Service, User};
use App\Services\Dispatchs\DesignListRowPresenter;
use App\Support\Ops\RowState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Desenho (Despacho, Parceira, Controle e Serviços) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsDesenhoListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'dispatchs/desenho/main',
        'dispatchs/desenho/partner_main',
        'dispatchs/desenho/stack',
        'dispatchs/desenho/partner_stack',
        'dispatchs/desenho/partials/stack-mass-actions',
        'dispatchs/desenho/partials/note-badges',
        'services/desenho/main',
        'services/desenho/partials/note-extras',
    ];

    private Service $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        $this->user    = User::factory()->create(['contract' => false]);
        $this->service = Service::create(['service' => 'Desenho', 'folder' => 'desenho']);
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
            'days_left'  => 12,
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
        $this->note('4000088001');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Despacho · Desenho')
            ->assertSee('4000088001')
            ->assertSee('Atribuir')
            ->assertSee('ops-list');
    }

    public function test_partner_list_reuses_the_dispatch_screen(): void
    {
        $this->note('4000088002');

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000088002')
            ->assertSee('ops-list');
    }

    public function test_dispatch_filters_apply_clear_and_search(): void
    {
        $this->note('4000088010', ['rubrica' => 'Mercado']);
        $this->note('4000088011', ['rubrica' => 'Outra']);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['Mercado']])
            ->assertSet('filters', ['rubrica' => ['Mercado']])
            ->assertSee('4000088010')
            ->assertDontSee('4000088011');

        $test->call('clearAllCriteria')
            ->assertSet('filters', [])
            ->assertSee('4000088011');

        $test->set('search', '4000088011')
            ->assertSee('4000088011')
            ->assertDontSee('4000088010');
    }

    public function test_dispatch_list_ignores_unknown_filter_values_and_keys(): void
    {
        $this->note('4000088020');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['inexistente'], 'hack' => ['1']])
            ->assertSet('filters', []);
    }

    public function test_dispatch_list_select_all_marks_only_dispatchable_rows_and_clears(): void
    {
        $free = $this->note('4000088030');
        $this->production($free); // Production aberta sem usuário: bloqueia o despacho comum.

        $other = $this->note('4000088031');

        $test                 = Livewire::test(Main::class, ['service' => $this->service->uuid]);
        $component            = $test->instance();
        $component->selectall = true;
        $component->setSelectAll();
        $this->assertSame([(string) $other->id], $component->selected);
        $test->call('clearSelection')->assertSet('selected', []);
    }

    public function test_row_state_maps_every_design_block_level_to_the_five_states(): void
    {
        $map = [
            0 => RowState::FREE,
            1 => RowState::ASSIGNED,
            2 => RowState::STACK,
            3 => RowState::DONE,
            4 => RowState::LATE,
        ];

        foreach ($map as $block => $state) {
            $this->assertSame($state, DesignListRowPresenter::rowState(['block' => $block], false));
        }

        $this->assertSame(RowState::ASSIGNED, DesignListRowPresenter::rowState(['block' => 5, 'reason' => 'prod_inconst_open_generic'], false));
        $this->assertSame(RowState::DONE, DesignListRowPresenter::rowState(['block' => 5, 'reason' => 'production_completed_waiting_confirmation'], false));
        $this->assertSame(RowState::FREE, DesignListRowPresenter::rowState(['block' => 2], true), 'pilha da empresa aparece livre');
    }

    public function test_design_deadline_rules_keep_their_original_limits(): void
    {
        // DStatus: verde < 4; âmbar de 4 até o limite (5 no despacho, 6 no controle); vermelho acima.
        $this->assertSame('green', DesignListRowPresenter::daysInStatusTone(3, 5));
        $this->assertSame('amber', DesignListRowPresenter::daysInStatusTone(4, 5));
        $this->assertSame('amber', DesignListRowPresenter::daysInStatusTone(5, 5));
        $this->assertSame('red', DesignListRowPresenter::daysInStatusTone(6, 5));
        $this->assertSame('amber', DesignListRowPresenter::daysInStatusTone(6, 6));
        $this->assertSame('red', DesignListRowPresenter::daysInStatusTone(7, 6));

        // Prazo real: vencido cinza; 0–5 vermelho; 6–9 âmbar; 10+ verde.
        $this->assertSame('muted', DesignListRowPresenter::deadlineTone(-1));
        $this->assertSame('red', DesignListRowPresenter::deadlineTone(0));
        $this->assertSame('red', DesignListRowPresenter::deadlineTone(5));
        $this->assertSame('amber', DesignListRowPresenter::deadlineTone(6));
        $this->assertSame('amber', DesignListRowPresenter::deadlineTone(9));
        $this->assertSame('green', DesignListRowPresenter::deadlineTone(10));
    }

    public function test_control_list_renders_filters_by_user_and_has_no_per_row_component(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $target  = User::factory()->create(['contract' => false]);
        $other   = User::factory()->create(['contract' => false]);

        $targetProduction = $this->production($this->note('4000088040'), ['user_id' => $target->id, 'company_id' => $company->id, 'status' => 2]);
        $this->production($this->note('4000088041'), ['user_id' => $other->id, 'company_id' => $company->id, 'status' => 2]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Desenho')
            ->assertSee('4000088040')
            ->set('selected', [$targetProduction->id + 1000])
            ->call('filterUser', $target->id)
            ->assertSet('filters', ['user' => [(string) $target->id]])
            ->assertSet('selected', [])
            ->assertDontSee('4000088041');

        $this->assertSame([$targetProduction->id], $test->instance()->getListsProperty()->pluck('id')->all());

        $test->call('clearAllCriteria')->assertSet('filters', [])->assertSee('4000088041');
    }

    public function test_control_status_cards_filter_by_status_and_partner_control_renders(): void
    {
        $this->production($this->note('4000088050'), ['status' => 1]);
        $this->production($this->note('4000088051'), ['status' => 2, 'user_id' => $this->user->id]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 2)
            ->assertSet('filters', ['status' => ['2']])
            ->assertSee('4000088051')
            ->assertDontSee('4000088050')
            ->call('toggleStatus', 2)
            ->assertSet('filters', []);

        Livewire::test(PartnerStack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000088050');
    }

    public function test_control_export_query_follows_the_same_filters_as_the_list(): void
    {
        $this->production($this->note('4000088060'), ['status' => 1]);
        $this->production($this->note('4000088061'), ['status' => 2, 'user_id' => $this->user->id]);

        $component = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 2)
            ->instance();

        $this->assertCount(1, $component->getExportsProperty()->get());
        $this->assertSame(['2' => 1, '1' => 1], collect($component->statusCounts)->mapWithKeys(fn ($v, $k) => [(string) $k => $v])->sortKeysDesc()->all());
    }

    public function test_services_list_renders_my_activities(): void
    {
        $this->production($this->note('4000088070'), ['user_id' => $this->user->id, 'status' => 2]);
        $this->production($this->note('4000088071'), ['user_id' => User::factory()->create(['contract' => false])->id, 'status' => 2]);

        $test = Livewire::test(ServiceMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Serviços · Desenho')
            ->assertSee('4000088070')
            ->assertDontSee('4000088071')
            ->assertSee('Iniciar');

        $test->set('search', 'inexistente')->assertDontSee('4000088070');
        $test->call('clearAllCriteria')->assertSet('search', '')->assertSee('4000088070');
        $test->call('applyListFilters', ['rubrica' => ['Mercado']])->assertSet('filters', ['rubrica' => ['Mercado']]);
    }

    public function test_views_have_no_inline_css_literal_colors_or_legacy_classes(): void
    {
        foreach (self::VIEWS as $view) {
            $content = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

            $this->assertStringNotContainsString('<style', $content, "{$view}: <style>");
            $this->assertStringNotContainsString('text-bg-', $content, "{$view}: text-bg-*");
            $this->assertStringNotContainsString('components.filter.filter', $content, "{$view}: filtro legado");
            $this->assertStringNotContainsString('.bounce.', $content, "{$view}: debounce inexistente");
            $this->assertDoesNotMatchRegularExpression('/\btable-(primary|warning|success|danger|secondary|dark|striped)\b/', $content, "{$view}: table-*");
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{6}\b|rgba?\(/', $content, "{$view}: cor literal");
            $this->assertDoesNotMatchRegularExpression('/style="[^"]*(color|cursor|background)/', $content, "{$view}: style de cor/cursor");

            // O fluxo de modais de Serviços → Desenho (script em `@push`) continua na view até haver um JS compartilhado.
            if ($view !== 'services/desenho/main') {
                $this->assertStringNotContainsString('<script', $content, "{$view}: <script>");
            }
        }
    }
}
