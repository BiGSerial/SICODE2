<?php

namespace App\Http\Livewire\Partner\Admin;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Company};
use App\Services\PartnerAccess\{PartnerAccessGate, PartnerUserManager};
use Livewire\{Component, WithPagination};

/**
 * Usuários da empresa terceira, para o administrador dela (lista de controle: docs/PADRAO_LISTAS_OPERACIONAIS.md §10).
 * Consulta, escopo e regras de proteção vêm do PartnerUserManager; aqui só estado de tela (busca, filtros, seleção, confirmação).
 */
class Users extends Component
{
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Ações que mudam o acesso e, por isso, pedem confirmação antes de executar. */
    private const ACTIONS = [
        'reset_password' => ['label' => 'Redefinir senha', 'permission' => 'admin_users.update', 'tab' => 'active'],
        'make_admin'     => ['label' => 'Tornar administrador', 'permission' => 'admin_users.update', 'tab' => 'active'],
        'remove_admin'   => ['label' => 'Remover administrador', 'permission' => 'admin_users.update', 'tab' => 'active'],
        'move_company'   => ['label' => 'Transferir de empresa', 'permission' => 'admin_users.update', 'tab' => 'active'],
        'disable'        => ['label' => 'Desativar acesso', 'permission' => 'admin_users.disable', 'tab' => 'active'],
        'restore'        => ['label' => 'Reativar acesso', 'permission' => 'admin_users.disable', 'tab' => 'disabled'],
    ];

    public string $search = '';

    /** active | disabled */
    public string $tab = 'active';

    public int $perPage = 25;

    /** @var array<int, string> */
    public array $selected = [];

    /** Confirmação pendente: ['action' => ..., 'ids' => [...]] ou null. */
    public ?array $confirming = null;

    public string $targetCompany = '';

