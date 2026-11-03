<?php

use App\Models\User;
use App\Support\Menu\{AdsNav, BtzeroNav, CancellationNav, HiringNav, LegalNav, PartnerNav, ProjectReviewNav, QualityNav, ReportsNav, SidebarNav};
use Illuminate\Support\Facades\{Gate, Route};

// Menus laterais no padrão (docs/PADRAO_MENU_LATERAL.md): ADS, Cancelamentos, Análise de projetos, BT Zero, Relatórios, Contratação, Jurídico e Qualidade.

/** @return array<int, array<string, mixed>> */
function navItems(string $nav, array $context = []): array
{
    return collect($nav::resolve($context))->flatMap(fn ($m) => collect($m['groups'])->flatMap(fn ($g) => $g['items']))->all();
}

dataset('rotas do menu', [
    'ADS'                       => [AdsNav::class, [], 'ads.dashboard', 'painel'],
    'cancelar'                  => [CancellationNav::class, [], 'cancellations.index', 'cancelar'],
    'descancelar'               => [CancellationNav::class, [], 'cancellations.uncancellation', 'descancelar'],
    'detalhe do pedido'         => [CancellationNav::class, [], 'cancellations.show', 'minhas'],
    'lista para analisar'       => [ProjectReviewNav::class, [], 'project_review.list', 'lista'],
    'governança'                => [ProjectReviewNav::class, [], 'project_review.dashboard', 'dashboard'],
    'rejeitados do SMC'         => [BtzeroNav::class, [], 'btzero.smcRejecteds', 'rejeitados'],
    'relatório de informes'     => [ReportsNav::class, ['variant' => 'informes'], 'reports.informe_ads_tacita', 'ads-tacita'],
    'relatório de RI'           => [ReportsNav::class, ['variant' => 'retorno-interno'], 'reports.return_intern_dashboard', 'indicadores'],
    'relatório de cancelamento' => [ReportsNav::class, ['variant' => 'cancelamentos'], 'reports.cancellations_list', 'lista'],
    'análise de projetos'       => [ReportsNav::class, ['variant' => 'analise-projetos'], 'reports.project_review_history', 'historico'],
]);

it('localiza o item ativo pela rota da tela', function (string $nav, array $context, string $route, string $item) {
    expect($nav::locate($route, $context)['item'])->toBe($item);
})->with('rotas do menu');

it('Contratação leva a atividade nos parâmetros e localiza as telas de detalhe', function () {
    $context = ['service' => (object) ['uuid' => 'abc-123', 'service' => 'Contratação']];

    foreach (navItems(HiringNav::class, $context) as $item) {
        expect($item['params'])->toBe(['service' => 'abc-123']);
    }

    expect(HiringNav::locate('construction.accompany', $context)['item'])->toBe('contratar')
        ->and(HiringNav::locate('construction.production', $context)['item'])->toBe('lista')
        ->and(HiringNav::locate('construction.returned', $context)['item'])->toBe('aguardando')
        ->and(HiringNav::modules([]))->toBe([]);   // sem atividade, sem menu
});

it('todo item de todo menu desta migração abre ativo na própria rota', function () {
    $menus = [AdsNav::class, CancellationNav::class, ProjectReviewNav::class, BtzeroNav::class, ReportsNav::class, HiringNav::class, QualityNav::class, LegalNav::class];

    Gate::before(fn () => true);
    $this->actingAs(new User(['name' => 'Teste']));

    foreach ($menus as $nav) {
        foreach ($nav::sampleContexts() as $context) {
            foreach (navItems($nav, $context) as $item) {
                expect($nav::locate($item['match'][0], $context)['item'])->toBe($item['key'], "{$nav}: {$item['match'][0]} deveria ativar {$item['key']}");
                expect(Route::has($item['route']))->toBeTrue("{$nav}: rota {$item['route']}");
            }
        }
    }
});

