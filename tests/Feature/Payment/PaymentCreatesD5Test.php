<?php

use App\Http\Livewire\Services\Payment\Forms\Jobform;
use App\Models\{Analise, Company, FiveNote, MeasurementProductionContext, Note, Production, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// Encerramento do 1º ciclo (Medição/Pagamento) criando a D5 que o fiscal não solicitou: assistente fiscal → dados,
// e a D5 só é cadastrada ao ENCERRAR.

uses(RefreshDatabase::class);

function createD5Fixture(bool $withFiscal = true): array
{
    $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
    $pay     = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
    $fis     = Service::create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao']);
    $user    = User::factory()->create(['name' => 'Izadora Tintore']);
    $fiscal  = User::factory()->create(['name' => 'Carlos Fiscal']);
    $note    = Note::query()->create(['note' => 'N-D5', 'type_note' => 1]);

    $reportId = DB::table('work_reports')->insertGetId([
        'note_id'  => $note->id, 'company_id' => $company->id, 'informed_at' => now(), 'canceled' => false,
        'rejected' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $fiscalProduction = $withFiscal ? Production::query()->create([
        'note_id' => $note->id, 'service_id' => $fis->uuid, 'company_id' => $company->id, 'user_id' => $fiscal->id,
        'status'  => 5, 'completed' => true, 'completed_at' => now()->subDay(), 'rejected' => false, 'd5' => false,
    ]) : null;

    $production = Production::query()->create([
        'note_id' => $note->id, 'service_id' => $pay->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
        'status'  => 3, 'completed' => false, 'rejected' => false, 'd5' => false,
    ]);

    DB::table('work_report_flow_productions')->insert([
        'work_report_id' => $reportId, 'production_id' => $production->id, 'stage' => 'payment', 'final_scope' => 'general',
        'is_current'     => true, 'linked_at' => now(), 'source' => 'test',
    ]);

    MeasurementProductionContext::create([
        'production_id'     => $production->id, 'work_report_id' => $reportId, 'cycle' => MeasurementProductionContext::CYCLE_ONE,
        'active_demand_key' => "work_report:{$reportId}",
    ]);

    return compact('company', 'user', 'fiscal', 'note', 'production', 'fiscalProduction', 'reportId');
}

function openCreateD5(array $f)
{
    test()->actingAs($f['user']);

    return Livewire::test(Jobform::class)
        ->call('showProduction', $f['production']->id)
        ->call('startCreateD5');
}

function fillD5($component, array $f, string $fiscalChoice)
{
    return $component
        ->set('d5Fiscal', $fiscalChoice)->call('confirmD5Fiscal')
        ->set('newD5.note_d5', '987654')
        ->set('newD5.company_id', $f['company']->id)
        ->set('newD5.reason', '01_FALTA DE MATERIAIS')
        ->set('newD5.codify', '01')
        ->set('newD5.loc_install', '708-EP-1')
        ->set('analise.postes', 3)
        ->set('analise.conclusion', 'MEDIDO');
}

it('só cadastra a D5 ao encerrar: antes disso nada é gravado', function () {
    $f = createD5Fixture();

    $component = fillD5(openCreateD5($f), $f, (string) $f['fiscalProduction']->id);

    expect(FiveNote::count())->toBe(0)
        ->and($f['production']->fresh()->dfive)->toBeFalsy();

    $component->call('save');

    expect(FiveNote::count())->toBe(1);
});

it('cria a D5, associa fiscal e própria produção, marca dfive e despacha à empreiteira como ciclo 1', function () {
    $f = createD5Fixture();

    fillD5(openCreateD5($f), $f, (string) $f['fiscalProduction']->id)->call('save');

    $five       = FiveNote::first();
    $production = $f['production']->fresh();

    expect($five->note_d5)->toBe('987654')
        ->and($five->work_report_id)->toBe($f['reportId'])
        ->and($five->visible_partner)->toBeTrue()
        ->and($five->is_payed)->toBeTrue()
        ->and($five->dispatch_at)->not->toBeNull()
        ->and($five->productions()->pluck('productions.id')->sort()->values()->all())
        ->toBe(collect([$f['fiscalProduction']->id, $production->id])->sort()->values()->all())
        ->and($production->dfive)->toBeTruthy()
        ->and($production->completed)->toBeTruthy()
        ->and($production->measurementContext->five_note_id)->toBe($five->id);

    $info = Analise::where('production_id', $production->id)->value('info');
    expect($info)->toContain('gerada pelo usuário Izadora Tintore em nome do fiscal Carlos Fiscal');
});

it('sem fiscal registra só o nome do usuário e associa apenas a própria produção', function () {
    $f = createD5Fixture(false);

    fillD5(openCreateD5($f), $f, 'none')->call('save');

    $five = FiveNote::first();
    $info = Analise::where('production_id', $f['production']->id)->value('info');

    expect($five->productions()->count())->toBe(1)
        ->and($info)->toContain('gerada pelo usuário Izadora Tintore')
        ->and($info)->not->toContain('em nome do fiscal');
});

it('exige a escolha do fiscal e valida os dados antes de encerrar, sem gravar nada', function () {
    $f = createD5Fixture();

    $component = openCreateD5($f)->call('confirmD5Fiscal')->assertHasErrors('d5Fiscal');
    expect($component->get('d5Stage'))->toBe('fiscal');

    $component = $component->set('analise.postes', 1)->set('analise.conclusion', 'MEDIDO')->set('d5Fiscal', 'none')->call('confirmD5Fiscal')
        ->call('to_finish')->assertDispatchedBrowserEvent('swal')   // etapas obrigatórias incompletas: encerrar fica bloqueado
        ->call('save');

    expect(collect($component->payload['effects']['dispatches'])->pluck('data.title'))->toContain('Erros de Validação');

    expect(FiveNote::count())->toBe(0)->and($f['production']->fresh()->completed)->toBeFalsy();
});

it('rejeita fiscal que não encerrou atividade da Nota e D5 duplicada no informe', function () {
    $f     = createD5Fixture();
    $other = Production::query()->create([
        'note_id'    => Note::query()->create(['note' => 'OUTRA', 'type_note' => 1])->id, 'service_id' => $f['fiscalProduction']->service_id,
        'company_id' => $f['company']->id, 'user_id' => $f['fiscal']->id, 'status' => 5, 'completed' => true, 'rejected' => false, 'd5' => false,
    ]);

    openCreateD5($f)->set('d5Fiscal', (string) $other->id)->call('confirmD5Fiscal')->assertHasErrors('d5Fiscal');

    FiveNote::create(['note_id' => $f['note']->id, 'work_report_id' => $f['reportId'], 'note_d5' => '111']);

    // Já existe D5 para o informe: o encerramento falha inteiro (sem D5 nova, produção segue aberta).
    fillD5(openCreateD5($f), $f, 'none')->call('save')->assertDispatchedBrowserEvent('swal');

    expect(FiveNote::count())->toBe(1)->and($f['production']->fresh()->completed)->toBeFalsy();
});

it('encerra mesmo com conjunto numérico vindo da Nota e avisa quando a validação falha', function () {
    $f = createD5Fixture();
    $f['note']->update(['num_material' => 12]);

    fillD5(openCreateD5($f), $f, 'none')->call('to_finish')->assertDispatchedBrowserEvent('alertar');

    $bad = fillD5(openCreateD5($f), $f, 'none')->set('newD5.note_d5', 'abc')->call('to_finish');
    expect(collect($bad->payload['effects']['dispatches'])->pluck('data.title'))->toContain('Dados da D5 incompletos');
});

it('com anexos, o upload continua montado depois de encerrar e o evidenceSaved fecha o modal', function () {
    $f = createD5Fixture();

    $component = fillD5(openCreateD5($f), $f, 'none')->emit('hasEvidence', true)->call('save');

    $component->assertSee('Anexar Arquivos na D5')
        ->assertDispatchedBrowserEvent('swal');
    expect($component->get('production'))->not->toBeNull();

    $component->emit('evidenceSaved');
    expect($component->get('production'))->toBeNull()->and($component->get('creatingD5'))->toBeFalse();
});

it('sem anexos o modal fecha direto ao encerrar', function () {
    $f = createD5Fixture();

    $component = fillD5(openCreateD5($f), $f, 'none')->call('save');

    expect($component->get('production'))->toBeNull();
});
