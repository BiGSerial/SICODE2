<?php

namespace App\Http\Livewire\Responsible;

use App\Models\City;
use App\Models\File;
use App\Models\Viability;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class JustifyViab extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search;
    public $perPage = 50;
    public $cities;



    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

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
        $query = Viability::query();

        $query->whereHas('Justification', function ($query) {
            $query->where('granted', false)
            ->where('dismissed', false)
            ->orderBy('justified_at', 'desc');
        })->where('tacit', true);


        if (!auth()->user()->superadm) {


            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            // } else {
            //     $query->where('company_id', Auth()->user()->Company->id);
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());

        }

        // return $query->select('viabilities.*', 'tacit_comments.justified_at as comment_justified_at');
        return $query;
    }



    public function render()
    {
        return view('livewire.responsible.justify-viab', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
