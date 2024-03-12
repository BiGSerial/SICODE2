<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('superadm', function (User $user) {
            return ($user->superadm)
                ? Response::allow()
                : Response::deny('Você precisa ser Super Administrador para acessar');
        });

        Gate::define('admin', function (User $user) {
            return ($user->admin || $user->superadm)
                ? Response::allow()
                : Response::deny('Você precisa ser Administrador para acessar');
        });

        Gate::define('management', function (User $user) {
            return ($user->management || $user->superadm)
                ? Response::allow()
                : Response::deny('Você precisa ser Gerente para acessar');
        });

        Gate::define('engineer', function (User $user) {
            return ($user->engineer || $user->superadm)
                ? Response::allow()
                : Response::deny('Você precisa ser Engenheiro para acessar');
        });

        Gate::define('operator', function (User $user) {
            return ($user->operator || $user->superadm)
                ? Response::allow()
                : Response::deny('Você precisa ser Operador para acessar');
        });

        Gate::define('user', function (User $user) {
            return ($user->user || $user->superadm)
                ? Response::allow()
                : Response::deny('Você precisa ser Usuário para acessar');
        });
    }
}
