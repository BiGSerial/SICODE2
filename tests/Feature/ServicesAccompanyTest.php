<?php

use App\Http\Livewire\Services\Concerns\IsActivityAccompanyList;
use App\Models\{Note, Production, User};
use App\Services\Accompany\{ActivityAccompanyProfile, NoteAccompanyRowPresenter};
use App\Support\Ops\{PrazoReal, RowState};
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

// Acompanhamento de Serviços (a fila de trabalho do usuário) no padrão de listas operacionais: docs/PADRAO_LISTAS_OPERACIONAIS.md.

beforeEach(fn () => Carbon::setTestNow('2026-10-31 10:00:00'));

afterEach(fn () => Carbon::setTestNow());

function accompanyProduction(array $production = [], array $note = []): Production
{
    $p             = new Production($production + ['status' => 2, 'priority' => false]);
    $p->block      = $production['block'] ?? false;
    $p->completed  = false;
    $p->att_at     = now()->subDays(3);
    $p->updated_at = now()->subDays(2);
    $p->setRelation('Note', new Note($note + ['note' => '4001922955', 'numPedido' => '170000014182', 'rubrica' => 'Mercado', 'lexp' => 'VITORIA', 'group1' => 'ZN', 'group2' => 'G2', 'material' => 'LN INSTALACAO', 'dt_created' => now()->subDays(12)->toDateString(), 'days_left' => 11]));

    return $p;
}

it('cada atividade tem o seu perfil, e o Desenho usa o de Análises', function () {
    expect(ActivityAccompanyProfile::for('analises')['event'])->toBe('open_analise_analise')
        ->and(ActivityAccompanyProfile::for('desenho')['key'])->toBe('analises')
        ->and(ActivityAccompanyProfile::for('analises_pre')['event'])->toBe('open_analise_preanalise')
        ->and(ActivityAccompanyProfile::for('cadastro')['event'])->toBe('open_analise_cadastro')
        ->and(ActivityAccompanyProfile::for('analises_inc')['event'])->toBe('open_analise_incorporation')
        ->and(ActivityAccompanyProfile::for('fluxo_reverso')['event'])->toBe('open_analise_analise')
        ->and(ActivityAccompanyProfile::for('qualquer')['key'])->toBe('analises');   // sem perfil: o de Análises
});

it('o limite de pausa e o encerramento em massa seguem a característica de cada atividade', function () {
    expect(ActivityAccompanyProfile::for('analises')['limitPause'])->toBe(50)
        ->and(ActivityAccompanyProfile::for('analises_inc')['limitPause'])->toBe(3)
        ->and(ActivityAccompanyProfile::for('cadastro')['limitPause'])->toBe(5000)
        ->and(ActivityAccompanyProfile::for('fluxo_reverso')['limitPause'])->toBe(1000)
        ->and(ActivityAccompanyProfile::for('analises')['bulk'])->toBe('analysis')
        ->and(ActivityAccompanyProfile::for('analises_pre')['bulk'])->toBe('preanalysis')
        ->and(ActivityAccompanyProfile::for('fluxo_reverso')['bulk'])->toBe('reverse')
        ->and(ActivityAccompanyProfile::for('cadastro')['bulk'])->toBeNull()
        ->and(ActivityAccompanyProfile::for('analises_inc')['bulk'])->toBeNull()
        ->and(ActivityAccompanyProfile::for('cadastro')['pauseColumn'])->toBeTrue()
        ->and(ActivityAccompanyProfile::for('cadastro')['group2'])->toBeFalse();
});

it('todo perfil aponta para um formulário Livewire que existe e para um modal', function () {
    foreach (ActivityAccompanyProfile::folders() as $folder) {
        $profile = ActivityAccompanyProfile::for($folder);

        expect(class_exists(Livewire\Livewire::getClass($profile['form'])))->toBeTrue("formulário {$profile['form']} ({$folder})")
            ->and($profile['modal'])->toBe('analise_form')
            ->and($profile['event'])->toStartWith('open_analise_');
    }
});

it('o prazo real vem da criação da nota em Análises e do SAP em Cadastro, Incorporação e Reverso', function () {
    $created = Carbon::parse(now()->subDays(12)->toDateString());

    expect(PrazoReal::daysLeft($created, 11, PrazoReal::SOURCE_CREATED))->toBe(18)    // 30 − 12 dias de vida
        ->and(PrazoReal::daysLeft($created, 11, PrazoReal::SOURCE_SAP))->toBe(11)     // o SAP já descontou os status não contabilizáveis
        ->and(PrazoReal::daysLeft(null, null, PrazoReal::SOURCE_CREATED))->toBeNull()
        ->and(PrazoReal::daysLeft($created, null, PrazoReal::SOURCE_SAP))->toBeNull()
        ->and(PrazoReal::elapsed(11))->toBe(19)->and(PrazoReal::elapsed(null))->toBeNull();
});

