<?php

use App\Http\Livewire\Closure\Dispatch\{Control, Index as DispatchIndex};
use App\Http\Livewire\Closure\Work\{Available, History, Index as WorkIndex};
use App\Jobs\Closure\ExportClosureListJob;
use App\Models\{Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, Notification, Queue, Storage};
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

function exportReadSheets(string $absolutePath): array
{
    $book = IOFactory::load($absolutePath);

    return collect($book->getSheetNames())->mapWithKeys(fn ($name) => [$name => $book->getSheetByName($name)->toArray(null, true, false, false)])->all();
}

it('filters the Control and service lists by status usuário and offers the options with counts', function () {
    $f = closureScreenFixture('IN_PROGRESS');
    Order::query()->whereKey($f['order']->id)->update(['statusUser' => 'JAND INVE BLOQ']);
    $second = closureWorkflowFixtureOrderInProduction($f, 'IN_PROGRESS');
    Order::query()->whereKey($second['order']->id)->update(['statusUser' => 'ABER CAOI']);

    Livewire::actingAs($f['dispatcher'])->test(Control::class)
        ->assertSee('Status usuário')
        ->assertSee('JAND INVE')
        ->assertSee('ABER CAOI')
        ->assertSee($f['order']->ordem)
        ->set('statusUsers', ['JAND INVE'])
        ->assertSee($f['order']->ordem)
        ->assertDontSee($second['order']->ordem)
        ->assertSee('Status usuário: JAND INVE')
        ->call('removeFilter', 'su', 'JAND INVE')
        ->assertSet('statusUsers', [])
        ->assertSee($second['order']->ordem);

    Livewire::actingAs($f['internal'])->test(WorkIndex::class)->set('statusUsers', ['ABER CAOI'])->assertDontSee($f['order']->ordem);
});

it('dispatches the export job from every list with the filters currently applied', function () {
    Bus::fake();
    $f = closureScreenFixture('IN_PROGRESS');

    Livewire::actingAs($f['dispatcher'])->test(Control::class)->set('statusUsers', ['JAND INVE'])->call('exportList')->assertDispatchedBrowserEvent('swal');
    Livewire::actingAs($f['internal'])->test(WorkIndex::class)->call('exportList');
    Livewire::actingAs($f['internal'])->test(History::class)->call('exportList');
    Livewire::actingAs($f['internal'])->test(Available::class)->call('exportList');
    Livewire::actingAs($f['dispatcher'])->test(DispatchIndex::class)->set('search', 'abc')->call('exportList');

    Bus::assertDispatched(ExportClosureListJob::class, 5);
    Bus::assertDispatched(ExportClosureListJob::class, fn ($job) => $job->component === Control::class && $job->filters['statusUsers'] === ['JAND INVE'] && $job->queue === 'exports');
    Bus::assertDispatched(ExportClosureListJob::class, fn ($job) => $job->component === DispatchIndex::class && $job->filters['search'] === 'abc');
});

it('generates the styled workbook for the Control list and for the Despacho list, then notifies the user', function () {
    Notification::fake();
    Storage::fake('local');
    Queue::fake();

    $f = closureScreenFixture('IN_PROGRESS');
    Order::query()->whereKey($f['order']->id)->update(['statusUser' => 'JAND INVE BLOQ', 'descricao' => 'Obra de teste']);

    // Controle (estados despachados)
    (new ExportClosureListJob(Control::class, ['status' => 'all', 'statusUsers' => ['JAND INVE']], (string) $f['dispatcher']->id))->handle();

    $files = Storage::disk('local')->files('exports');
    expect($files)->toHaveCount(1);
    $sheets = exportReadSheets(Storage::disk('local')->path($files[0]));

    expect(array_keys($sheets))->toBe(['Ordens', 'Resumo', 'Controle Exportacao'])
        ->and($sheets['Ordens'][0][0])->toBe('SICODE - ORDENS')           // título (padrão SICODE)
        ->and($sheets['Ordens'][1])->toContain('Status usuário', 'Depósito bloqueado', 'Data de entrada no status') // cabeçalho
        ->and((string) $sheets['Ordens'][2][2])->toBe($f['order']->ordem)           // 1ª linha de dados
        ->and($sheets['Ordens'])->toHaveCount(3);                          // título + cabeçalho + 1 Ordem (filtro de status usuário)

    // Nota e Ordem com formato inteiro (12 dígitos não podem virar notação científica).
    $book = IOFactory::load(Storage::disk('local')->path($files[0]));
    expect($book->getSheetByName('Ordens')->getStyle('C3')->getNumberFormat()->getFormatCode())->toBe('0');

    $audit = collect($sheets['Controle Exportacao'])->pluck(1, 0);
    expect($audit['Tipo de exportação'])->toBe('Controle de Encerramento')->and($audit['Filtro - Status usuário'])->toBe('JAND INVE');

    Notification::assertSentTo($f['dispatcher'], \App\Notifications\SystemNotification::class);

    // Despacho (todas as Ordens elegíveis, inclusive não despachadas)
    Storage::disk('local')->deleteDirectory('exports');
    (new ExportClosureListJob(DispatchIndex::class, ['search' => '', 'types' => [], 'status' => '', 'statusUsers' => [], 'sort' => null, 'dir' => null], (string) $f['dispatcher']->id))->handle();

    expect(Storage::disk('local')->files('exports'))->toHaveCount(1);
});

it('refuses the Despacho export for users without dispatch access', function () {
    Queue::fake();
    $f = closureScreenFixture();

    expect(fn () => (new ExportClosureListJob(DispatchIndex::class, [], (string) $f['internal']->id))->handle())->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});