it('Jurídico mostra cada papel só a quem tem o gate, e as telas de detalhe ativam o grupo certo', function () {
    $this->actingAs(new User(['name' => 'Teste']));

    Gate::define('legal.demands.triage', fn () => false);
    Gate::define('legal.demands.answer', fn () => false);
    Gate::define('legal.manager', fn () => false);

    expect(LegalNav::resolve())->toBe([])->and(LegalNav::topSections())->toBe([]);

    Gate::define('legal.demands.answer', fn () => true);

    expect(array_column(LegalNav::resolve()['juridico']['groups'], 'key'))->toBe(['executante'])
        ->and(LegalNav::locate('legal.field.response')['item'])->toBe('atribuicoes');

    Gate::define('legal.demands.triage', fn () => true);
    Gate::define('legal.manager', fn () => true);

    expect(array_column(LegalNav::resolve()['juridico']['groups'], 'key'))->toBe(['controlador', 'executante', 'gestao'])
        ->and(LegalNav::locate('legal.demand.detail')['group'])->toBe('controlador')
        ->and(LegalNav::locate('legal.subdemand.detail')['item'])->toBe('monitor');

    // O dropdown do topo é o mesmo conteúdo do lateral.
    $top = LegalNav::topSections();

    expect(array_column($top, 'label'))->toBe(['Controlador', 'Executante', 'Gestão'])
        ->and($top[0]['items'][0])->toMatchArray(['label' => 'FILA DE DEMANDAS', 'route' => 'legal.queue']);
});

it('Qualidade muda os grupos pelo papel e localiza abas e filas pela chave rota:parâmetro', function () {
    Gate::before(fn () => true);
    $this->actingAs(new User(['name' => 'Teste']));

    $groups = fn (array $context) => array_column(QualityNav::modules($context)['qualidade']['groups'], 'key');

    expect($groups(['mode' => 'manager']))->toBe(['operacao', 'configuracao'])
        ->and($groups(['mode' => 'member', 'n1' => true, 'n2' => false]))->toBe(['n1'])
        ->and($groups(['mode' => 'member', 'n1' => false, 'n2' => true]))->toBe(['n2'])
        ->and($groups(['mode' => 'member', 'n1' => true, 'n2' => true]))->toBe(['n1', 'n2']);

    // "Histórico" aparece uma vez só: no N1; o N2 só o mostra quando a pessoa não é N1.
    $history = fn (array $context) => collect(navItems(QualityNav::class, $context))->where('route', 'quality.history')->count();

    expect($history(['mode' => 'member', 'n1' => true, 'n2' => true]))->toBe(1)
        ->and($history(['mode' => 'member', 'n1' => false, 'n2' => true]))->toBe(1);

    $member = ['mode' => 'member', 'n1' => true, 'n2' => true];

    expect(QualityNav::routeKey('quality.n1', 'analisar'))->toBe('quality.n1:analisar')
        ->and(QualityNav::routeKey('quality.n1'))->toBe('quality.n1:despachar')
        ->and(QualityNav::routeKey('quality.n2', null))->toBe('quality.n2:decidir')
        ->and(QualityNav::routeKey('quality.queue', null, 'aguardando-n2'))->toBe('quality.queue:aguardando-n2')
        ->and(QualityNav::routeKey('quality.dashboard'))->toBe('quality.dashboard')
        ->and(QualityNav::locate(QualityNav::routeKey('quality.n1', 'analisar'), $member)['item'])->toBe('n1-analisar')
        ->and(QualityNav::locate(QualityNav::routeKey('quality.n2', 'decidir'), $member)['item'])->toBe('n2-decidir')
        ->and(QualityNav::locate('quality.process', $member)['item'])->toBe('historico')
        ->and(QualityNav::locate(QualityNav::routeKey('quality.queue', null, 'aguardando-n2'), ['mode' => 'manager'])['item'])->toBe('aguardando-n2')
        ->and(QualityNav::locate(QualityNav::routeKey('quality.queue', null, 'em-andamento'), ['mode' => 'manager'])['item'])->toBe('andamento');
});

it('as abas e filas da Qualidade levam o parâmetro que a rota exige', function () {
    $item = collect(navItems(QualityNav::class, ['mode' => 'manager']))->firstWhere('key', 'aguardando-n2');

    expect(route($item['route'], $item['params']))->toEndWith('/quality/queue?fila=aguardando-n2');

    $tab = collect(navItems(QualityNav::class, ['mode' => 'member', 'n1' => true]))->firstWhere('key', 'n1-no-n2');

    expect(route($tab['route'], $tab['params']))->toEndWith('/quality/n1/no-n2');
});

