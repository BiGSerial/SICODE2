<?php

namespace App\Http\Livewire\Quality;

use App\Services\Quality\{QualityBoard, QualityRoles};
use Livewire\Component;

/**
 * Menu lateral da Qualidade com badges de contagem que se atualizam sozinhos (polling).
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
        $roles = app(QualityRoles::class);
        $user  = auth()->user();
        $board = app(QualityBoard::class);

        $isManager = $roles->canManage($user);
        $showN1    = !$isManager && $roles->isN1($user);
        $showN2    = !$isManager && $roles->isN2($user);

        return view('livewire.quality.sidebar', [
            'roles'     => $roles,
            'user'      => $user,
            'isManager' => $isManager,
            'showN1'    => $showN1,
            'showN2'    => $showN2,
            'n1Tabs'    => $showN1 ? $board->tabs($user, QualityBoard::N1_TABS) : [],
            'n2Tabs'    => $showN2 ? $board->tabs($user, QualityBoard::N2_TABS) : [],
            'mgmt'      => $isManager ? $board->managementCounts($user) : [],
        ]);
    }
}
