<?php

namespace Tests\Feature\Engineers;

use App\Http\Livewire\Engineers\WaitingFiveNotes;
use App\Models\{Company, FiveNote, Note, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class D5ConsultScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $this->actingAs(User::factory()->create(['company_id' => $company->id]));

        foreach ([['1', now()->subDays(9), false], ['2', now()->subDays(1), false], ['3', now()->subDays(12), true]] as [$d5, $dispatch, $archived]) {
            $note = Note::create(['note' => '40000000' . $d5, 'dt_status' => now()->toDateTimeString(), 'nstats' => 'NEW']);

            FiveNote::create([
                'note_id'         => $note->id, 'note_d5' => $d5, 'company_id' => $company->id, 'reason' => '1_REPARO',
                'visible_partner' => true, 'dispatch_at' => $dispatch, 'is_archived' => $archived,
            ]);
        }
    }

    public function test_renders_the_executive_panel_and_the_list(): void
    {
        Livewire::test(WaitingFiveNotes::class)
            ->assertOk()
            ->assertSee('Consulta geral D5')
            ->assertSee('D5 em andamento')
            ->assertSee('Atrasadas')
            ->assertSee('Lista de D5')
            ->assertSee('Compel')
            ->assertSee('data-my-panel-chart="stage"', false)
            ->assertSee('data-my-panel-chart="flow"', false)
            ->assertSee('data-my-panel-chart="age"', false);
    }

    public function test_stage_chip_filters_only_the_list_not_the_cards(): void
    {
        $component = Livewire::test(WaitingFiveNotes::class)->call('setStage', 'finalizado');

        $component->assertSet('statusFilter', 'finalizado');
        $this->assertSame(1, $component->get('lists')->total());
        $this->assertSame(2, $component->get('snapshot')['summary']['open']);
    }

    public function test_invalid_stage_falls_back_to_all(): void
    {
        $component = Livewire::test(WaitingFiveNotes::class)->call('setStage', 'qualquer-coisa');

        $component->assertSet('statusFilter', '');
        $this->assertSame(3, $component->get('lists')->total());
    }

    public function test_search_narrows_cards_and_dispatches_chart_update(): void
    {
        $component = Livewire::test(WaitingFiveNotes::class)->set('search', '1_REPARO');

        $component->assertDispatchedBrowserEvent('my-panel:charts');
        $this->assertSame(3, $component->get('snapshot')['summary']['total']);

        $component->set('search', 'nao-existe-esse-texto');
        $this->assertSame(0, $component->get('snapshot')['summary']['total']);
        $component->assertSee('Nenhuma D5 nos filtros atuais');
    }

    public function test_awaiting_resolution_screen_hides_finalized_everywhere(): void
    {
        $component = Livewire::test(WaitingFiveNotes::class, ['hideFinalized' => true]);

        $this->assertSame(2, $component->get('lists')->total());
        $this->assertSame(0, $component->get('snapshot')['summary']['finalized']);
        $component->assertDontSee('Finalizado ·');

        // Mesmo forçando a etapa (URL ou chamada direta), a lista não mostra finalizadas.
        $component->call('setStage', 'finalizado')->assertSet('statusFilter', '');
        $this->assertSame(2, $component->get('lists')->total());

        $labels = \App\Services\Engineers\D5ConsultCharts::all($component->get('snapshot'), false)['stage']['data']['labels'];
        $this->assertSame(['Fornecedor', 'Fiscalização', 'Medição'], $labels);
    }
}
