<?php

use App\Exports\Admin\Company\CompanyBulkWorkbookExport;
use App\Http\Livewire\Admin\Company\Create;
use App\Models\{Centerjob, Company, Contract, Service, User};
use App\Services\Admin\Company\CompanyBulkImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

function bulkWorkbook(bool $withData, array $fill = []): string
{
    $path = tempnam(sys_get_temp_dir(), 'bulk') . '.xlsx';
    Excel::store(new CompanyBulkWorkbookExport($withData), basename($path), 'local');
    copy(Storage::disk('local')->path(basename($path)), $path);
    Storage::disk('local')->delete(basename($path));

    if ($fill) {
        $book = IOFactory::load($path);

        foreach ($fill as $sheet => $rows) {
            $ws = $book->getSheetByName($sheet);

            foreach ($rows as $n => $cells) {
                foreach ($cells as $col => $value) {
                    $ws->setCellValueExplicit($col . ($n + 2), $value, is_float($value) ? 'n' : 's');
                }
            }
        }

        IOFactory::createWriter($book, 'Xlsx')->save($path);
    }

    return $path;
}

function seedBulk(): array
{
    $service = Service::query()->create(['service' => 'Cadastro', 'project' => true, 'construction' => false, 'post_construction' => false]);
    $matrix  = Company::query()->create(['name' => 'Edp', 'email' => 'edp@edp.com']);
    $unit    = Company::query()->create(['name' => 'Edp Es', 'email' => 'es@edp.com', 'parent_id' => $matrix->id]);

    return [$service, $matrix, $unit];
}

it('exports the full registry and re-importing it unchanged is a no-op', function () {
    [$service, , $unit] = seedBulk();
    Centerjob::query()->create(['company_id' => $unit->id, 'centerjob' => 'ES1', 'center' => '5503', 'deposit' => '5011']);
    $contract             = new Contract(['number' => 'C-1', 'service' => true, 'construction' => false, 'date_end' => '2030-01-31']);
    $contract->company_id = $unit->id;
    $contract->save();
    $contract->services()->attach($service->id, ['posts' => false, 'qtd' => 0, 'days' => 0, 'dispatch' => true]);

    $plan = app(CompanyBulkImporter::class)->analyze(bulkWorkbook(true));

    expect($plan['summary']['errors'])->toBe(0)->and($plan['summary']['changes'])->toBe(0)->and($plan['summary']['rows'])->toBe(5);
});

it('creates companies, centers, contracts and activities from a sheet and never duplicates on a second run', function () {
    [$service, $matrix] = seedBulk();

    $path = bulkWorkbook(false, [
        'Empresas'            => [['B' => 'Nova Matriz', 'D' => 'Nova@Matriz.com', 'H' => 'vitoria', 'I' => 'ES'], ['B' => 'Unidade Um', 'C' => 'Nova Matriz', 'D' => 'u1@matriz.com', 'E' => '(27) 99999-9999']],
        'Centros e Depositos' => [['B' => 'Nova Matriz / Unidade Um', 'C' => 'u1', 'D' => '100', 'E' => '200']],
        'Contratos'           => [['B' => 'Nova Matriz / Unidade Um', 'C' => 'K-9', 'D' => '31/12/2035', 'E' => 'Serviço']],
        'Atividades'          => [['B' => 'Nova Matriz / Unidade Um | K-9', 'C' => 'Cadastro', 'D' => 'Sim']],
    ]);

    $summary = app(CompanyBulkImporter::class)->apply($path);

    expect($summary['changes'])->toBe(5);
    $unit = Company::where('name', 'Unidade Um')->firstOrFail();
    expect($unit->parent->name)->toBe('Nova Matriz')
        ->and(Company::where('name', 'Nova Matriz')->first()->email)->toBe('nova@matriz.com')
        ->and(Centerjob::where('company_id', $unit->id)->value('centerjob'))->toBe('U1')
        ->and($unit->contracts()->first()->services()->first()->pivot->dispatch)->toBe(1);

    $second = app(CompanyBulkImporter::class)->apply($path);
    expect($second['changes'])->toBe(0)->and(Company::count())->toBe(4)->and(Contract::count())->toBe(1);
});

it('blocks the whole import when anything is wrong', function () {
    seedBulk();

    $path = bulkWorkbook(false, [
        'Empresas'   => [['B' => 'Boa Empresa', 'D' => 'ok@ok.com'], ['B' => 'Ruim', 'D' => 'sem-arroba'], ['B' => 'Boa Empresa', 'D' => 'dup@ok.com']],
        'Contratos'  => [['B' => 'Empresa Fantasma', 'C' => 'X', 'D' => '99/99/2030', 'E' => 'Outro']],
        'Atividades' => [['B' => 'nada', 'C' => 'Inexistente']],
    ]);

    $plan = app(CompanyBulkImporter::class)->analyze($path);
    expect($plan['summary']['errors'])->toBeGreaterThanOrEqual(5);
    expect(fn () => app(CompanyBulkImporter::class)->apply($path))->toThrow(RuntimeException::class);
    expect(Company::where('name', 'Boa Empresa')->exists())->toBeFalse();
});

it('refuses to remove companies with users and contracts with linked users, but removes safe items', function () {
    [, $matrix, $unit] = seedBulk();
    User::factory()->create(['company_id' => $unit->id]);
    Centerjob::query()->create(['company_id' => $unit->id, 'centerjob' => 'ES1']);

    $blocked = bulkWorkbook(false, ['Empresas' => [['A' => 'Remover', 'B' => 'Edp Es', 'C' => 'Edp']]]);
    expect(app(CompanyBulkImporter::class)->analyze($blocked)['summary']['errors'])->toBe(1);

    $ok = bulkWorkbook(false, ['Centros e Depositos' => [['A' => 'Remover', 'B' => 'Edp / Edp Es', 'C' => 'ES1']]]);
    expect(app(CompanyBulkImporter::class)->apply($ok)['changes'])->toBe(1)->and(Centerjob::count())->toBe(0);
});

it('rejects a workbook with altered headers', function () {
    $path = bulkWorkbook(false);
    $book = IOFactory::load($path);
    $book->getSheetByName('Empresas')->setCellValue('B1', 'Outra coisa');
    IOFactory::createWriter($book, 'Xlsx')->save($path);

    expect(fn () => app(CompanyBulkImporter::class)->analyze($path))->toThrow(RuntimeException::class, 'cabeçalhos');
});

it('validates and rejects duplicate names in the create modal', function () {
    $admin = User::factory()->create(['superadm' => true, 'admin' => true]);
    Company::query()->create(['name' => 'Edp', 'email' => 'a@a.com']);

    Livewire::actingAs($admin)->test(Create::class)
        ->set('name', 'edp')->set('email', 'x@x.com')->call('save')
        ->assertHasErrors(['name'])
        ->set('name', 'Outra')->set('email', 'ruim')->call('save')
        ->assertHasErrors(['email'])
        ->set('email', 'bom@bom.com')->set('uf', 'ES')->set('telephone', '(27) 99999-9999')->call('save')
        ->assertHasNoErrors();

    expect(Company::where('name', 'Outra')->exists())->toBeTrue();
});
