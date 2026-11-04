<?php

use App\Exports\Dispatchs\DispatchSupervisionControlExport;
use App\Http\Livewire\Dispatchs\Supervision\{PartnerStack, Stack};
use App\Jobs\Dispatchs\ExportDispatchSupervisionJob;
use App\Models\{Company, Note, Order, Production, Service, User};
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, Notification, Storage};
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Excel do Controle de Fiscalização: docs/Padrao_Estilizacao_Exportacoes.md. Mesma consulta da tela (SupervisionControlQuery).

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
});

function controlFixture(): array
{
    $admin   = User::factory()->create(['contract' => false, 'bypassprod' => false, 'superadm' => true, 'name' => 'Ana Despachante']);
    $service = Service::create(['service' => 'Fiscalizacao', 'folder' => 'fiscalizacao']);
    $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
    $worker  = User::factory()->create(['company_id' => $company->id, 'name' => 'Thiago Lucas Silva']);

    $make = function (string $note, int $status, string $rubrica) use ($service, $company, $worker, $admin) {
        $n = Note::create(['note' => $note, 'rubrica' => $rubrica, 'lexp' => 'GUARULHOS', 'postes' => 3, 'type_note' => 1]);
        Order::create(['note_id' => $n->id, 'ordem' => '17' . substr($note, 2), 'statusSist' => 'LIB']);

        return Production::create([
            'note_id'     => $n->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $worker->id,
            'dispatch_by' => $admin->id, 'status' => $status, 'completed' => false, 'confirmed' => false,
            'dispatch_at' => now()->subDays(12), 'att_at' => now()->subDays(5),
        ]);
    };

    return [$admin, $service, $make('4000001111', 2, 'BT Zero'), $make('4000002222', 3, 'Mercado')];
}

function runControlExport(array $params, User $user): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
    (new ExportDispatchSupervisionJob($params, $user->id))->handle(
        app(App\Services\Supervision\SupervisionControlQuery::class),
        app(App\Services\Supervision\SupervisionControlRowPresenter::class),
        app(App\Services\Files\ExportStorage::class),
    );

    $file = collect(Storage::disk('local')->files('exports'))->first();

    return IOFactory::load(Storage::disk('local')->path($file));
}

it('a tela entrega ao Excel os mesmos critérios dos filtros (busca, tipo, usuário, status e localidade)', function () {
    Bus::fake();
    [$admin, $service] = controlFixture();

    foreach ([Stack::class, PartnerStack::class] as $component) {
        Livewire::actingAs($admin)->test($component, ['service' => $service->uuid])
            ->set('search', 'BT Zero')
            ->set('note_type', '1')
            ->call('applyListFilters', ['status' => ['2'], 'user' => []])
            ->call('exportToExcel');
    }

    Bus::assertDispatchedTimes(ExportDispatchSupervisionJob::class, 2);
    Bus::assertDispatched(ExportDispatchSupervisionJob::class, fn ($job) => $job->params['service'] === $service->uuid
        && $job->params['search'] === 'BT Zero'
        && $job->params['note_type'] === '1'
        && $job->params['status'] === ['2']
        && $job->params['cityCodes'] === null
        && (string) $job->userId === (string) $admin->id);
});

it('gera o Excel só com o que a tela mostra, com as abas do padrão e a auditoria preenchida', function () {
    [$admin, $service] = controlFixture();

    $book = runControlExport(['service' => $service->uuid, 'status' => ['2']], $admin);

    expect($book->getSheetNames())->toBe(['Controle', 'Resumo', 'Controle Exportacao']);

    $sheet = $book->getSheetByName('Controle');

    expect($sheet->getCell('A1')->getValue())->toBe('SICODE - CONTROLE')
        ->and($sheet->getCell('A2')->getValue())->toBe('Nota')
        ->and($sheet->getCell('A3')->getValue())->toEqual(4000001111)
        ->and($sheet->getCell('A4')->getValue())->toBeNull() // o status 3 ficou de fora
        ->and($sheet->getCell('B3')->getValue())->toEqual(1700001111)
        ->and($sheet->getCell('J3')->getValue())->toBe('Thiago Lucas Silva')
        ->and($sheet->getCell('H3')->getValue())->toBe('Ana Despachante');

    $headings = collect(range(1, count(DispatchSupervisionControlExport::HEADINGS)))->map(fn ($c) => $sheet->getCell([$c, 2])->getValue());
    expect($headings->all())->toBe(DispatchSupervisionControlExport::HEADINGS);

    $audit = collect($book->getSheetByName('Controle Exportacao')->toArray())->slice(2)->pluck(1, 0);

    expect($audit['Usuário'])->toBe('Ana Despachante')
        ->and($audit['E-mail'])->toBe($admin->email)
        ->and($audit['Tipo'])->toBe('Controle de fiscalizacao')
        ->and($audit['Total de notas'])->toBe('1')
        ->and($audit['Status'])->not->toBe('Todos');

    Notification::assertSentTo($admin, SystemNotification::class);
});

it('sem filtro exporta todas as notas e o Resumo soma 100%', function () {
    [$admin, $service] = controlFixture();

    $book = runControlExport(['service' => $service->uuid], $admin);

    expect($book->getSheetByName('Controle')->getCell('A4')->getValue())->not->toBeNull();

    $summary = collect($book->getSheetByName('Resumo')->toArray())->slice(2);

    expect($summary->sum(1))->toBe(2)
        ->and(round($summary->sum(2), 2))->toBe(100.0);
});

it('a parceira só exporta as empresas do contrato dela', function () {
    [, $service, $mine, $other] = controlFixture();

    $partnerCompany = Company::create(['name' => 'Outra Empreiteira', 'email' => 'o@example.com']);
    $other->update(['company_id' => $partnerCompany->id]);

    $partner = User::factory()->create(['contract' => true, 'bypassprod' => false, 'superadm' => false, 'company_id' => $mine->company_id, 'name' => 'Parceira Compel']);

    $book = runControlExport(['service' => $service->uuid], $partner);

    expect($book->getSheetByName('Controle')->getCell('A3')->getValue())->toEqual(4000001111)
        ->and($book->getSheetByName('Controle')->getCell('A4')->getValue())->toBeNull();
});

it('o job não usa mais o construtor legado de notificação nem a consulta duplicada', function () {
    $job = file_get_contents(app_path('Jobs/Dispatchs/ExportDispatchSupervisionJob.php'));

    expect($job)->toContain('UserNotificationData')->not->toContain('Storage::url');
});
