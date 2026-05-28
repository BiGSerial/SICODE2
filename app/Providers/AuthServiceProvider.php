<?php

namespace App\Providers;

use App\Models\User;
use App\Models\CancellationRequest;
use App\Models\CancellationCategory;
use App\Policies\CancellationRequestPolicy;
use App\Policies\CancellationCategoryPolicy;
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
        CancellationRequest::class => CancellationRequestPolicy::class,
        CancellationCategory::class => CancellationCategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $roles = [
            'superadm' => 'Você precisa ser Super Administrador para acessar',
            'admin' => 'Você precisa ser Administrador para acessar',
            'management' => 'Você precisa ser Gerente para acessar',
            'engineer' => 'Você precisa ser Engenheiro para acessar',
            'operator' => 'Você precisa ser Operador para acessar',
            'user' => 'Você precisa ser Usuário para acessar',
            'responsible' => 'Você precisa ser Usuário Responsável para acessar',
            'btzero' => 'Você precisa ser Usuario Btzero para acessar',
            'can_dispatch' => 'Você precisa ser Usuário com permissão de despacho para acessar',
            'analyst' => 'Você precisa ser Analista de Projeto para acessar',
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
    }
}
