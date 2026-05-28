<?php

namespace App\Providers;

use App\Models\{CancellationCategory, CancellationRequest, User};
use App\Policies\{CancellationCategoryPolicy, CancellationRequestPolicy};
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CancellationRequest::class  => CancellationRequestPolicy::class,
        CancellationCategory::class => CancellationCategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $roles = [
            'superadm'     => 'Você precisa ser Super Administrador para acessar',
            'admin'        => 'Você precisa ser Administrador para acessar',
            'management'   => 'Você precisa ser Gerente para acessar',
            'engineer'     => 'Você precisa ser Engenheiro para acessar',
            'operator'     => 'Você precisa ser Operador para acessar',
            'user'         => 'Você precisa ser Usuário para acessar',
            'responsible'  => 'Você precisa ser Usuário Responsável para acessar',
            'btzero'       => 'Você precisa ser Usuario Btzero para acessar',
            'can_dispatch' => 'Você precisa ser Usuário com permissão de despacho para acessar',
            'analyst'      => 'Você precisa ser Analista de Projeto para acessar',
        ];

        foreach ($roles as $role => $message) {
            Gate::define($role, function (User $user) use ($role, $message) {
                return ($user->$role || $user->superadm)
                    ? Response::allow()
                    : Response::deny($message);
            });
        }

        Gate::define('viewLogViewer', function (?User $user) {
            return $user && $user->superadm
                ? Response::allow()
                : Response::deny('Você precisa ser Administrador ou Super Administrador para acessar o Log Viewer');
        });

        Gate::define('projectReviewReports', function (User $user) {
            return ($user->superadm || $user->admin || $user->management || $user->contract)
                ? Response::allow()
                : Response::deny('Você não possui permissão para acessar os relatórios de Análise de Projeto.');
        });

        // Módulo Jurídico — mapeamento coerente com flags existentes em users:
        // legal_controller, legal_field, legal_manager (+ admin/superadm).
        $isController = fn (User $u) => $u->legal_controller || $u->superadm || $u->admin;
        $isField = fn (User $u) => $u->legal_field || $u->superadm || $u->admin;
        $isManager = fn (User $u) => $u->legal_manager || $u->superadm || $u->admin;

        // Controladoria
        Gate::define('legal.demands.triage', $isController);
        Gate::define('legal.demands.assign', $isController);
        Gate::define('legal.demands.review', $isController);
        Gate::define('legal.demands.close_internal', $isController);
        Gate::define('legal.demands.close_external', $isController);
        Gate::define('legal.demands.reopen', $isController);

        // Executante
        Gate::define('legal.demands.answer', $isField);

        // Leitura e arquivos (controller/field)
        Gate::define('legal.demands.view', fn (User $u) => $isController($u) || $isField($u) || $isManager($u));
        Gate::define('legal.demands.manage_files', fn (User $u) => $isController($u) || $isField($u));
        Gate::define('legal.demands.view_controller_files', fn (User $u) => $isController($u) || $isManager($u));

        // Gestão / relatórios
        Gate::define('legal.manager', $isManager);
        Gate::define('legal.reports', $isManager);
    }
}
