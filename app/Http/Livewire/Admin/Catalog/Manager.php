<?php

namespace App\Http\Livewire\Admin\Catalog;

use App\Models\{Service, SystemSetting};
use App\Services\Catalog\CatalogService;
use App\Support\Catalog\{CatalogException, CatalogRegistry};
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Cadastro único de categorias e motivos (docs/admin/CATALOGOS.md). Navegação em colunas: escolhe o catálogo, depois
 * categoria → subcategoria → item. Toda regra mora em `CatalogService`; os catálogos, em `CatalogRegistry`.
 * `$only` trava a tela num catálogo (atalhos de cada atividade, sem o seletor).
 */
class Manager extends Component
{
    public ?string $only = null;

    public string $catalog = '';

    /** @var array<int, int|string|null> id escolhido em cada nível (vai para a URL: s[0]=…&s[1]=…). */
    public array $selected = [];

    public string $search = '';

    /** @var array<int, string> nome digitado no "Adicionar" de cada coluna. */
    public array $add = [];

    /** Edição: `form` guarda os campos do nível (name, description, code, active, flags, números). */
    public ?int $editLevel = null;

    public ?int $editId = null;

    public array $form = [];

    public ?int $bulkLevel = null;

    public string $bulkText = '';

    public ?int $confirmLevel = null;

    public ?int $confirmId = null;

    public string $confirmName = '';

    public ?string $surveyService = null;

    protected $queryString = [
        'catalog'  => ['except' => '', 'as' => 'c'],
        'selected' => ['except' => [], 'as' => 's'],
        'search'   => ['except' => '', 'as' => 'q'],
    ];

    public function mount(?string $only = null): void
    {
        $this->only = $only;

        $allowed = $this->allowed();
        abort_if($allowed === [], 403);

        if ($only) {
            abort_unless(isset($allowed[$only]), 403);
            $this->catalog = $only;
        } elseif (!isset($allowed[$this->catalog])) {
            $this->catalog = (string) array_key_first($allowed);
        }

        $this->surveyService = SystemSetting::getValue('project_review_survey_service_id');
    }

    /** @return array<string, array<string, mixed>> */
    private function allowed(): array
    {
        return CatalogRegistry::allowedFor(auth()->user());
    }

    /** Toda ação revalida o acesso: o estado do componente vem do navegador. */
    private function guard(): array
    {
        $definition = $this->allowed()[$this->only ?: $this->catalog] ?? null;
        abort_if($definition === null, 403);

        return $definition;
    }

    private function service(): CatalogService
    {
        return app(CatalogService::class);
    }

    private function parentId(int $level): ?int
    {
        return $level > 0 ? (int) ($this->selected[$level - 1] ?? 0) ?: null : null;
    }

    public function pickCatalog(string $key): void
    {
        if ($this->only || !isset($this->allowed()[$key])) {
            return;
        }

        $this->catalog  = $key;
        $this->selected = [];
        $this->add      = [];
        $this->search   = '';
    }

    public function select(int $level, int $id): void
    {
        $this->guard();
        $this->selected[$level] = $id;

        // Trocar o pai zera tudo que está abaixo (a coluna seguinte volta a escolher o primeiro).
        $this->selected = array_slice($this->selected, 0, $level + 1, true);
    }

    public function addItem(int $level): void
    {
        $this->guard();

        $this->run(function () use ($level) {
            $this->service()->create($this->catalog, $level, $this->parentId($level), ['name' => $this->add[$level] ?? '']);
            $this->add[$level] = '';

            return 'Adicionado.';
        });
    }

    public function openBulk(int $level): void
    {
        $this->guard();
        $this->bulkLevel = $level;
        $this->bulkText  = '';
    }

    public function saveBulk(): void
    {
        $this->guard();

        $this->run(function () {
            $result = $this->service()->createMany($this->catalog, (int) $this->bulkLevel, $this->parentId((int) $this->bulkLevel), $this->bulkText);

            $this->dispatchBrowserEvent('cat-modal-close');
            $this->bulkText = '';

            return $result['created'] . ' adicionado(s)' . ($result['skipped'] ? ', ' . $result['skipped'] . ' já existiam' : '') . '.';
        });
    }

    public function edit(int $level, int $id): void
    {
        $this->guard();

        $config = $this->service()->level($this->catalog, $level);
        $model  = $config['model']::query()->find($id);

        if (!$model) {
            $this->notify('error', 'Registro não encontrado: atualize a página.');

            return;
        }

        $this->editLevel = $level;
        $this->editId    = $id;
        $this->form      = ['name' => (string) $model->name];

        foreach (['description', 'code'] as $field) {
            if (!empty($config[$field])) {
                $this->form[$field] = (string) $model->{$field};
            }
        }

        if (!empty($config['active'])) {
            $this->form['active'] = (bool) $model->active;
        }

        foreach (array_keys($config['flags'] ?? []) as $flag) {
            $this->form[$flag] = (bool) $model->{$flag};
        }

        foreach (array_keys($config['numbers'] ?? []) as $field) {
            $this->form[$field] = (int) $model->{$field};
        }
    }

