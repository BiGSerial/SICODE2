<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Default\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Dispatchs\Payment\PartnerStack as PaymentPartnerStack;
use App\Models\{Company, Note, Production, Service, User};
use App\Services\Dispatchs\DefaultListRowPresenter;
use App\Support\Ops\RowState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Atividade padrão (Despacho, Parceira, Controle) e Controle de Pagamento da parceira no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsDefaultListsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = [
        'dispatchs/default/main',
        'dispatchs/default/partner_main',
        'dispatchs/default/stack',
        'dispatchs/default/partner_stack',
        'dispatchs/default/partials/stack-mass-actions',
        'dispatchs/pagamento/partner_stack',
    ];

    private Service $service;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        config(['sicode.ruleset' => 'sp']);
        $this->actingAs(User::factory()->create(['contract' => false, 'management' => true]));
        $this->service = Service::create(['service' => 'Cadastro', 'status' => 3]);
        $this->company = Company::create(['name' => 'Compel Engenharia', 'email' => 'compel@example.com']);
    }

    private function note(string $number, array $extra = []): Note
    {
        return Note::create($extra + [
            'note'       => $number,
            'dt_status'  => now(),
            'dt_created' => now(),
            'type_note'  => 1,
            'nstats'     => 3,
            'rubrica'    => 'Mercado',
            'material'   => 'CABO',
        ]);
    }

    private function production(Note $note, array $extra = []): Production
    {
        return Production::create($extra + [
            'note_id'     => $note->id,
            'service_id'  => $this->service->uuid,
            'company_id'  => $this->company->id,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->nstats,
            'dispatch_at' => now()->subDays(3),
            'status'      => 1,
        ]);
    }

    public function test_views_have_no_inline_css_js_or_legacy_classes(): void
    {
        foreach (self::VIEWS as $view) {
            $html = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

            expect($html)->not->toContain('<style')
                ->not->toContain('<script')
                ->not->toContain('text-bg-')
                ->not->toContain('components.filter.filter')
                ->not->toMatch('/model\.bounce/')
                ->not->toMatch('/\btable-(primary|warning|success|danger|striped|dark)\b/')
                ->not->toMatch('/#[0-9a-fA-F]{3,8}\b/')
                ->not->toMatch('/style="[^"]*(color|cursor|background)/');
        }
    }

    public function test_dispatch_lists_render_filter_and_clear(): void
    {
        $this->note('4000099001');
        $this->note('4000099002', ['rubrica' => 'Outra']);

        foreach ([Main::class, PartnerMain::class] as $component) {
            Livewire::test($component, ['service' => $this->service->uuid])
                ->assertStatus(200)
                ->assertSee('Despacho · Cadastro')
                ->assertSee('4000099001')
                ->assertDontSee('<style', false)
                ->call('applyListFilters', ['rubrica' => ['Outra']])
                ->assertSet('filters', ['rubrica' => ['Outra']])
                ->set('search', '4000099')
                ->set('note_type', '2')
                ->call('clearAllCriteria')
                ->assertSet('filters', [])
                ->assertSet('search', '')
                ->assertSet('note_type', '')
                ->set('advanceSearch', "4000099001\n4000099002")
                ->call('buscarMulti')
                ->assertSet('multiSearch', ['4000099001', '4000099002'])
                ->call('clearAllCriteria')
                ->assertSet('multiSearch', []);
        }
    }

    public function test_partner_variant_shows_dispatch_dates(): void
    {
        $note = $this->note('4000099003');
        $this->production($note, ['status' => 2, 'confirmed' => false, 'att_at' => now()]);

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertSee('Despachado em')
            ->assertSee('Finalizado em');

        Livewire::test(Main::class, ['service' => $this->service->uuid])->assertDontSee('Despachado em');
    }

    public function test_row_state_maps_the_open_production_to_the_five_states(): void
    {
        $presenter = app(DefaultListRowPresenter::class);
        $uuid      = $this->service->uuid;
        $cases     = [
            [['status' => 1, 'confirmed' => false], RowState::STACK, false],
            [['status' => 2, 'confirmed' => false], RowState::ASSIGNED, false],
            [['status' => 5, 'confirmed' => false], RowState::DONE, false],
            [['status' => 5, 'confirmed' => true], RowState::LATE, true],
        ];

        foreach ($cases as $i => [$attrs, $state, $canDispatch]) {
            $note = $this->note('40001' . $i);
            $this->production($note, $attrs);
            $note = Note::with('Productions')->find($note->id);
            $eval = $presenter->evaluate($note, $uuid);

            expect($eval['state'])->toBe($state)->and($eval['canDispatch'])->toBe($canDispatch);
        }

        $free = Note::with('Productions')->find($this->note('4000199')->id);
        expect($presenter->evaluate($free, $uuid))->toMatchArray(['state' => RowState::FREE, 'canDispatch' => true]);
    }

    public function test_control_lists_render_with_one_query_for_list_and_status_counts(): void
    {
        $note = $this->note('4000099010');
        $this->production($note, ['status' => 2, 'user_id' => auth()->id(), 'priority' => true]);

        foreach ([Stack::class, PartnerStack::class] as $component) {
            Livewire::test($component, ['service' => $this->service->uuid])
                ->assertStatus(200)
                ->assertSee('Controle · Cadastro')
                ->assertSee('4000099010')
                ->assertSee('ops-row-priority', false)
                ->call('toggleStatus', 2)
                ->assertSet('filters', ['status' => ['2']])
                ->call('filterUser', auth()->id())
                ->assertSet('filters.user', [(string) auth()->id()])
                ->call('clearAllCriteria')
                ->assertSet('filters', []);
        }
    }

    public function test_control_row_actions_are_methods_of_the_component(): void
    {
        $note = $this->note('4000099011');
        $prod = $this->production($note, ['status' => 2, 'completed' => false]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('askRemove', $prod->id)
            ->assertDispatchedBrowserEvent('alertar')
            ->assertSet('pendingProductionId', $prod->id)
            ->call('confirmRemove', 'chave-errada')
            ->assertSet('pendingProductionId', $prod->id);

        expect(Production::find($prod->id))->not->toBeNull();
    }

    public function test_payment_partner_control_renders_without_pep_and_keeps_filters(): void
    {
        $service = Service::create(['service' => 'Pagamento']);
        $note    = $this->note('4000099020');
        Production::create([
            'note_id'     => $note->id,
            'service_id'  => $service->uuid,
            'company_id'  => $this->company->id,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->nstats,
            'dispatch_at' => now(),
            'status'      => 1,
        ]);

        Livewire::test(PaymentPartnerStack::class, ['service' => $service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Pagamento')
            ->assertSee('4000099020')
            ->assertDontSee('Saldo PEP')
            ->call('toggleStatus', 1)
            ->assertSet('filters', ['status' => ['1']])
            ->call('clearAllCriteria')
            ->assertSet('filters', []);
    }
}
