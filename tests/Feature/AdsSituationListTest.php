<?php

use App\Http\Livewire\Engineers\Ads\Status;
use App\Services\Engineers\AdsSituationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

// Situação de ADS (Engenharia): lista de controle no padrão de docs/PADRAO_LISTAS_OPERACIONAIS.md

uses(RefreshDatabase::class);

it('a tela de situação de ADS segue o padrão: sem CSS/JS inline nem filtro legado', function () {
    $view = file_get_contents(resource_path('views/livewire/engineers/ads/status.blade.php'));

    expect($view)->not->toContain('<style')
        ->not->toContain('<script')
        ->not->toContain('components.filter.filter')
        ->not->toMatch('/style="[^"]*(color|cursor|background)/')
        ->not->toMatch('/text-bg-|table-dark|class="badge /')
        ->toContain('ops-list ops-control')
        ->toContain('<x-ops.filters');
});

it('o service devolve tons semânticos e não classes Bootstrap', function () {
    $row = (object) [
        'work_report_id' => 1, 'note_number' => '123', 'company_name' => 'X', 'informed_at' => now()->subDays(40),
        'state'          => 'ES', 'tacit_due_at' => now()->subDays(5), 'tacit_delivered_at' => null, 'tacit' => 0,
        'adsform_id'     => null, 'ads_created_at' => null,
    ];

    $enriched = app(AdsSituationService::class)->enrichRow($row);

    expect($enriched['status_code'])->toBe('vencida_sem_entrega')
        ->and($enriched['status_tone'])->toBe('red')
        ->and($enriched['due_tone'])->toBe('red')
        ->and($enriched)->not->toHaveKey('status_badge');
});

it('empreiteira é filtro padrão: vai ao Excel/consulta como ids e ignora valor desconhecido', function () {
    // As opções saem do cache (empreiteiras com informe); o teste fixa a lista sem montar informes.
    Cache::put('ops-filter:' . md5(Status::class) . ':company', [['v' => '7', 'l' => 'Empreiteira Teste']], 60);

    Livewire::test(Status::class)
        ->assertSet('statusFilter', 'atual')
        ->call('applyListFilters', ['company' => ['7', '999999'], 'x' => ['1']])
        ->assertSet('filters', ['company' => ['7']])
        ->call('clearAllCriteria')
        ->assertSet('filters', []);
});

it('empreiteira usa o id (uuid) como veio, sem converter para número', function () {
    $uuid = '9beeb6dd-3516-4916-82d6-3718d383a478';
    Cache::put('ops-filter:' . md5(Status::class) . ':company', [['v' => $uuid, 'l' => 'Compel Linhares']], 60);

    $component = Livewire::test(Status::class)->call('applyListFilters', ['company' => [$uuid]]);

    $filters = (new ReflectionMethod($component->instance(), 'filters'))->invoke($component->instance());

    expect($filters['companyIds'])->toBe([$uuid]);
});

it('link antigo com status disabled cai no escopo Atual', function () {
    Livewire::withQueryParams(['status' => 'disabled'])
        ->test(Status::class)
        ->assertSet('statusFilter', 'atual');
});
