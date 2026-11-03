<?php

namespace App\Http\Livewire\Quality;

use App\Services\Quality\QualityRoles;
use App\Support\Menu\QualityNav;
use Livewire\Component;

/**
 * Menu lateral da Qualidade: monta o contexto do papel de quem olha e entrega ao `<x-nav.sidebar>`; a estrutura vive em
 * App\Support\Menu\QualityNav e as contagens em `quality.menu-count` (padrão em docs/PADRAO_MENU_LATERAL.md).
 * A rota ativa chega por props porque, nas atualizações do Livewire, `request()` já não é a da página.
 */
class Sidebar extends Component
{
    public string $route = '';

    public ?string $tab = null;

    public ?string $fila = null;

    public function mount(string $route = '', ?string $tab = null, ?string $fila = null): void
    {
        $this->route = $route;
        $this->tab   = $tab;
        $this->fila  = $fila;
    }

    public function render()
    {
        $roles     = app(QualityRoles::class);
        $user      = auth()->user();
        $isManager = $roles->canManage($user);

        return view('livewire.quality.sidebar', [
            'roles'     => $roles,
            'user'      => $user,
            'isManager' => $isManager,
            'routeKey'  => QualityNav::routeKey($this->route, $this->tab, $this->fila),
            'context'   => $isManager
                ? ['mode' => 'manager']
                : ['mode' => 'member', 'n1' => $roles->isN1($user), 'n2' => $roles->isN2($user)],
        ]);
    }
}
