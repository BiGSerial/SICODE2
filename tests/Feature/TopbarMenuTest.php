<?php

use App\Models\{Production, Service, ServiceUser, User};
use App\Services\Menu\TopbarCounts;
use App\Support\Menu\TopbarNav;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Menu superior: docs/MenuSuperior.md

uses(RefreshDatabase::class);

function topbarUser(array $roles = []): User
{
    $flags = array_fill_keys(['superadm', 'admin', 'management', 'operator', 'user', 'engineer', 'responsible', 'contract', 'can_dispatch', 'analyst'], false);

    return User::factory()->create($roles + $flags);
}

function topbarService(string $name, string $folder, array $phase): Service
{
    return Service::create(['service' => $name, 'folder' => $folder] + $phase);
}

it('segue a ordem do fluxo e mostra só o que o papel pode ver', function () {
    $admin = topbarUser(['superadm' => true, 'admin' => true, 'management' => true, 'operator' => true, 'user' => true, 'engineer' => true, 'responsible' => true]);

    $titles = array_column(TopbarNav::menus($admin), 'title');

    // ATIVIDADES some quando o usuário não tem nenhuma atividade; os demais mantêm a ordem do fluxo.
    expect(array_values(array_intersect(['ATIVIDADES', 'OCORRÊNCIAS', 'ENGENHARIA', 'RESPONSÁVEL', 'JURÍDICO', 'CONSULTAS', 'RELATÓRIOS', 'ADMINISTRAÇÃO'], $titles)))->toBe($titles)
        ->and($titles)->toContain('OCORRÊNCIAS')->toContain('ADMINISTRAÇÃO');
});

it('o usuário comum não vê Engenharia, Responsável, Relatórios nem Administração', function () {
    $this->actingAs(topbarUser());

    preg_match_all('/<a class="nav-link dropdown-toggle[^>]*>\s*([^<]+)</', view('layouts.menu_itens')->render(), $m);
    $titles = array_map('trim', $m[1]);

    expect($titles)->toContain('OCORRÊNCIAS')->toContain('CONSULTAS')
        ->not->toContain('ENGENHARIA')->not->toContain('RESPONSÁVEL')->not->toContain('RELATÓRIOS')->not->toContain('ADMINISTRAÇÃO');
});

it('Atividades: uma linha por atividade, na fase certa, com fila e ações de despacho', function () {
    $user = topbarUser(['operator' => true, 'user' => true]);
    $a    = topbarService('Levantamento', 'levantamento', ['construction' => true]);
    $b    = topbarService('Comissionamento', 'comissionamento', ['construction' => true]);
    $c    = topbarService('Cadastro', 'cadastro', ['project' => true]);

    ServiceUser::create(['user_id' => $user->id, 'service_id' => $a->uuid, 'service' => true, 'dispatch' => true]);
    ServiceUser::create(['user_id' => $user->id, 'service_id' => $b->uuid, 'service' => true, 'dispatch' => false]);
    ServiceUser::create(['user_id' => $user->id, 'service_id' => $c->uuid, 'service' => false, 'dispatch' => true]);

    $menu   = collect(TopbarNav::menus($user))->firstWhere('title', 'ATIVIDADES');
    $groups = collect($menu['nodes'])->keyBy('label');

    expect($groups->keys()->all())->toBe(['PROJETO', 'CONSTRUÇÃO']);

    $construction = collect($groups['CONSTRUÇÃO']['nodes']);

    // Ordem do fluxo: Levantamento antes de Comissionamento.
    expect($construction->pluck('label')->all())->toBe(['LEVANTAMENTO', 'COMISSIONAMENTO']);

    $levantamento = $construction->firstWhere('label', 'LEVANTAMENTO');
    expect($levantamento['count'])->toBe('service:' . $a->uuid)
        ->and(array_column($levantamento['actions'], 'label'))->toBe(['Despacho', 'Controle', 'Dashboard'])
        ->and($construction->firstWhere('label', 'COMISSIONAMENTO')['actions'])->toBe([]);

    // Quem só despacha: o nome leva ao despacho, sem contador.
    $cadastro = collect($groups['PROJETO']['nodes'])->firstWhere('label', 'CADASTRO');
    expect($cadastro['route'])->toBe('dispatch.main')->and($cadastro['count'])->toBeNull();
});

