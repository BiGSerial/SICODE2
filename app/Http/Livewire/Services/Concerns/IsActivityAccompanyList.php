<?php

namespace App\Http\Livewire\Services\Concerns;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Note, Production, Service};
use App\Services\Accompany\{ActivityAccompanyProfile, NoteAccompanyRowPresenter};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{Cache, DB, Gate};

/**
 * Base do ACOMPANHAMENTO de Serviços "por nota" (Análises, Pré-análise, Cadastro, Incorporação, Reverso): a fila de trabalho do usuário,
 * isto é, as Notas/OVs que ele assumiu e ainda não concluiu. Padrão da tela: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 *
 * O componente (que também usa `WithPagination`) só decide qual atividade é; tudo que muda por atividade vem do
 * `ActivityAccompanyProfile` (formulário, limite de pausa, encerramento em massa, origem do prazo, colunas). Aqui ficam busca, filtros
 * (na URL), a visão administrativa do superadm, as abas Produção/Transferências e as ações de iniciar e transferir.
 */
trait IsActivityAccompanyList
{
    use BuildsLegalNoteTags;
    use ClosesAccompanyInBulk;
    use WithListFilters;

    public const PAGE_SIZES = [25, 50, 100, 250];

    public const TABS = ['producao', 'transferencias'];

    public $service;

    public $perPage = 50;

    public $search = '';

    public $tab = 'producao';

    /** Visão administrativa: o superadm vê a fila de outro usuário. */
    public $user_s = '';

    public $advanceSearch;

    /** @var array<int, string> notas ou pedidos da busca em lote */
    public $multiSearch = [];

    /** @var array<int, int|string> produções marcadas (caixas `wire:model.defer`) */
    public $selected = [];

