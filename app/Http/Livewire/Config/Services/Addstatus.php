<?php

namespace App\Http\Livewire\Config\Services;

use App\Models\{AuxiliarService, Note, Service};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Addstatus extends Component
{
    public $showAddstatus = false;

    public $service;

    public $status_l;

    public $status_s;

    public $status_list;

    public $exclusion;

    public $value;

    public $condition;

    public $column_search;

    public $exclusion2;

    public $value2;

    public $condition2;

    public $column_search2;

    public $view_and = false;

    public $columns_l;

    protected $listeners = [
        'open_add_status' => 'open_add_status',
    ];

    public function and()
    {
        if ($this->view_and) {
            $this->view_and = false;

            $this->exclusion2     = false;
            $this->value2         = '';
            $this->condition2     = '';
            $this->column_search2 = '';
        } else {
            $this->view_and = true;
        }
    }

    public function mount()
    {
        $this->columns_l = (new Note())->getFillable();
    }

    public function open_add_status(Service $service)
    {
        $this->service = $service->load('Status');

        // dd($this->service);

        $this->showAddstatus = true;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'add_status_modal',
        ]);
    }

    public function add()
    {
        $aux = AuxiliarService::Where('service_id', $this->service->uuid)->where('value', $this->value)->first();

        if (!$aux || ($aux && $this->value2 != $aux->value2)) {
            AuxiliarService::create([
                'service_id'     => $this->service->uuid,
                'column_search'  => trim($this->column_search),
                'condition'      => $this->condition,
                'exclusion'      => $this->exclusion ? true : false,
                'value'          => $this->value,
                'column_search2' => trim($this->column_search2),
                'condition2'     => $this->condition2,
                'exclusion2'     => $this->exclusion2 ? true : false,
                'value2'         => $this->value2,
            ]);
        }
    }

    public function remove($id)
    {
        AuxiliarService::find($id)->delete();
    }

    public function render()
    {
        // $this->status_l = Note::select('nstats', DB::raw('MAX(status) as status'))
        // ->orderBy('nstats')
        // ->groupBy('nstats')
        // ->get();

        if ($this->service) {
            $this->status_list = AuxiliarService::where('service_id', $this->service->uuid)->orderBy('exclusion')->orderBy('value')->get();
        }

        return view('livewire.config.services.addstatus');
    }
}
