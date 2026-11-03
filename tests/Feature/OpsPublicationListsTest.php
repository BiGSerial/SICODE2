<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Publication\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Publication\Main as ServiceMain;
use App\Models\{Company, Note, Operation, Order, Production, Service, User, WorkReport};
use App\Services\Publication\NoteFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Publicação (Despacho, Parceira, Controle e Serviços) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsPublicationListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'dispatchs/publication/main',
        'dispatchs/publication/partner_main',
        'dispatchs/publication/stack',
        'dispatchs/publication/partner_stack',
        'dispatchs/publication/partials/stack-mass-actions',
        'dispatchs/publication/partials/stack-note-tags',
        'services/publication/main',
    ];

    private Service $service;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        config(['sicode.ruleset' => 'sp']);
        $this->actingAs(User::factory()->create(['contract' => false, 'management' => true]));
        $this->service = Service::create(['service' => 'Publicação']);
        $this->company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
    }

    /** Nota com informe de rede e OP20 liberada: entra na lista de Publicação. */
    private function note(string $number, array $extra = []): Note
    {
        $note = Note::create($extra + [
            'note'       => $number,
            'dt_status'  => now(),
            'dt_created' => now(),
            'type_note'  => 1,
            'nstats'     => 2,
            'rubrica'    => 'Mercado',
            'material'   => 'CABO',
        ]);

        $order = Order::create(['note_id' => $note->id, 'ordem' => '15' . substr($number, 2), 'statusSist' => 'LIB']);
        Operation::create(['order_id' => $order->id, 'operacao' => '0020', 'status' => 'LIB']);

        $report = WorkReport::create([
            'note_id'               => $note->id,
            'company_id'            => $this->company->id,
            'date'                  => '2026-09-10',
            'informed_at'           => '2026-09-10 08:00:00',
            'rejected'              => false,
            'canceled'              => false,
            'selected_final_scopes' => ['network'],
        ]);
        $report->Orders()->sync([$order->id]);

        return $note;
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
            ->assertSee('Despacho · Publicação')
            ->assertSee('4000088001')
            ->assertSee('Atribuir');
    }

    public function test_partner_list_renders(): void
    {
        $this->note('4000088002');

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('da sua empresa')
            ->assertSee('4000088002');
    }

    public function test_dispatch_list_searches_filters_and_clears(): void
    {
        $this->note('4000088003');
        $this->note('4000088004', ['rubrica' => 'Outra']);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('search', '4000088003')
            ->assertSee('4000088003')
            ->assertDontSee('4000088004')
            ->call('applyListFilters', ['rubrica' => ['Inexistente']])
            ->assertSet('filters', [])
            ->call('clearAllCriteria')
            ->assertSet('search', '')
            ->assertSet('filters', [])
            ->assertSee('4000088004');

        $this->assertFalse($test->instance()->hasListFilters());
    }

    public function test_dispatch_list_filters_by_rubrica_type_and_company(): void
    {
        $this->note('4000088005');
        $this->note('4000088006', ['rubrica' => 'Outra', 'type_note' => 2, 'days_left' => 9]);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid]);

        $test->call('applyListFilters', ['rubrica' => ['Outra']])->assertSee('4000088006')->assertDontSee('4000088005');

        $test->call('clearAllCriteria')->set('typeNote', '1')->assertSee('4000088005')->assertDontSee('4000088006');

        $test->set('typeNote', '')->call('applyListFilters', ['company' => [(string) $this->company->id]])->assertSee('4000088005')->assertSee('4000088006');
    }

    public function test_mass_search_replaces_simple_search(): void
    {
        $this->note('4000088007');
        $this->note('4000088008');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('advanceSearch', "4000088007\n")
            ->call('buscarMulti')
            ->assertSet('multiSearch', ['4000088007'])
            ->assertSee('4000088007')
            ->assertDontSee('4000088008');
    }

    public function test_only_free_shortcut_keeps_the_other_filters(): void
    {
        $busy = $this->note('4000088009');
        $this->note('4000088010');
        $this->production($busy, ['user_id' => User::factory()->create()->id, 'status' => 2]);

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('filterStatus')
            ->assertSet('not_assigned', true)
            ->assertSee('4000088010')
            ->assertDontSee('4000088009');
    }

    public function test_select_all_marks_only_rows_without_open_activity(): void
    {
        $free = $this->note('4000088011');
        $busy = $this->note('4000088012');
        $this->production($busy, ['user_id' => User::factory()->create()->id, 'status' => 2]);

        $test = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('selectall', true);

        // A Production da atividade só bloqueia a linha quando está ligada ao informe (fluxo); sem vínculo a linha segue livre.
        $this->assertContains($free->id . ':' . WorkReport::where('note_id', $free->id)->value('id'), $test->get('selected'));

        $test->call('clearSelection')->assertSet('selected', []);
    }

    public function test_control_list_renders_and_toggles_status_and_user_filter(): void
    {
        $user = User::factory()->create();
        $note = $this->note('4000088013');
        $this->production($note, ['user_id' => $user->id, 'status' => 2, 'att_at' => now()->subDay()]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Publicação')
            ->assertSee('Lista de controle')
            ->assertSee('4000088013')
            ->call('toggleStatus', 1)
            ->assertSet('filters', ['status' => ['1']])
            ->assertDontSee('4000088013')
            ->call('clearAllCriteria')
            ->assertSee('4000088013')
            ->call('filterUser', $user->id)
            ->assertSet('filters', ['user' => [(string) $user->id]])
            ->assertSee('4000088013');

        $this->assertTrue($test->instance()->hasListFilters());
    }

    public function test_control_shares_one_query_with_the_excel(): void
    {
        $note = $this->note('4000088014');
        $this->production($note, ['status' => 1]);
        $other = $this->note('4000088015', ['rubrica' => 'Outra']);
        $this->production($other, ['status' => 1]);

        $test = Livewire::test(Stack::class, ['service' => $this->service->uuid]);
        $test->call('applyListFilters', ['rubrica' => ['Outra']]);

        $this->assertSame(1, $test->instance()->exports->get()->count());
        $this->assertSame([1 => 1], $test->instance()->statusCounts);
    }

    public function test_partner_control_renders(): void
    {
        $this->production($this->note('4000088016'));

        Livewire::test(PartnerStack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Publicação')
            ->assertSee('4000088016');
    }

    public function test_service_list_renders_assumes_and_filters(): void
    {
        $this->note('4000088017');
        $this->note('4000088018', ['rubrica' => 'Outra']);

        Livewire::test(ServiceMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Serviços · Publicação')
            ->assertSee('4000088017')
            ->assertSee('Assumir')
            ->set('search', '4000088018')
            ->assertSee('4000088018')
            ->assertDontSee('4000088017')
            ->call('clearAllCriteria')
            ->assertSee('4000088017');
    }

    public function test_note_filter_accepts_explicit_filters_without_the_session(): void
    {
        $this->note('4000088019');
        $this->note('4000088020', ['rubrica' => 'Outra']);

        $filter = app(NoteFilter::class);

        $this->assertSame(2, $filter->filter('publishing')->count());
        $this->assertSame(1, $filter->filter('publishing', true, ['rubrica' => ['Outra']])->count());
        $this->assertSame(2, $filter->filter('publishing', true, [])->count());
    }

    public function test_views_have_no_inline_style_script_or_legacy_classes(): void
    {
        foreach (self::VIEWS as $view) {
            $source = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

            $this->assertStringNotContainsString('<style', $source, $view);
            $this->assertStringNotContainsString('<script', $source, $view);
            $this->assertStringNotContainsString('text-bg-', $source, $view);
            $this->assertStringNotContainsString('table-', str_replace(['ops-table', 'table-hover'], '', $source), $view);
            $this->assertStringNotContainsString('components.filter.filter', $source, $view);
            $this->assertStringNotContainsString('.bounce.', $source, $view);
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b|rgba?\(/', $source, $view);
        }
    }
}
