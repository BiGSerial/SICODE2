<?php

namespace App\Http\Livewire\Partner;

use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, WithViabilityListFilters};
use App\Models\{File, Viability};
use App\Services\Files\FileStorageService;
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\{Component, WithPagination};

/**
 * Viabilidades vencidas tacitamente nos últimos 7 dias que ainda esperam a justificativa da parceira.
 * Lista de controle (docs/PADRAO_LISTAS_OPERACIONAIS.md §10); a justificativa mora em `partner.actions.tacit-justify`.
 */
class TacitList extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;
    use WithViabilityListFilters;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search = '';

    protected $listeners = ['refresh_list' => '$refresh'];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    public function mount(): void
    {
        $this->filters = $this->normalizeFilters($this->filters);
    }

    public function updatingSearch(): void
    {
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

    public function getListsProperty()
    {
        $query = Viability::query()
            ->doesntHave('Justification')
            ->whereBetween('tacit_at', [now()->subDays(7)->startOfDay(), now()->endOfDay()])
            ->where('approved', true)
            ->where('completed', true)
            ->where('tacit', true);

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        if (filled($this->search)) {
            $term = trim($this->search);

            $query->where(function ($q) use ($term) {
                $q->whereRelation('Note', 'note', $term)
                    ->orWhereRelation('Note.Orders', 'ordem', $term);
            });
        }

        return $this->applyViabilityFilters($query)->orderBy('tacit_at');
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
        $lists = $this->lists
            ->with([
                'Note:id,note,rubrica,lexp,type_note',
                'Note.Orders:id,note_id,ordem,statusSist',
                'Note.Files:id,note_id,service_id,file_name,ext,path',
                'Note.Files.service:uuid,service',
                'Note.Files.note:id,note',
                'Engineer:id,name,email',
                'Company:id,name',
            ])
            ->paginate($this->perPage);

        return view('livewire.partner.tacit-list', [
            'lists'        => $lists,
            'tacitFileIds' => $this->tacitFileIdsFor($lists),
            'hasCriteria'  => $this->hasViabilityCriteria(),
        ]);
    }
}
