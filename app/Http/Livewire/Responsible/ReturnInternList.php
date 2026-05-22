<?php

namespace App\Http\Livewire\Responsible;

use App\Exports\Engineers\InterReturnExport;
use App\Models\File;
use App\Models\Reclaim;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ReturnInternList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search;

    // Filters
    private $filter_group = 'partner';
    private $filter;

    protected $listeners = [
        'refresh' => '$refresh',
        'refresh_list' => '$refresh',
    ];


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

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
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

    public function export_excel()
    {
        return (new InterReturnExport($this->my_lists->get()))->download(date('YmdHis').'_interReturnExport.xlsx');
    }


    public function getMyListsProperty()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }



        $query = Viability::query()
            ->when(trim((string)$this->search) !== '', function ($query) {
                $query->where(function ($q) {
                    $q->orwhereRelation('Note', 'note', 'like', '%'.trim($this->search).'%')
                    ->orWhereRelation('Orders', 'ordem', 'like', '%'.trim($this->search).'%');
                });
            })
            ->where('rejected', true)
            ->where('status', 13)
            ->whereHas('Reclaims', function ($q) {
                $q->where('completed', true);
            });

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

        if (!auth()->user()->superadm) {
            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        // Eager load do último Reclaim
        $query->with([
            'Note',
            'Orders',
            'Reclaims' => function ($q) {
                $q->orderBy('id', 'desc')->limit(1);
            }
        ]);


        return $query->orderBy('viabilities.updated_at');
    }




    public function render()
    {



        return view('livewire.responsible.return-intern-list', [
            'myLists' => $this->my_lists->paginate($this->perPage, ['*'], 'myListsPage'),
        ]);

    }
}
