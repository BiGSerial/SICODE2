<?php

namespace App\Http\Livewire\Partner;

use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, WithViabilityListFilters};
use App\Models\{File, Viability};
use App\Services\Files\FileStorageService;
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\{Component, WithPagination};

/**
 * Viabilidades rejeitadas pela Engenharia, em duas abas: as que aguardam a resposta da parceira (status 5) e as que
 * já estão em tratativa. Lista de controle (docs/PADRAO_LISTAS_OPERACIONAIS.md §10); a resposta mora em `partner.actions.viab-response`.
 */
class ViabRejectedList extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;
    use WithViabilityListFilters;

    protected $paginationTheme = 'bootstrap';

    /** Status da viabilidade que significa "aguardando resposta da parceira". */
    private const AWAITING_STATUS = 5;

    public $perPage = 50;

    public $search = '';

    /** awaiting | treating */
    public string $tab = 'awaiting';

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'tab'     => ['except' => 'awaiting'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    public function mount(): void
    {
        $this->tab     = $this->tab === 'treating' ? 'treating' : 'awaiting';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->tab = $this->tab === 'treating' ? 'treating' : 'awaiting';
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->resetPage();
    }

    public function can(string $permission): bool
    {
        return PartnerAccessGate::allows(auth()->user(), $permission);
    }

    /** Recorte comum às duas abas: rejeitadas e ainda não concluídas, na empresa do usuário, com busca e filtros. */
    private function baseQuery()
    {
        $query = Viability::query()->where('rejected', true)->where('completed', false);

        if (filled($this->search)) {
            $term = trim($this->search);

            $query->where(function ($q) use ($term) {
                $q->whereRelation('Note', 'note', $term)
                    ->orWhereRelation('Note.Orders', 'ordem', $term);
            });
        }

        $this->applyPartnerCompanyScope($query);
        $this->applyPartnerBranchScopeToNoteRelation($query);

        return $this->applyViabilityFilters($query);
    }

    /** Contagem das duas abas em uma consulta só (no mesmo recorte de busca e filtros). */
    private function counts(): array
    {
        $row = $this->baseQuery()->toBase()
            ->selectRaw('COALESCE(SUM(status = ?), 0) as awaiting', [self::AWAITING_STATUS])
            ->selectRaw('COALESCE(SUM(status != ?), 0) as treating', [self::AWAITING_STATUS])
            ->first();

        return ['awaiting' => (int) $row->awaiting, 'treating' => (int) $row->treating];
    }

    public function downloadFile($id)
    {
        $this->authorizePartnerAccess('viability.view_files');

        if ($file = File::find($id)) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }
    }

    public function render()
    {
        $awaiting = $this->tab === 'awaiting';

        $lists = $this->baseQuery()
            ->when($awaiting, fn ($q) => $q->where('status', self::AWAITING_STATUS), fn ($q) => $q->where('status', '!=', self::AWAITING_STATUS))
            ->withSum('Days as days_sum', 'days')
            ->with([
                'Note:id,note,rubrica,lexp,type_note',
                'Note.Orders:id,note_id,ordem,statusSist',
                'Note.Files:id,note_id,service_id,file_name,ext,path',
                'Note.Files.service:uuid,service',
                'Note.Files.note:id,note',
                'Orders:id,note_id,ordem',
                'Form:id,viability_id,reason',
                'Engineer:id,name,email',
                'Company:id,name',
            ])
            ->orderBy('updated_at')
            ->paginate($this->perPage);

        return view('livewire.partner.viab-rejected-list', [
            'lists'        => $lists,
            'tacitFileIds' => $this->tacitFileIdsFor($lists),
            'counts'       => $this->counts(),
            'awaiting'     => $awaiting,
            'hasCriteria'  => $this->hasViabilityCriteria(),
        ]);
    }
}
