<?php

use App\Http\Livewire\Services\Analisesinc\Accompany\Main as AnalisesIncAccompany;
use App\Http\Livewire\Services\Comission\Main as ComissionMain;
use App\Http\Livewire\Services\Desenho\Main as DesenhoMain;
use App\Http\Livewire\Services\Levantamento\Main as LevantamentoMain;
use App\Http\Livewire\Services\Publication\Accompany\Main as PublicationAccompany;
use App\Http\Livewire\Services\Supervision\Main as SupervisionMain;
use App\Models\{Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Abas Produção/Transferências por servidor (`$tab`, `?aba=`), nunca Bootstrap: docs/PADRAO_LISTAS_OPERACIONAIS.md

uses(RefreshDatabase::class);

dataset('telas com abas', [
    'Fiscalização'    => [SupervisionMain::class, 'fiscalizacao', ['producao', 'transferencias']],
    'Desenho'         => [DesenhoMain::class, 'desenho', ['producao', 'transferencias']],
    'Levantamento'    => [LevantamentoMain::class, 'levantamento', ['producao', 'transferencias']],
    'Comissionamento' => [ComissionMain::class, 'comissionamento', ['producao', 'transferencias']],
    'Análise (inc)'   => [AnalisesIncAccompany::class, 'analisesinc', ['producao', 'transferencias']],
    'Publicação'      => [PublicationAccompany::class, 'publicacao', ['producao', 'espera', 'transferencias']],
]);

it('guarda a aba no servidor e rejeita aba desconhecida', function (string $component, string $folder, array $tabs) {
    $this->actingAs(User::factory()->create(['contract' => false]));
    $service = Service::create(['service' => ucfirst($folder), 'folder' => $folder]);

    expect($component::TABS)->toBe($tabs);

    $test = Livewire::test($component, ['service' => $service->uuid])->assertSet('tab', 'producao');

    foreach ($tabs as $tab) {
        $test->call('setTab', $tab)->assertSet('tab', $tab)->call('$refresh')->assertSet('tab', $tab);
    }

    $test->call('setTab', 'qualquer-coisa')->assertSet('tab', 'producao');

    Livewire::withQueryParams(['aba' => 'transferencias'])
        ->test($component, ['service' => $service->uuid])
        ->assertSet('tab', 'transferencias');

    Livewire::withQueryParams(['aba' => 'invalida'])
        ->test($component, ['service' => $service->uuid])
        ->assertSet('tab', 'producao');
})->with('telas com abas');

it('não deixa aba do Bootstrap nas telas migradas', function () {
    foreach (['supervision', 'desenho', 'levantamento', 'comission', 'analisesinc/accompany', 'publication/accompany'] as $view) {
        $path = resource_path('views/livewire/services/' . $view . '/main.blade.php');

        expect(file_get_contents($path))->not->toContain('data-bs-toggle="tab"')->not->toContain('tab-pane');
    }
});