it('renderiza o menu inteiro com rotas válidas, sem CSS/JS inline e com um só componente Livewire', function () {
    $user = topbarUser(['superadm' => true, 'admin' => true, 'management' => true, 'operator' => true, 'user' => true, 'engineer' => true, 'responsible' => true]);
    $svc  = topbarService('Pagamento', 'pagamento', ['construction' => true]);

    ServiceUser::create(['user_id' => $user->id, 'service_id' => $svc->uuid, 'service' => true, 'dispatch' => true]);
    $this->actingAs($user);

    $html = view('layouts.menu_itens')->render();

    expect($html)->toContain('class="dropdown-menu dropdown-menu-end tb-menu"')
        ->toContain('data-tb-count="service:' . $svc->uuid . '"')
        ->not->toContain('<style')->not->toContain('<script')
        ->and(substr_count($html, 'wire:id'))->toBe(1);
});

it('os contadores vêm de uma consulta só, por atividade', function () {
    $user = topbarUser(['operator' => true, 'user' => true]);
    $a    = topbarService('Levantamento', 'levantamento', ['construction' => true]);
    $b    = topbarService('Desenho', 'desenho', ['project' => true]);
    $co   = \App\Models\Company::create(['name' => 'Empresa', 'email' => 'e@example.com']);
    $note = fn (string $n) => \App\Models\Note::create(['note' => $n])->id;

    $mk = fn ($service, $n, $extra = []) => Production::create($extra + ['note_id' => $note($n), 'service_id' => $service->uuid, 'company_id' => $co->id, 'user_id' => $user->id, 'completed' => false, 'status' => 2]);
    $mk($a, '4001');
    $mk($a, '4002');
    $mk($a, '4003', ['completed' => true]);
    $mk($b, '4004');
    $mk($b, '4005', ['status' => 3]);

    $counts = app(TopbarCounts::class)->for($user);

    expect($counts['service:' . $a->uuid])->toBe(2)
        ->and($counts['service:' . $b->uuid])->toBe(1)
        ->and($counts['protests'])->toBe(0);
});

it('Qualidade só aparece no menu para o superadm e para N1/N2; Análise de Projeto, só para quem tem a flag de analista', function () {
    $company = \App\Models\Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
    $member  = fn (string $role) => tap(topbarUser(), fn (User $u) => \App\Models\QualityMember::create(['user_id' => $u->id, 'company_id' => $company->id, 'role' => $role, 'active' => true]));

    $super    = topbarUser(['superadm' => true]);
    $manager  = topbarUser(['management' => true]);
    $admin    = topbarUser(['admin' => true]);
    $plain    = topbarUser(['user' => true]);
    $analyst  = topbarUser(['analyst' => true]);
    $inactive = tap(topbarUser(), fn (User $u) => \App\Models\QualityMember::create(['user_id' => $u->id, 'company_id' => $company->id, 'role' => \App\Models\QualityMember::N1, 'active' => false]));

    expect($super->can('quality.menu'))->toBeTrue()
        ->and($member(\App\Models\QualityMember::N1)->can('quality.menu'))->toBeTrue()
        ->and($member(\App\Models\QualityMember::N2)->can('quality.menu'))->toBeTrue()
        ->and($manager->can('quality.menu'))->toBeFalse()   // gerência e admin acessam o módulo, mas sem o atalho no menu
        ->and($admin->can('quality.menu'))->toBeFalse()
        ->and($plain->can('quality.menu'))->toBeFalse()
        ->and($inactive->can('quality.menu'))->toBeFalse();

    expect($super->can('analyst'))->toBeTrue()
        ->and($analyst->can('analyst'))->toBeTrue()
        ->and($manager->can('analyst'))->toBeFalse()
        ->and($admin->can('analyst'))->toBeFalse()
        ->and($plain->can('analyst'))->toBeFalse();

    // O menu amarra cada atalho ao seu gate.
    $svc = topbarService('Cadastro', 'cadastro', ['project' => true]);
    ServiceUser::create(['user_id' => $plain->id, 'service_id' => $svc->uuid, 'service' => true, 'dispatch' => false]);

    $project = collect(collect(TopbarNav::menus($super))->firstWhere('title', 'ATIVIDADES')['nodes'] ?? [])->firstWhere('label', 'PROJETO');
    $nodes   = collect($project['nodes'] ?? [])->keyBy('label');

    expect($nodes['QUALIDADE']['can'] ?? null)->toBe('quality.menu')
        ->and($nodes['ANÁLISE DE PROJETO']['can'] ?? null)->toBe('analyst');
});