it('colore o prazo real: vencido ou até 5 dias vermelho, 6 a 9 âmbar, 10 ou mais verde', function (?int $left, string $tone) {
    expect(PrazoReal::tone($left))->toBe($tone);
})->with([[null, 'muted'], [-3, 'red'], [0, 'red'], [5, 'red'], [6, 'amber'], [9, 'amber'], [10, 'green'], [30, 'green']]);

it('o tooltip do prazo real diz de onde vem a conta e quanto falta', function () {
    expect(PrazoReal::hint(11, PrazoReal::SOURCE_SAP))->toContain('sem os tempos em status não contabilizáveis')->and(PrazoReal::hint(11, PrazoReal::SOURCE_SAP))->toContain('11 dias para vencer')
        ->and(PrazoReal::hint(1, PrazoReal::SOURCE_CREATED))->toContain('desde a criação')->and(PrazoReal::hint(1, PrazoReal::SOURCE_CREATED))->toContain('1 dia para vencer')
        ->and(PrazoReal::hint(-2, PrazoReal::SOURCE_CREATED))->toContain('Prazo vencido');
});

it('a linha traz o estado, o prazo e os dias de atribuição prontos, sem classe Bootstrap', function () {
    $row = (new NoteAccompanyRowPresenter())->present(accompanyProduction(), ActivityAccompanyProfile::for('analises'), [], now());

    expect($row['note'])->toBe('4001922955')
        ->and($row['attDays'])->toBe(3)
        ->and($row['prazoElapsed'])->toBe(12)->and($row['prazoLeft'])->toBe(18)->and($row['prazoTone'])->toBe('green')   // 18 dias para vencer
        ->and($row['createdDays'])->toBe(12)
        ->and($row['state'])->toBe(RowState::FREE)
        ->and($row['canAct'])->toBeTrue()
        ->and(json_encode($row))->not->toContain('text-bg-');

    $sap = (new NoteAccompanyRowPresenter())->present(accompanyProduction(), ActivityAccompanyProfile::for('cadastro'), [], now());

    expect($sap['prazoElapsed'])->toBe(19)->and($sap['prazoTone'])->toBe('green');
});

it('produção bloqueada fica azul e sem ação; em pausa mostra há quanto tempo e oferece retomar', function () {
    $presenter = new NoteAccompanyRowPresenter();
    $profile   = ActivityAccompanyProfile::for('cadastro');

    $blocked = $presenter->present(accompanyProduction(['block' => true]), $profile, [], now());

    expect($blocked['state'])->toBe(RowState::ASSIGNED)->and($blocked['canAct'])->toBeFalse();

    $paused = $presenter->present(accompanyProduction(['status' => 4]), $profile, [], now());

    expect($paused['paused'])->toBeTrue()->and($paused['pausedFor'])->toBe('2 dias')->and($paused['actionLabel'])->toBe('Retomar');

    expect($presenter->present(accompanyProduction(['status' => 3]), $profile, [], now())['actionLabel'])->toBe('Continuar')
        ->and($presenter->present(accompanyProduction(['status' => 2]), $profile, [], now())['actionLabel'])->toBe('Iniciar');
});

/** Componente mínimo com a trait, para testar o comportamento sem banco. */
function accompanyHarness(): object
{
    return new class () {
        use IsActivityAccompanyList;
        use WithPagination;

        public array $events = [];

        public function __construct()
        {
            $this->service = (object) ['uuid' => 'abc', 'folder' => 'analises', 'service' => 'Análises'];
        }

        public function dispatchBrowserEvent($event, $data = [])
        {
            $this->events[] = [$event, $data];
        }

        public function resetPage($pageName = 'page'): void
        {
        }

        public function callProtected(string $method)
        {
            return $this->{$method}();
        }
    };
}

it('não zera a seleção quando o navegador envia as caixas marcadas, mas zera ao mudar a busca', function () {
    $h           = accompanyHarness();
    $h->selected = ['1', '2'];

    $h->updated('selected');
    $h->updated('selected.0');
    $h->updated('bulkConclusion');

    expect($h->selected)->toBe(['1', '2']);

    $h->updated('search');

    expect($h->selected)->toBe([]);
});

it('só o superadm vê a fila de outro usuário', function () {
    $user = (new User(['name' => 'Ana']))->forceFill(['id' => 'uuid-ana']);

    test()->actingAs($user);

    $h         = accompanyHarness();
    $h->user_s = 'uuid-outro';

    Gate::define('superadm', fn () => false);
    expect($h->callProtected('targetUserId'))->toBe('uuid-ana');

    Gate::define('superadm', fn () => true);
    expect($h->callProtected('targetUserId'))->toBe('uuid-outro');

    $h->user_s = '';
    expect($h->callProtected('targetUserId'))->toBe('uuid-ana');
});