it('o topo lê dos mesmos menus: relatórios, SMC e ADS apontam para rotas do menu lateral', function () {
    foreach (ReportsNav::topLinks() as $link) {
        expect(Route::has($link['route']))->toBeTrue("rota {$link['route']}")->and($link['can'])->not->toBe('');
    }

    expect(array_column(BtzeroNav::topItems(), 'route'))->toBe(['btzero.main'])
        ->and(array_column(AdsNav::topItems(), 'route'))->toBe(['ads.dashboard'])
        ->and(collect(ReportsNav::topLinks())->pluck('route')->all())->toContain('reports.complaints_mede', 'reports.five_notes', 'reports.return_intern', 'reports.cancellations_dashboard');
});

it('nenhum destes menus tem mais HTML de sidebar escrito à mão', function () {
    $files = array_merge(
        [resource_path('views/ads/menu.blade.php'), resource_path('views/cancellations/menu.blade.php'), resource_path('views/project-review/menu.blade.php')],
        glob(resource_path('views/reports/*menu.blade.php')),
        [resource_path('views/livewire/btzero/menu.blade.php'), resource_path('views/livewire/construction/hiring/menu.blade.php'), resource_path('views/livewire/legal/menu.blade.php'), resource_path('views/livewire/quality/sidebar.blade.php')],
    );

    expect($files)->toHaveCount(13);

    foreach ($files as $file) {
        $html = file_get_contents($file);

        expect(str_contains($html, '<x-nav.sidebar'))->toBeTrue(basename($file) . ' deve usar <x-nav.sidebar>')
            ->and(str_contains($html, 'sidebar-nav'))->toBeFalse(basename($file) . ' ainda tem HTML de sidebar antigo')
            ->and(str_contains($html, 'nav-content'))->toBeFalse(basename($file) . ' ainda tem HTML de sidebar antigo')
            ->and(str_contains($html, 'style='))->toBeFalse(basename($file) . ' tem estilo inline');
    }

    foreach (['reports/menu', 'reports/cancellations-menu', 'reports/complaints-menu', 'reports/five-notes-menu', 'reports/project-review-menu', 'reports/return-intern-menu'] as $view) {
        $this->actingAs(new User(['name' => 'Teste']));

        expect(str_contains(view(str_replace('/', '.', $view))->render(), 'snav-aside'))->toBeTrue("{$view} não renderiza o menu padrão");
    }
});

it('cada variante de relatório é um módulo só, com os relatórios da família', function () {
    $labels = fn (string $variant) => array_values(array_map(fn ($i) => $i['label'], navItems(ReportsNav::class, ['variant' => $variant])));

    expect($labels('cancelamentos'))->toBe(['Dashboard gerencial', 'Lista de cancelamentos'])
        ->and($labels('retorno-interno'))->toBe(['Retornos abertos', 'Indicadores históricos', 'Relatório histórico'])
        ->and($labels('reclamacoes'))->toBe(['Relatório de reclamação'])
        ->and($labels('informes'))->toContain('Obras informadas', 'Informes rejeitados', 'Histórico de informes rejeitados');

    foreach (ReportsNav::VARIANTS as $variant) {
        expect(ReportsNav::modules(['variant' => $variant]))->toHaveCount(1);
    }
});

it('os módulos e grupos devolvem as chaves que o componente do menu exige', function () {
    foreach ([AdsNav::class, CancellationNav::class, ProjectReviewNav::class, BtzeroNav::class, LegalNav::class] as $nav) {
        expect(is_subclass_of($nav, SidebarNav::class))->toBeTrue();
    }

    expect(ProjectReviewNav::modules()['analise']['groups'][0]['items'][0]['counts'])->toBe([['project-review.menu-counts']])
        ->and(collect(navItems(BtzeroNav::class))->firstWhere('key', 'rejeitados')['counts'])->toBe([['btzero.count.return-ramal-count']]);
});

// ---------- Portal da parceira (tema próprio) ----------

function partnerUser(array $attributes = []): User
{
    return (new User(['name' => 'Parceira']))->forceFill($attributes);
}

it('o menu da parceira usa as permissões da parceira e mostra tudo ao superadm', function () {
    $this->actingAs(partnerUser(['superadm' => true]));

    $groups = array_column(PartnerNav::resolve()['portal']['groups'], 'key');

    expect($groups)->toBe(['inicio', 'viabilidade', 'conclusao', 'parcial', 'reclamacoes', 'd5', 'encerramento', 'administracao'])
        ->and(PartnerNav::THEME)->toBe('partner')
        ->and(AdsNav::THEME)->toBeNull();
});

