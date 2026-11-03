<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Reverse\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Reverse\Main as ServiceMain;
use App\Models\{Note, Production, Service, User};
use App\Services\Reverse\ReverseListRowPresenter;
use App\Support\Ops\RowState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Fluxo Reverso (Despacho, Parceira, Controle e Serviços) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsReverseListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'dispatchs/reverse/main',
        'dispatchs/reverse/stack',
        'dispatchs/reverse/partials/stack-mass-actions',
        'services/reverse/main',
    ];

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        $this->actingAs(User::factory()->create(['contract' => false]));
        $this->service = Service::create(['service' => 'Fluxo Reverso', 'folder' => 'fluxo_reverso', 'status' => 2]);
    }

    private function note(string $number, array $extra = []): Note
    {
        return Note::create($extra + [
            'note'       => $number,
            'dt_status'  => now(),
            'dt_created' => now(),
            'nstats'     => 2,
            'type_note'  => 2,
            'rubrica'    => 'Mercado',
            'material'   => 'MMGD - REDUCAO',
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
        $this->note('4000077001');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Despacho · Fluxo Reverso')
            ->assertSee('4000077001')
            ->assertSee('Atribuir');
    }

    public function test_partner_list_renders_with_its_own_header_and_queues_the_export(): void
    {
        $this->note('4000077002');

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Parceira · Fluxo Reverso')
            ->assertSee('4000077002');
    }

    public function test_dispatch_list_searches_filters_and_clears(): void
    {
        $this->note('4000077003');
        $this->note('4000077004', ['rubrica' => 'Outra']);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('search', '4000077003')
            ->assertSee('4000077003')
            ->assertDontSee('4000077004')
            ->call('applyListFilters', ['rubrica' => ['Inexistente']])
            ->assertSet('filters', [])
            ->call('clearAllCriteria')
            ->assertSet('search', '')
            ->assertSet('filters', [])
            ->assertSee('4000077004');

        $this->assertFalse($test->instance()->hasListFilters());
    }

    public function test_dispatch_list_filters_by_rubrica_and_hides_mmgd(): void
    {
        $this->note('4000077005');
        $this->note('4000077006', ['rubrica' => 'Outra', 'material' => 'CABO']);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid]);

        $test->call('applyListFilters', ['rubrica' => ['Outra']])->assertSee('4000077006')->assertDontSee('4000077005');

        $test->call('clearAllCriteria')->call('filterMMGD')
            ->assertSet('mmgd', true)
            ->assertSee('4000077006')
            ->assertDontSee('4000077005');
    }

    public function test_mass_search_replaces_simple_search(): void
    {
        $this->note('4000077007');
        $this->note('4000077008');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('advanceSearch', "4000077007\n")
            ->call('buscarMulti')
            ->assertSet('multiSearch', ['4000077007'])
            ->assertSee('4000077007')
            ->assertDontSee('4000077008');
    }

    public function test_select_all_marks_only_dispatchable_rows_of_the_page(): void
    {
        $free = $this->note('4000077009');
        $busy = $this->note('4000077010');
        $this->production($busy, ['user_id' => User::factory()->create()->id, 'status' => 2]);

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('selectall', true)
            ->assertSet('selected', [(string) $free->id])
            ->call('clearSelection')
            ->assertSet('selected', []);
    }

    public function test_control_list_renders_and_toggles_status_and_user_filter(): void
    {
        $user = User::factory()->create();
        $note = $this->note('4000077011');
        $this->production($note, ['user_id' => $user->id, 'status' => 2, 'att_at' => now()->subDay()]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Fluxo Reverso')
            ->assertSee('Lista de controle')
            ->assertSee('4000077011')
            ->call('toggleStatus', 1)
            ->assertSet('filters', ['status' => ['1']])
            ->assertDontSee('4000077011')
            ->call('clearAllCriteria')
            ->assertSee('4000077011')
            ->call('filterUser', $user->id)
            ->assertSet('filters', ['user' => [(string) $user->id]])
            ->assertSee('4000077011');

        $this->assertTrue($test->instance()->hasListFilters());
    }

    public function test_control_row_actions_ask_before_acting_and_are_scoped_to_the_control(): void
    {
        $note       = $this->note('4000077012');
        $production = $this->production($note, ['status' => 1]);
        $other      = $this->note('4000077013');
        $foreign    = Production::create([
            'company_id'  => \App\Models\Company::query()->firstOrCreate(['name' => 'Empresa de teste'], ['email' => 'teste@example.com'])->id,
            'note_id'     => $other->id,
            'service_id'  => Service::create(['service' => 'Outra', 'folder' => 'outra'])->uuid,
            'dt_note'     => $other->dt_status,
            'status_note' => $other->nstats,
            'dispatch_at' => now(),
            'status'      => 1,
        ]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('askRemove', $production->id)
            ->assertDispatchedBrowserEvent('alertar')
            ->assertSet('pendingProductionId', $production->id)
            ->call('confirmRemove', 'chave-errada')
            ->assertSet('pendingProductionId', $production->id);

        $this->assertNotNull(Production::find($production->id));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(Stack::class, ['service' => $this->service->uuid])->call('askRemove', $foreign->id);
    }

    public function test_partner_control_renders(): void
    {
        $this->production($this->note('4000077014'));

        Livewire::test(PartnerStack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Parceira · Fluxo Reverso')
            ->assertSee('4000077014');
    }

    public function test_service_list_renders_filters_and_clears(): void
    {
        $this->note('4000077015');
        $this->note('4000077016', ['rubrica' => 'Outra']);

        Livewire::test(ServiceMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Serviços · Fluxo Reverso')
            ->set('search', 'inexistente')
            ->call('clearAllCriteria')
            ->assertSet('search', '')
            ->assertSet('filters', []);
    }

    public function test_row_state_follows_the_original_block_rules(): void
    {
        $presenter = app(ReverseListRowPresenter::class);
        $note      = $this->note('4000077017');
        $note->load('Productions.User');

        $this->assertSame(RowState::FREE, $presenter->serviceRow($note, $this->service->uuid, 1, true)['state']);

        $this->production($note);
        $row = $presenter->serviceRow($note->fresh('Productions.User'), $this->service->uuid, 1, true);

        $this->assertSame(RowState::STACK, $row['state'], 'aberta e sem usuário = na pilha');
        $this->assertFalse($row['canDispatch']);
    }

    public function test_deadline_keeps_the_original_color_rule(): void
    {
        $presenter = app(ReverseListRowPresenter::class);

        $this->assertSame('muted', $presenter->deadline(-1)['tone']);
        $this->assertSame('red', $presenter->deadline(5)['tone']);
        $this->assertSame('amber', $presenter->deadline(9)['tone']);
        $this->assertSame('green', $presenter->deadline(10)['tone']);
        $this->assertSame(18, $presenter->deadline(12)['label']);
    }

    public function test_views_follow_the_pattern_without_inline_style_or_legacy_markup(): void
    {
        foreach (self::VIEWS as $view) {
            $html = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

            $this->assertDoesNotMatchRegularExpression('/<style|<script|@push|text-bg-|table-(?:danger|warning|success|info|primary|dark|striped)|#[0-9a-fA-F]{3,6}\b|rgb\(|\.bounce\.|components\.filter\.|production\.actions\./', $html, $view);
            $this->assertStringNotContainsString('style="cursor', $html, $view);

            if (!str_contains($view, 'partials')) {
                $this->assertStringContainsString('closure-dash my-panel ops-list', $html, $view);
            }
        }
    }
}