    public function saveEdit(): void
    {
        $this->guard();

        $this->run(function () {
            $this->service()->update($this->catalog, (int) $this->editLevel, (int) $this->editId, $this->form);
            $this->dispatchBrowserEvent('cat-modal-close');

            return 'Salvo.';
        });
    }

    public function toggleActive(int $level, int $id): void
    {
        $this->guard();
        $this->run(fn () => $this->service()->toggleActive($this->catalog, $level, $id)->active ? 'Ativado.' : 'Desativado.');
    }

    public function toggleFlag(int $level, int $id, string $flag): void
    {
        $this->guard();
        $this->run(function () use ($level, $id, $flag) {
            $this->service()->toggleFlag($this->catalog, $level, $id, $flag);

            return 'Atualizado.';
        });
    }

    public function move(int $level, int $id, string $direction): void
    {
        $this->guard();
        $this->run(function () use ($level, $id, $direction) {
            $this->service()->move($this->catalog, $level, $id, $direction === 'up' ? 'up' : 'down');

            return null;
        });
    }

    public function confirmDelete(int $level, int $id): void
    {
        $this->guard();

        $config = $this->service()->level($this->catalog, $level);

        $this->confirmLevel = $level;
        $this->confirmId    = $id;
        $this->confirmName  = (string) ($config['model']::query()->whereKey($id)->value('name') ?? '');
    }

    public function deleteConfirmed(): void
    {
        $this->guard();

        $this->run(function () {
            $this->service()->delete($this->catalog, (int) $this->confirmLevel, (int) $this->confirmId);
            $this->dispatchBrowserEvent('cat-modal-close');

            return 'Excluído.';
        });
    }

    public function updatedSurveyService($value): void
    {
        abort_unless($this->guard()['settings'] ?? null, 403);

        $value = $value ?: null;

        if ($value !== null && !Service::query()->where('uuid', $value)->exists()) {
            $value               = null;
            $this->surveyService = null;
        }

        SystemSetting::setValue('project_review_survey_service_id', $value);
        $this->notify('success', 'Configuração salva.');
    }

    /** Executa uma ação do serviço: devolve o texto de sucesso em toast, ou o erro de regra no mesmo lugar. */
    private function run(\Closure $action): void
    {
        try {
            if ($message = $action()) {
                $this->notify('success', $message);
            }
        } catch (CatalogException $exception) {
            $this->notify('warning', $exception->getMessage());
        }
    }

    private function notify(string $icon, string $title): void
    {
        $this->dispatchBrowserEvent('swal', [
            'toast'             => true,
            'position'          => 'top-end',
            'icon'              => $icon,
            'title'             => $title,
            'showConfirmButton' => false,
            'timer'             => $icon === 'success' ? 2000 : 4500,
            'timerProgressBar'  => true,
        ]);
    }

    /** Colunas visíveis: a 1ª sempre; cada seguinte só com o item de cima escolhido (e o 1º é escolhido sozinho). */
    private function columns(array $definition): array
    {
        $columns = [];
        $term    = mb_strtolower(trim($this->search));

        foreach ($definition['levels'] as $level => $config) {
            $parent = $this->parentId($level);

            if ($level > 0 && !$parent) {
                break;
            }

            $rows = $this->service()->rows($this->catalog, $level, $parent);

            // Valor vindo da URL/navegador que não existe mais: cai no primeiro da lista.
            $current = (int) ($this->selected[$level] ?? 0);

            if (isset($definition['levels'][$level + 1]) && !$rows->contains(fn ($row) => (int) $row->getKey() === $current)) {
                $this->selected[$level] = $rows->first()?->getKey();
            }

            $total = $rows->count();

            if ($term !== '') {
                $rows = $rows->filter(fn ($row) => str_contains(mb_strtolower($row->name . ' ' . ($row->code ?? '')), $term))->values();
            }

            $columns[] = ['level' => $level, 'config' => $config, 'rows' => $rows, 'total' => $total, 'parent' => $parent];
        }

        return $columns;
    }

    public function render()
    {
        $allowed    = $this->allowed();
        $definition = $allowed[$this->catalog] ?? $allowed[array_key_first($allowed)];

        $groups = [];

        foreach (CatalogRegistry::GROUPS as $key => $group) {
            $items = collect($allowed)->filter(fn ($catalog) => $catalog['group'] === $key);

            if ($items->isNotEmpty()) {
                $groups[$key] = $group + ['catalogs' => $items->map(fn ($catalog, $catalogKey) => $catalog + ['key' => $catalogKey, 'count' => $this->service()->topCount($catalogKey)])];
            }
        }

        return view('livewire.admin.catalog.manager', [
            'definition' => $definition,
            'columns'    => $this->columns($definition),
            'groups'     => $this->only ? [] : $groups,
            'editConfig' => $this->editLevel !== null ? $definition['levels'][$this->editLevel] ?? null : null,
            'services'   => ($definition['settings'] ?? null) === 'survey_service' ? Service::query()->orderBy('service')->get(['uuid', 'service']) : new Collection(),
        ]);
    }
}
