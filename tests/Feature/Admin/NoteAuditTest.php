<?php

use App\Http\Livewire\Admin\Audits\NoteAudit;
use App\Models\{Audit, Note, Service, User};
use App\Services\Audit\AuditPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan, DB};
use Livewire\Livewire;

// Auditoria de notas: filtra pelas colunas indexadas (extraídas do JSON na gravação), com período padrão de 7 dias.

uses(RefreshDatabase::class);

function auditFixture(): array
{
    $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
    $user    = User::factory()->create(['name' => 'Caio Teixeira']);
    $note    = Note::query()->create(['note' => '4002046049', 'type_note' => 1]);
    $other   = Note::query()->create(['note' => '4002046251', 'type_note' => 1]);

    return compact('service', 'user', 'note', 'other');
}

function makeAudit(array $f, Note $note, array $before, array $after, string $action = 'updated', ?string $at = null): Audit
{
    $base = ['id' => 77, 'note_id' => $note->id, 'service_id' => $f['service']->uuid];

    $audit = Audit::create([
        'user_id' => $f['user']->id, 'action' => $action, 'model_class' => App\Models\Production::class,
        'before'  => json_encode($base + $before), 'after' => json_encode($base + $after),
    ]);

    if ($at) {
        DB::table('audits')->where('id', $audit->id)->update(['created_at' => $at]);
    }

    return $audit->fresh();
}

it('extrai nota, atividade, registro e campos alterados do JSON ao gravar', function () {
    $f     = auditFixture();
    $audit = makeAudit($f, $f['note'], ['status' => 3, 'user_id' => 'a'], ['status' => 5, 'user_id' => 'a']);

    expect($audit->note_id)->toBe($f['note']->id)
        ->and($audit->service_id)->toBe($f['service']->uuid)
        ->and($audit->record_id)->toBe('77')
        ->and($audit->changes_count)->toBe(1)
        ->and($audit->changed_fields)->toBe(',status,');
});

it('não conta como mudança o que só trocou de tipo (5 x "5", 0 x false, null x "")', function () {
    $diff = AuditPayload::diff(
        ['a' => 5, 'b' => 0, 'c' => null, 'd' => 'x', 'updated_at' => '1'],
        ['a' => '5', 'b' => false, 'c' => '', 'd' => 'y', 'updated_at' => '2']
    );

    expect(array_keys($diff))->toBe(['d']);
});

it('o período padrão são 7 dias; buscar por nota ou registro olha todo o histórico', function () {
    $f = auditFixture();
    makeAudit($f, $f['note'], ['status' => 1], ['status' => 2]);
    makeAudit($f, $f['note'], ['status' => 2], ['status' => 3], 'updated', now()->subDays(40)->toDateTimeString());

    $component = Livewire::test(NoteAudit::class);
    expect($component->viewData('audits')->total())->toBe(1);

    expect($component->set('period', 'all')->viewData('audits')->total())->toBe(2);
    expect($component->set('period', '7d')->set('note', '4002046049')->viewData('audits')->total())->toBe(2);
});

it('filtra por nota, usuário, ação e campo alterado sem ler o JSON', function () {
    $f = auditFixture();
    makeAudit($f, $f['note'], ['status' => 1], ['status' => 2]);
    makeAudit($f, $f['other'], ['user_id' => 'a'], ['user_id' => 'b']);
    makeAudit($f, $f['other'], [], ['status' => 9], 'created');

    $component = Livewire::test(NoteAudit::class);

    expect($component->set('note', '4002046251')->viewData('audits')->total())->toBe(2)
        ->and($component->set('note', '')->call('applyListFilters', ['field' => ['status']])->viewData('audits')->total())->toBe(1)
        ->and($component->call('applyListFilters', ['field' => ['user_id']])->viewData('audits')->total())->toBe(1)
        ->and($component->call('applyListFilters', ['action' => ['created']])->viewData('audits')->total())->toBe(1)
        ->and($component->call('applyListFilters', ['user' => [(string) $f['user']->id]])->viewData('audits')->total())->toBe(3);

    $component->call('clearAllCriteria');
    expect($component->get('filters'))->toBe([])->and($component->get('period'))->toBe('7d');
});

it('mostra o que mudou na linha com rótulos e abre o detalhe', function () {
    $f     = auditFixture();
    $audit = makeAudit($f, $f['note'], ['status' => 3, 'user_id' => $f['user']->id], ['status' => 5, 'user_id' => $f['user']->id]);

    $component = Livewire::test(NoteAudit::class)
        ->assertSee('4002046049')
        ->assertSee('Caio Teixeira')
        ->assertSee('Status:')
        ->assertSee('Em Andamento (3)')
        ->assertDontSee('Responsável:')   // não mudou
        ->call('openDetails', $audit->id)
        ->assertSee('Detalhes da auditoria');

    expect($component->viewData('detail')['rows'])->toHaveCount(1);

    $component->call('closeDetails')->assertDontSee('Detalhes da auditoria');
});

it('o backfill preenche o contexto das auditorias antigas, sem apagar o que já existe', function () {
    $f     = auditFixture();
    $audit = makeAudit($f, $f['note'], ['status' => 1], ['status' => 2]);

    DB::table('audits')->where('id', $audit->id)->update(['note_id' => null, 'service_id' => null, 'record_id' => null, 'changes_count' => null, 'changed_fields' => null]);

    Artisan::call('audits:backfill-context', ['--changes' => true]);

    $fresh = $audit->fresh();
    expect($fresh->note_id)->toBe($f['note']->id)
        ->and($fresh->record_id)->toBe('77')
        ->and($fresh->changed_fields)->toBe(',status,');
});

it('data em UTC no antes e no fuso local no depois não é mudança', function () {
    config(['app.timezone' => 'America/Sao_Paulo']);

    $diff = AuditPayload::diff(
        ['dispatch_at' => '2026-09-23T17:44:24.000000Z', 'completed_at' => null],
        ['dispatch_at' => '2026-09-23 14:44:24', 'completed_at' => '2026-09-23 15:00:00']
    );

    expect(array_keys($diff))->toBe(['completed_at']);
});

it('o detalhe traz nomes de usuário, empresa, status com selo e sim/não nos booleanos', function () {
    $f     = auditFixture();
    $new   = User::factory()->create(['name' => 'Izadora Tintore']);
    $audit = makeAudit(
        $f,
        $f['note'],
        ['status' => 2, 'att_by' => null, 'user_id' => $f['user']->id, 'completed' => false, 'eo' => 0],
        ['status' => 5, 'att_by' => $new->id, 'user_id' => $new->id, 'completed' => true, 'eo' => true]
    );

    $component = Livewire::test(NoteAudit::class)->call('openDetails', $audit->id)
        ->assertSee('Izadora Tintore')->assertSee('Caio Teixeira')
        ->assertSee('Atribuído por')->assertSee('Responsável')
        ->assertSee('Sim')->assertSee('Entidade externa');

    $rows = collect($component->viewData('detail')['rows'])->keyBy('key');
    expect($rows['status']['tones'])->toBe(['blue', 'green'])
        ->and($rows['status']['before'])->toContain('Atribuido')
        ->and($rows['att_by']['after'])->toBe('Izadora Tintore')
        ->and($rows['completed']['after'])->toBe('Sim');
});
