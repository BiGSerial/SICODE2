<?php

namespace App\Http\Livewire\Responsible;

use App\Exports\Viability\ViabilitiesRejectedExport;
use App\Models\File;
use App\Models\Viability;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class RejectedListviab extends Component
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
    ];


    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];


    public function exportToExcel_mylist()
    {
        return (new ViabilitiesRejectedExport($this->my_lists))
             ->download(date('Ymd_His').'-viabilidades_rejeitadas_para_responder.xlsx');
    }

    public function exportToExcel_lists()
    {
        return (new ViabilitiesRejectedExport($this->lists))
             ->download(date('Ymd_His').'-viabilidades_rejeitadas_aguardando_resolucao.xlsx');
    }

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

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query()->where('rejected', true)
                ->where('completed', false)
                ->where('status', '!=', 4);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
            });
        }

        if (!auth()->user()->superadm) {

            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }



        return $query->orderBy('updated_at');
    }


    public function getMyListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query()->where('rejected', true)
                ->where('completed', false)
                ->where('status', 4);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
            });
        }

        if (!auth()->user()->superadm) {


            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());

        }



        return $query->orderBy('updated_at');
    }


    public function render()
    {
        return view('livewire.responsible.rejected-listviab', [
            'lists' => $this->lists->paginate($this->perPage, ['*'], 'listsPage'),
            'myLists' => $this->my_lists->paginate($this->perPage, ['*'], 'myListsPage'),
        ]);

    }
}
