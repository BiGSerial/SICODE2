<?php

namespace App\Http\Livewire\Services\Levantamento;

use App\Models\{File, Production, Service, User};
use Illuminate\Support\Facades\Storage;
use Livewire\{Component, WithPagination};

class Histviab extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $limit_pause = 3;

    public $analise;

    public $production;

    public $note;

    public $user_l;

    public $user_s;

    public $user_search;

    public $date_prod_l;

    public $date_prod_s;

    public $meses = [
        1  => 'Janeiro',
        2  => 'Fevereiro',
        3  => 'Março',
        4  => 'Abril',
        5  => 'Maio',
        6  => 'Junho',
        7  => 'Julho',
        8  => 'Agosto',
        9  => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    protected $listeners = [
        'getCopy' => 'copy',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();

        // $this->date_prod_l = Production::Where('service_id', $this->service->uuid)
        //                     ->where('user_id', Auth()->User()->id)
        //                     ->where('completed', true)
        //                     ->where('rejected', false)
        //                     ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as mes_ano, COUNT(*) as total')
        //                     ->groupBy('mes_ano')
        //                     ->orderBy('mes_ano')
        //                     ->get();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function visualizar()
    {
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function getListsProperty()
    {
        $this->date_prod_l = Production::whererelation('Note', function ($q) {
            $q->whereHas('Viabilities');
        })
            ->Where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', true)
            ->where('rejected', false)
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as mes_ano, COUNT(*) as total')
            ->groupBy('mes_ano')
            ->orderBy('mes_ano')
            ->get();

        $this->user_l = User::when($this->user_search, function ($q) {
            return $q->where('name', 'like', '%' . $this->user_search . '%');
        })->orderBy('name')->get();

        return Production::whererelation('Note', function ($q) {
            $q->whereHas('Viabilities');
        })
            ->Where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', true)
            ->where('rejected', false)
            ->when($this->search, function ($q, $s) {
                return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                    ->orwhereRelation('Note', 'material', 'like', '%' . $s . '%');
            })
            ->when($this->date_prod_s, function ($q) {
                $q->whereRaw('DATE_FORMAT(completed_at, "%Y-%m") = ?', [$this->date_prod_s]);
            })
            ->with(['Note' => function ($query) {
                $query->orderBy('dt_status', 'asc');
            }], 'Analise')
            ->orderBy('completed_at', 'DESC')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.services.levantamento.histviab', [
            'lists' => $this->lists,
        ]);
    }
}