it('só aceita as abas conhecidas e limpa os critérios sem mexer na quantidade por página', function () {
    $h          = accompanyHarness();
    $h->perPage = 100;

    $h->setTab('transferencias');
    expect($h->tab)->toBe('transferencias');

    $h->setTab('qualquer-coisa');
    expect($h->tab)->toBe('producao');

    $h->search      = 'x';
    $h->user_s      = 'uuid';
    $h->multiSearch = ['1'];
    $h->clearAllCriteria();

    expect([$h->search, $h->user_s, $h->multiSearch, $h->filters, $h->perPage])->toBe(['', '', [], [], 100]);
});

it('a busca em lote vira uma lista de notas ou pedidos, sem repetidos', function () {
    $h                = accompanyHarness();
    $h->advanceSearch = "4001922955\n4001922955, 170000014182;  4001855169";
    $h->buscarMulti();

    expect($h->multiSearch)->toBe(['4001922955', '170000014182', '4001855169'])->and($h->events[0][0])->toBe('hideModal');
});

it('os cinco acompanhamentos por nota são finos e usam a mesma tela', function () {
    foreach (['Analises', 'Analises_pre', 'Cadastro', 'Incorporation', 'Reverse'] as $dir) {
        $php = file_get_contents(app_path("Http/Livewire/Services/{$dir}/Accompany/Main.php"));

        expect($php)->toContain('IsActivityAccompanyList')->and($php)->toContain("view('livewire.services.accompany.note-list'")
            ->and(substr_count($php, "\n"))->toBeLessThan(40);

        expect(file_exists(resource_path('views/livewire/services/' . strtolower($dir) . '/accompany/main.blade.php')))->toBeFalse("{$dir}: view antiga ainda existe");
    }
});

it('a tela compartilhada não tem CSS nem JS inline, cor de Bootstrap ou filtro legado', function () {
    $html = file_get_contents(resource_path('views/livewire/services/accompany/note-list.blade.php'));

    expect($html)->toContain('closure-dash my-panel ops-list')
        ->and($html)->not->toContain('<style')->and($html)->not->toContain('<script')
        ->and($html)->not->toContain('text-bg-')->and($html)->not->toContain('table-primary')
        ->and($html)->not->toContain('components.filter.filter')->and($html)->not->toContain('$_SESSION')
        ->and($html)->toContain('x-ops.age')->and($html)->toContain('x-ops.filters')->and($html)->toContain('x-ops.pager');

    // O único `style=` aceito é a largura das colunas do colgroup (dado, não layout).
    preg_match_all('/style="([^"]*)"/', $html, $styles);

    foreach ($styles[1] as $style) {
        expect($style)->toMatch('/^width: \d+px$/');
    }
});

it('o prazo real é a data de vencimento da nota, com os dias que faltam embaixo, na cor do prazo', function () {
    $badge = fn (string $attributes) => Illuminate\Support\Facades\Blade::render('<x-ops.prazo ' . $attributes . ' />');

    // Hoje é 31/10/2026: 18 dias para vencer = 18/11/2026.
    $ok = $badge(':left="18"');

    expect($ok)->toContain('18/11/2026')->and($ok)->toContain('18 d')->and($ok)->toContain('tone-green');

    expect($badge(':left="8"'))->toContain('08/11/2026')->and($badge(':left="8"'))->toContain('tone-amber')
        ->and($badge(':left="2"'))->toContain('tone-red');

    $late = $badge(':left="-3"');

    expect($late)->toContain('28/10/2026')->and($late)->toContain('3 dias vencidos')->and($late)->toContain('tone-red')->and($late)->not->toContain('-3 d');
    expect($badge(':left="-1"'))->toContain('1 dia vencido');

    // `elapsed` = dias que já passaram dos 30: 19 passados = 11 para vencer.
    expect($badge(':elapsed="19"'))->toContain('11 d')->and($badge(':elapsed="19"'))->toContain('11/11/2026');

    $none = $badge('');

    expect($none)->toContain('ops-empty')->and($none)->not->toContain('tone-');
});

it('a fila e as listas de trabalho mostram o prazo real pelo componente, nunca como número solto', function () {
    foreach (['accompany/note-list', 'cadastro/main', 'analisesinc/main'] as $view) {
        $html = file_get_contents(resource_path("views/livewire/services/{$view}.blade.php"));

        expect($html)->toContain('<x-ops.prazo')->and($html)->not->toContain("\$row['prazoElapsed']\" :hint");
    }
});