it('engenheiro que só atua pela parceira não informa nem entrega ADS', function () {
    $keys = function (array $attributes) {
        $this->actingAs(partnerUser($attributes + ['superadm' => true]));

        return collect(navItems(PartnerNav::class))->pluck('key')->all();
    };

    expect($keys([]))->toContain('conc-informar', 'conc-ads', 'parc-informar');

    $restricted = $keys(['engineer' => true, 'onlyparner' => true]);

    expect($restricted)->not->toContain('conc-informar', 'conc-ads', 'parc-informar')
        ->and($restricted)->toContain('conc-rejeitados', 'conc-lista', 'parc-lista');
});

it('sem permissão da parceira o menu fica só com a coluna, no tema da parceira', function () {
    $this->actingAs(partnerUser());

    expect(PartnerNav::resolve())->toBe([]);

    $html = Illuminate\Support\Facades\Blade::render('<x-nav.sidebar :nav="\App\Support\Menu\PartnerNav::class" route="partner.report.workedlist" label="Menu da parceira" />');

    expect($html)->toContain('snav snav--partner')->and($html)->not->toContain('edp-bg-sprucegreen-100');

    // O menu da empresa continua com o verde padrão e sem tema.
    $company = Illuminate\Support\Facades\Blade::render('<x-nav.sidebar :nav="\App\Support\Menu\AdsNav::class" route="ads.dashboard" label="Menu de ADS" />');

    expect($company)->toContain('edp-bg-sprucegreen-100')->and($company)->not->toContain('snav--');
});

it('as telas de detalhe da parceira ativam o item da lista a que pertencem', function () {
    $this->actingAs(partnerUser(['superadm' => true]));

    foreach ([
        ['forms.viability', 'viab-todo'],
        ['partner.report.reinformWorkreport', 'conc-rejeitados'],
        ['protests.partner.view', 'recl-aguardando'],
        ['protests.partner.view_only', 'recl-aguardando'],
        ['partner.closure.issue.show', 'enc-pendencias'],
        ['partner.closure.work.show', 'enc-pendencias'],
        ['partner.admin.users.edit', 'adm-usuarios'],
        ['partner.note_d5.returned', 'd5-rejeitadas'],
        ['partner.main.viability', 'principal'],
    ] as [$route, $item]) {
        expect(PartnerNav::locate($route)['item'])->toBe($item, "{$route} deveria ativar {$item}");
    }
});

it('o dropdown BUSCAR do topo da parceira sai da mesma definição, com a permissão de cada item', function () {
    $top = PartnerNav::topItems();

    expect($top)->toHaveCount(1)
        ->and($top[0])->toMatchArray(['route' => 'partner.search.notes', 'can' => 'portal.search_notes'])
        ->and(Route::has($top[0]['route']))->toBeTrue();
});

it('o layout da parceira não tem CSS inline, usa o tema e não tem a marca verde neon', function () {
    $layout = file_get_contents(resource_path('views/layouts/company.blade.php'));

    expect($layout)->toContain('partner-shell')->and($layout)->toContain('partner-brand-product')
        ->and($layout)->not->toContain('<style')->and($layout)->not->toContain('edp-text-green')
        ->and(str_contains($layout, 'edp-bg-gray'))->toBeFalse();

    // O tema só usa cores EDP por variável (ou o bloco de tokens): nada de #hex solto fora dos tokens.
    $scss    = file_get_contents(resource_path('sass/partner/_partner.scss'));
    $outside = preg_replace('/\.partner-shell,\s*\.snav\.snav--partner \{.*?\n\}/s', '', $scss, 1);

    expect(preg_match_all('/#[0-9a-fA-F]{6}\b/', (string) $outside))->toBe(0);
});

it('o menu da parceira é só uma chamada ao componente padrão', function () {
    $html = file_get_contents(resource_path('views/livewire/partner/menu.blade.php'));

    expect($html)->toContain('<x-nav.sidebar')->and($html)->not->toContain('sidebar-nav')->and($html)->not->toContain('nav-content');
});
