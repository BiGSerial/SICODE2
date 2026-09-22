<?php

namespace App\Http\Livewire\Partner;

use App\Models\{File, Viability};
use App\Services\Files\FileStorageService;
use Livewire\{Component, WithPagination};

class TacitList extends Component
{
    use \App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search;

    // Filters
    private $filter_group = 'partner';

    private $filter;

    protected $listeners = ['refresh_list' => '$refresh'];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    // Sempre que atualizar a página, coloca para exibir o primeiro reegistro.
    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function export_excel()
    {
        $this->authorizePartnerAccess('viability.export');

    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) {
                session()->start();
            }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query()
            ->doesntHave('Justification')
            ->whereBetween('tacit_at', [now()->subDays(7)->startOfDay(), now()->endOfDay()])
            ->where('approved', true)
            ->where('completed', true)
            ->where('tacit', true);

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', $this->search)
                    ->orWhereRelation('Note.Orders', 'ordem', $this->search);
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        return $query->orderBy('tacit_at');
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
        return view('livewire.partner.tacit-list', [
            'lists' => $this->lists->paginate($this->perPage, ['*'], 'listsPage'),

        ]);

    }
}