    public $analise;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'filters' => ['except' => [], 'as' => 'f'],
        'perPage' => ['except' => 50, 'as' => 'pp'],
        'tab'     => ['except' => 'producao', 'as' => 'aba'],
        'user_s'  => ['except' => '', 'as' => 'usuario'],
    ];

    protected $listeners = [
        'refresh_accomany'    => '$refresh',
        'refresh_service'     => '$refresh',
        'getCopy'             => 'copy',
        'confirm_getAnalise'  => 'go_to_analise',
        'confirm_finish_mass' => 'finishBulk',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();

        $this->perPage = in_array((int) $this->perPage, self::PAGE_SIZES, true) ? (int) $this->perPage : 50;
        $this->tab     = in_array($this->tab, self::TABS, true) ? $this->tab : 'producao';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    /** @return array<string, mixed> */
    protected function profile(): array
    {
        return ActivityAccompanyProfile::for($this->service?->folder);
    }

    /** De quem é a fila: do próprio usuário, ou do usuário escolhido pelo superadm. */
    protected function targetUserId(): string
    {
        return $this->user_s && Gate::allows('superadm') ? (string) $this->user_s : (string) auth()->id();
    }

    // ---------- Estado da tela ----------

    public function updated($name): void
    {
        // As caixas marcadas chegam como atualização de `selected` antes da ação em lote: não pode zerar.
        if ($name === 'page' || str_starts_with((string) $name, 'selected') || str_starts_with((string) $name, 'bulk')) {
            return;
        }

        $this->resetPage();
        $this->selected = [];
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'producao';
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** "Limpar tudo": filtros, busca (simples e em lote) e visão administrativa. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search        = '';
        $this->advanceSearch = '';
        $this->multiSearch   = [];
        $this->user_s        = '';
        $this->filters       = [];

        $this->afterListFiltersChanged();
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || filled($this->search) || $this->multiSearch !== [] || filled($this->user_s);
    }

    public function buscarMulti(): void
    {
        $this->search = '';
        // Separa por linha, espaço, vírgula ou ponto e vírgula (a mesma regra que o Reverso sempre teve).
        $this->multiSearch = collect(preg_split('/[\s,;\r\n\t]+/', (string) $this->advanceSearch))
            ->map(fn ($term) => trim((string) $term))->filter()->unique()->take(300)->values()->all();

        $this->advanceSearch = '';
        $this->selected      = [];
        $this->resetPage();
        $this->dispatchBrowserEvent('hideModal');
    }

    public function clearMultiSearch(): void
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', ['status' => 'success', 'menssage' => $msg]);
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica' => ['label' => 'Rubrica'],
            'city'    => ['label' => 'Município'],
        ];
    }

    protected function listFilterOptions(): array
    {
        $noteIds = fn () => Production::query()
            ->where('service_id', $this->service->uuid)->where('user_id', $this->targetUserId())->where('completed', false)
            ->select('note_id');

        $distinct = fn (string $column) => $this->rememberFilterOptions($column, fn () => Note::query()
            ->whereIn('id', $noteIds())->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column)
            ->map(fn ($value) => ['v' => (string) $value, 'l' => (string) $value])->all());

        return ['rubrica' => $distinct('rubrica'), 'city' => $distinct('lexp')];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . $this->service->uuid . ':' . $this->targetUserId();
    }

    /** A fila muda a cada nota assumida ou concluída: as opções valem pouco tempo. */
    protected function listFilterCacheTtl(): int
    {
        return 120;
    }

    /**
     * Usuários com atividade em aberto nesta atividade, para a visão administrativa do superadm.
     *
     * @return array<int, array{id: string, name: string, total: int}>
     */
    protected function adminUserOptions(): array
    {
        if (!Gate::allows('superadm')) {
            return [];
        }

        return Cache::remember('accompany-admin-users:' . $this->service->uuid, 300, fn () => Production::query()
            ->join('users', 'users.id', '=', 'productions.user_id')
            ->where('productions.service_id', $this->service->uuid)->where('productions.completed', false)
            ->groupBy('users.id', 'users.name')->orderBy('users.name')
            ->get(['users.id', 'users.name', DB::raw('COUNT(*) as total')])
            ->map(fn ($u) => ['id' => (string) $u->id, 'name' => (string) $u->name, 'total' => (int) $u->total])->all());
    }

    // ---------- Consulta ----------

    protected function listQuery(): Builder
    {
        $profile = $this->profile();

        $query = Production::query()
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.user_id', $this->targetUserId())
            ->where('productions.completed', false)
            ->select('productions.*')
            ->with('Note');

        $term = trim((string) $this->search);

        // Um só grupo: o OR da busca nunca pode escapar do escopo do usuário.
        $query->when($term !== '', function ($q) use ($term) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            return $q->where(fn ($w) => $w->where('notes.note', 'like', $like)->orWhere('notes.material', 'like', $like)->orWhere('notes.numPedido', 'like', $like));
        });

        $query->when($this->multiSearch, fn ($q) => $q->where(fn ($w) => $w->whereIn('notes.note', $this->multiSearch)->orWhereIn('notes.numPedido', $this->multiSearch)));
        $query->when($this->filterValues('rubrica'), fn ($q, $values) => $q->whereIn('notes.rubrica', $values));
        $query->when($this->filterValues('city'), fn ($q, $values) => $q->whereIn('notes.lexp', $values));

        return $query
            ->orderByDesc('productions.priority')
            ->orderBy($profile['order'] === 'days_left' ? 'notes.days_left' : 'notes.dt_created')
            ->orderBy('productions.id');
    }

    /** Dados comuns do render: página, linhas prontas e o que a barra de ferramentas precisa. */
    protected function accompanyViewData(): array
    {
        $profile = $this->profile();
        $lists   = $this->listQuery()->paginate((int) $this->perPage);
        $legal   = $this->buildLegalTagsByNoteIds(collect($lists->items())->pluck('note_id')->all());
        $now     = now();

        return [
            'lists'       => $lists,
            'rows'        => collect($lists->items())->map(fn (Production $p) => app(NoteAccompanyRowPresenter::class)->present($p, $profile, $legal[$p->note_id] ?? [], $now))->values(),
            'profile'     => $profile,
            'pageSizes'   => self::PAGE_SIZES,
            'hasCriteria' => $this->hasCriteria(),
            'adminUsers'  => $this->adminUserOptions(),
            'viewingName' => $this->user_s && Gate::allows('superadm') ? \App\Models\User::whereKey($this->user_s)->value('name') : null,
        ];
    }

    // ---------- Ações da linha ----------

    /** A produção é da fila que está na tela? (o usuário, ou o escolhido pelo superadm) */
    private function ownsProduction($productionId): bool
    {
        return Production::query()
            ->whereKey($productionId)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', $this->targetUserId())
            ->exists();
    }

    public function goTransferProd($prod_id)
    {
        if ($this->ownsProduction($prod_id)) {
            $this->emit('transfer_production', $prod_id);
        }
    }

    /** Ao abrir a tela: se o usuário ainda tem uma nota em atividade, abre o formulário dela (e avisa que é preciso encerrá-la ou pausá-la). */
    public function checkOpen()
    {
        $check = Production::where('service_id', $this->service->uuid)->where('user_id', auth()->id())->where('status', 3)->first();

        if (!$check) {
            return;
        }

        $profile = $this->profile();

        $this->emit($profile['event'], ['productionId' => $check->id, 'noteId' => $check->note_id]);
        $this->dispatchBrowserEvent('showModal', ['id' => $profile['modal']]);
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'info',
            'title'    => 'NOTA AINDA EM ATIVIDADE',
            'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA. \n
                <p class='text-bg-light mt-2 p-2'>
                    É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                    adequada.
                </p>
            ",
        ]);
    }

    public function go_to_analise()
    {
        $profile = $this->profile();

        $this->emit($profile['event'], $this->analise);
        $this->dispatchBrowserEvent('showModal', ['id' => $profile['modal']]);
    }

    /** Iniciar (ou continuar/retomar) a nota: abre o formulário da atividade, avisando antes se o limite de pausa já foi atingido. */
    public function getAnalise($production, $note)
    {
        if (!$this->ownsProduction($production)) {
            return;
        }

        $this->analise = ['productionId' => $production, 'noteId' => $note];

        $paused = Production::where('status', 4)->where('service_id', $this->service->uuid)->where('user_id', auth()->id())->count();

        if ($this->profile()['limitPause'] === $paused && Production::find($production)->status != 4) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você ja atingiu o limite de pausa neste serviço, ao iniciar esta nota, você não poderá colocar esta NOTA/OV em espera. \n Tem certeza que deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',
            ]);

            return;
        }

        $this->go_to_analise();
    }
}
