<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Survey\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Levantamento\Main as ServiceMain;
use App\Models\{Note, Production, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Levantamento (Despacho, Parceira, Controle e Serviços) no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
class OpsSurveyListsTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();

        $this->user    = User::factory()->create(['contract' => false]);
        $this->service = Service::create(['service' => 'Levantamento', 'folder' => 'levantamento']);
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
            'material'   => 'LEVANTAMENTO NOVO',
        ]);
    }

    private function production(Note $note, array $extra = []): Production
    {
        return Production::create($extra + [
            'company_id'  => \App\Models\Company::query()->firstOrCreate(['name' => 'Empresa de teste'], ['email' => 'teste@example.com'])->id,
            'note_id'     => $note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $this->user->id,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->nstats,
            'dispatch_at' => now()->subDays(3),
            'att_at'      => now()->subDays(2),
            'status'      => 2,
        ]);
    }

    public function test_dispatch_list_renders_in_the_pattern_and_shows_the_note(): void
    {
        $this->note('4000098001');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Despacho · Levantamento')
            ->assertSee('4000098001')
            ->assertSee('Atribuir')
            ->assertSee('ops-list');
    }

    public function test_partner_list_renders(): void
    {
        $this->note('4000098002');

        Livewire::test(PartnerMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000098002')
            ->assertSee('Atribuir');
    }

    public function test_dispatch_search_type_shortcut_and_clear(): void
    {
        $this->note('4000098011');
        $this->note('4000098012', ['type_note' => 1]);

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('search', '4000098011')
            ->assertSee('4000098011')
            ->assertDontSee('4000098012')
            ->call('clearAllCriteria')
            ->assertSet('search', '')
            ->assertSee('4000098012')
            ->set('note_type', '1')
            ->assertSee('4000098012')
            ->assertDontSee('4000098011')
            ->call('filterStatus')
            ->assertSet('not_assigned', true)
            ->call('clearAllCriteria')
            ->assertSet('note_type', '')
            ->assertSet('not_assigned', false);
    }

    public function test_dispatch_filters_apply_and_clear(): void
    {
        $this->note('4000098021', ['rubrica' => 'AAA']);
        $this->note('4000098022', ['rubrica' => 'BBB']);

        $component = Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->call('applyListFilters', ['rubrica' => ['AAA', 'inexistente']]);

        // Só valores que existem nas opções chegam à consulta.
        $component->assertSet('filters', ['rubrica' => ['AAA']])
            ->assertSee('4000098021')
            ->assertDontSee('4000098022');

        $component->call('applyListFilters', ['inexistente' => ['x']])->assertSet('filters', []);
        $component->call('applyListFilters', ['rubrica' => ['BBB']])->call('clearAllCriteria')->assertSet('filters', []);
    }

    public function test_bulk_search_filters_by_the_given_notes(): void
    {
        $this->note('4000098031');
        $this->note('4000098032');

        Livewire::test(Main::class, ['service' => $this->service->uuid])
            ->set('advanceSearch', '4000098032')
            ->call('buscarMulti')
            ->assertSee('4000098032')
            ->assertDontSee('4000098031');
    }

    public function test_control_renders_the_production_and_status_cards(): void
    {
        $note = $this->note('4000098041');
        $this->production($note, ['priority' => true]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Controle · Levantamento')
            ->assertSee('4000098041')
            ->assertSee('Prioridade')
            ->assertSee('ops-control');

        Livewire::test(PartnerStack::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('4000098041');
    }

    public function test_control_status_card_user_filter_and_clear(): void
    {
        $this->production($this->note('4000098051'), ['status' => 2]);
        $this->production($this->note('4000098052'), ['status' => 4]);

        Livewire::test(Stack::class, ['service' => $this->service->uuid])
            ->call('toggleStatus', 4)
            ->assertSet('filters', ['status' => ['4']])
            ->assertSee('4000098052')
            ->assertDontSee('4000098051')
            ->call('clearAllCriteria')
            ->assertSet('filters', [])
            ->assertSee('4000098051')
            ->call('filterUser', $this->user->id)
            ->assertSet('filters', ['user' => [(string) $this->user->id]])
            ->assertSee('4000098051')
            ->set('search', '4000098052')
            ->assertSee('4000098052')
            ->assertDontSee('4000098051');
    }

    public function test_service_list_shows_only_my_production(): void
    {
        $other = User::factory()->create(['contract' => false]);
        $this->production($this->note('4000098061'));
        $this->production($this->note('4000098062'), ['user_id' => $other->id]);

        Livewire::test(ServiceMain::class, ['service' => $this->service->uuid])
            ->assertStatus(200)
            ->assertSee('Minha produção em levantamento')
            ->assertSee('4000098061')
            ->assertDontSee('4000098062')
            ->assertSee('Iniciar')
            ->set('search', 'inexistente')
            ->assertSee('Nenhum resultado para a busca')
            ->call('clearAllCriteria')
            ->assertSee('4000098061');
    }
}
