<?php

namespace App\Http\Livewire\Services\Cadastro;

use App\Models\{Production, Service};
use Livewire\{Component, WithPagination};

class Historic extends Component
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

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();

    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function getListsProperty()
    {
        return Production::Where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->User()->id)
            ->where('completed', true)
            ->when($this->search, function ($q, $s) {
                return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                    ->orwhereRelation('Note', 'material', 'like', '%' . $s . '%');
            })
            ->with(['Note' => function ($query) {
                $query->orderBy('dt_status', 'asc');
            }], 'Analise')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.services.cadastro.historic', [
            'lists' => $this->lists,
        ]);
    }
}