    protected $queryString = [
        'search'  => ['except' => ''],
        'tab'     => ['except' => 'active'],
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    private ?PartnerUserManager $managerMemo = null;

    private function manager(): PartnerUserManager
    {
        return $this->managerMemo ??= new PartnerUserManager(auth()->user());
    }

    public function mount(): void
    {
        abort_unless(PartnerAccessGate::allows(auth()->user(), 'admin_users.view'), 403);

        $this->tab     = $this->tab === 'disabled' ? 'disabled' : 'active';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return [
            'company'  => ['label' => 'Empresa'],
            'role'     => ['label' => 'Perfil'],
            'presence' => ['label' => 'Conexão'],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'company' => $this->manager()->companies()
                ->map(fn (Company $c) => ['v' => (string) $c->id, 'l' => $c->name . ($c->parent_id ? ' (filial)' : '')])
                ->all(),
            'role' => [
                ['v' => 'admin', 'l' => 'Administradores'],
                ['v' => 'user', 'l' => 'Usuários'],
            ],
            'presence' => [
                ['v' => 'online', 'l' => 'Online agora'],
                ['v' => 'offline', 'l' => 'Offline'],
                ['v' => 'never', 'l' => 'Nunca acessaram'],
                ['v' => 'pending', 'l' => 'Primeiro acesso pendente'],
            ],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . $this->manager()->managedCompanyId();
    }

    protected function listFilterCacheTtl(): int
    {
        return 60;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [25, 50, 100], true) ? $this->perPage : 25;
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab      = $tab === 'disabled' ? 'disabled' : 'active';
        $this->selected = [];
        $this->resetPage();
    }

    /** Cartões-resumo clicam como filtro rápido (um perfil/conexão por vez). */
    public function quickFilter(string $key, string $value): void
    {
        $current = $this->filterValues($key);

        $this->tab = 'active';
        $this->applyListFilters(array_merge($this->filters, [$key => $current === [$value] ? [] : [$value]]));
    }

    public function clearAllCriteria(): void
    {
        $this->search = '';
        $this->clearListFilters();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    private function query(string $tab)
    {
        $presence = $this->filterValues('presence');
        $role     = $this->filterValues('role');
        $company  = $this->filterValues('company');
        $online   = now()->subMinutes(PartnerUserManager::ONLINE_MINUTES);

        return $this->manager()->scope()
            ->when($tab === 'disabled', fn ($q) => $q->whereNotNull('deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->when($company, fn ($q) => $q->whereIn('company_id', $company))
            ->when($role && count($role) === 1, fn ($q) => $q->where('admin', $role[0] === 'admin'))
            ->when($presence, fn ($q) => $q->where(function ($w) use ($presence, $online) {
                foreach ($presence as $p) {
                    match ($p) {
                        'online'  => $w->orWhere('last_seen_at', '>=', $online),
                        'offline' => $w->orWhere(fn ($x) => $x->whereNotNull('last_login_at')->where(fn ($y) => $y->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $online))),
                        'never'   => $w->orWhereNull('last_login_at'),
                        'pending' => $w->orWhere('first_pass', true),
                        default   => null,
                    };
                }
            }))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
    }

    /** Contagens do cabeçalho em uma só consulta agrupada (ignoram busca e filtros: são o retrato da empresa). */
    private function counts(): array
    {
        $online = now()->subMinutes(PartnerUserManager::ONLINE_MINUTES);

        $row = $this->manager()->scope()
            ->selectRaw('SUM(deleted_at IS NULL) as active')
            ->selectRaw('SUM(deleted_at IS NOT NULL) as disabled')
            ->selectRaw('SUM(deleted_at IS NULL AND last_seen_at >= ?) as online', [$online])
            ->selectRaw('SUM(deleted_at IS NULL AND last_login_at IS NULL) as never')
            ->selectRaw('SUM(deleted_at IS NULL AND admin = 1) as admins')
            ->first();

        return [
            'active'   => (int) $row->active,
            'disabled' => (int) $row->disabled,
            'online'   => (int) $row->online,
            'never'    => (int) $row->never,
            'admins'   => (int) $row->admins,
        ];
    }

    // ---- ações ----------------------------------------------------------------------------------------------------

    public function can(string $permission): bool
    {
        return PartnerAccessGate::allows(auth()->user(), $permission);
    }

    /** Abre a confirmação de uma ação para um usuário (menu da linha). */
    public function ask(string $action, string $userId): void
    {
        $this->openConfirmation($action, [$userId]);
    }

    /** Abre a confirmação de uma ação para os usuários marcados (barra de seleção). */
    public function askSelected(string $action): void
    {
        $this->openConfirmation($action, array_values(array_map('strval', $this->selected)));
    }

    private function openConfirmation(string $action, array $ids): void
    {
        $meta = self::ACTIONS[$action] ?? null;

        abort_unless($meta && $this->can($meta['permission']), 403);

        if ($ids === []) {
            return;
        }

        $this->targetCompany = '';
        $this->confirming    = ['action' => $action, 'ids' => $ids];
    }

    public function cancelConfirmation(): void
    {
        $this->confirming = null;
    }

    public function confirm(): void
    {
        if (!$this->confirming) {
            return;
        }

        ['action' => $action, 'ids' => $ids] = $this->confirming;

        try {
            $count = $this->manager()->bulk($action, $ids, $action === 'move_company' ? $this->targetCompany : null);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->confirming = null;
            $this->notify('error', $e->getMessage() ?: 'Não foi possível concluir a ação.');

            return;
        }

        $this->confirming = null;
        $this->selected   = [];
        $this->notify('success', $this->successMessage($action, $count));
    }

    private function successMessage(string $action, int $count): string
    {
        $who = $count === 1 ? '1 usuário' : "{$count} usuários";

        return match ($action) {
            'reset_password' => "Senha redefinida para {$who}. No próximo acesso será pedida uma nova senha.",
            'make_admin'     => "{$who} agora " . ($count === 1 ? 'é administrador' : 'são administradores') . '.',
            'remove_admin'   => "Administração removida de {$who}.",
            'move_company'   => "{$who} transferido(s) de empresa.",
            'disable'        => "Acesso desativado para {$who}.",
            'restore'        => "Acesso reativado para {$who}.",
            default          => 'Alteração concluída.',
        };
    }

    private function notify(string $icon, string $title): void
    {
        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => $icon, 'title' => $title, 'timer' => $icon === 'error' ? 5000 : 2500]);
    }

    public function render()
    {
        $managed = Company::withTrashed()->find($this->manager()->managedCompanyId());

        $users = $this->query($this->tab)
            ->with([
                'Company:id,parent_id,name',
                'partnerBranchAddresses' => fn ($q) => $q->wherePivot('company_id', $managed?->id)->with('Company:id,parent_id,name'),
            ])
            ->orderBy('name')
            ->paginate($this->perPage);

        $confirmUsers = $this->confirming
            ? $this->manager()->scope()->whereIn('id', $this->confirming['ids'])->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('livewire.partner.admin.users', [
            'users'        => $users,
            'counts'       => $this->counts(),
            'managed'      => $managed,
            'companies'    => $this->manager()->companies(),
            'actions'      => self::ACTIONS,
            'confirmUsers' => $confirmUsers,
            'hasCriteria'  => $this->hasListFilters() || trim($this->search) !== '',
            'meId'         => (string) auth()->id(),
            'onlineSince'  => now()->subMinutes(PartnerUserManager::ONLINE_MINUTES),
        ]);
    }
}
