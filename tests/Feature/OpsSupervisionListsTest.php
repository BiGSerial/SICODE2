<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Supervision\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Supervision\Main as ServiceMain;
use App\Models\{City, Company, Note, Production, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Listas de Fiscalização no padrão de listas operacionais: docs/PADRAO_LISTAS_OPERACIONAIS.md

class OpsSupervisionListsTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        return Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
    }

    public function test_every_migrated_component_renders_in_the_pattern(): void
    {
        $service = $this->service();

        foreach ([Main::class, PartnerMain::class] as $component) {
            Livewire::test($component, ['service' => $service->uuid])
                ->assertSee('ops-list', false)
                ->assertSee('Lista para fiscalizacao')
                ->assertSee('Sem notas em Fiscalizacao');
        }

        foreach ([Stack::class, PartnerStack::class] as $component) {
            Livewire::test($component, ['service' => $service->uuid])
                ->assertSee('ops-control', false)
                ->assertSee('Controle de fiscalizacao')
                ->assertSee('Nada em controle no momento');
        }

        Livewire::test(ServiceMain::class, ['service' => $service->uuid])
            ->assertSee('ops-list', false)
            ->assertSee('Acompanhamento de fiscalizacao');
    }

    public function test_service_accompany_tabs_are_server_driven_and_keep_the_transfers_tab_open(): void
    {
        $service = $this->service();

        $producaoPanel = '<h2 class="mp-panel-title">Acompanhamento</h2>';

        // A aba é estado do servidor: sobrevive a um novo render (o que o Bootstrap não garantia).
        Livewire::test(ServiceMain::class, ['service' => $service->uuid])
            ->assertSee($producaoPanel, false)
            ->call('setTab', 'transferencias')
            ->assertSet('tab', 'transferencias')
            ->assertDontSee($producaoPanel, false)
            ->assertSee('mp-preset is-active" wire:click="setTab(\'transferencias\')"', false)
            ->call('$refresh')
            ->assertSet('tab', 'transferencias')
            ->assertDontSee($producaoPanel, false)
            ->call('setTab', 'qualquer-coisa')
            ->assertSet('tab', 'producao')
            ->assertSee($producaoPanel, false);

        Livewire::withQueryParams(['aba' => 'transferencias'])
            ->test(ServiceMain::class, ['service' => $service->uuid])
            ->assertSet('tab', 'transferencias');

        // O próprio Translist renderiza no padrão (painel + estado vazio), sem <style> nem cores soltas.
        Livewire::test(\App\Http\Livewire\Components\Transprod\Translist::class, ['service' => $service])
            ->assertSee('mp-panel', false)
            ->assertSee('Nenhuma transferência pendente')
            ->assertDontSee('<style', false);
    }

    public function test_dispatch_list_filters_apply_clear_and_become_the_query_filter(): void
    {
        $service = $this->service();
        Note::create(['note' => '4000009001', 'rubrica' => 'RUB-A']);
        City::create(['rdMunicipio' => 'RD1', 'municipio' => 'Cidade 1', 'regiao' => 'Norte', 'baseConstrucao' => 'Base 1']);
        City::create(['rdMunicipio' => 'RD2', 'municipio' => 'Cidade 2', 'regiao' => 'Sul', 'baseConstrucao' => 'Base 2']);

        foreach ([Main::class, PartnerMain::class] as $component) {
            $test = Livewire::test($component, ['service' => $service->uuid])
                ->call('applyListFilters', ['rubrica' => ['RUB-A', 'INEXISTENTE'], 'region' => ['Norte'], 'hack' => ['1']])
                ->assertSet('filters', ['rubrica' => ['RUB-A'], 'region' => ['Norte']]);

            $filter = (function () {
                return $this->listQueryFilter();
            })->call($test->instance());

            $this->assertSame(['rubrica' => ['RUB-A'], 'city' => ['RD1']], $filter);

            $test->call('clearAllCriteria')->assertSet('filters', [])->assertSet('search', '');
        }
    }

    public function test_control_filters_apply_clear_and_follow_the_occupancy_click(): void
    {
        $service = $this->service();
        $user    = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        Production::create(['note_id' => Note::create(['note' => '4000009002'])->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id, 'status' => 2, 'completed' => false, 'confirmed' => false]);

        foreach ([Stack::class, PartnerStack::class] as $component) {
            Livewire::test($component, ['service' => $service->uuid])
                ->call('filterUser', $user->id)
                ->assertSet('filters', ['user' => [(string) $user->id]])
                ->call('toggleStatus', 2)
                ->assertSet('filters', ['user' => [(string) $user->id], 'status' => ['2']])
                ->call('clearAllCriteria')
                ->assertSet('filters', []);
        }
    }
}
