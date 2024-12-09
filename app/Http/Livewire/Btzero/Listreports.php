<?php

namespace App\Http\Livewire\Btzero;

use App\Models\Company;
use App\Models\Note;
use Livewire\Component;
use Livewire\WithPagination;

class Listreports extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $selected;
    public $search;
    public $companies;
    public $company;

    protected $queryString = [
            'search' => ['except' => ''],
            'company' => ['except' => ''],
        ];

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }


    public function getListsProperty()
    {
        return Note::whereHas('RamalForm', function ($q) {
            $q->when($this->company, function ($sq) {
                $sq->where('company_id', $this->company);
            });
        })
        ->where(function ($q) {
            $q->whereDoesntHave('WorkForm')
            ->orWhereHas('WorkForm', function ($query) {
                $query->where('created_at', '>=', now()->subDays(3));
            });
        })
        ->when($this->search, function ($q) {
            $q->where('note', 'like', '%'.$this->search.'%')
            ->orWhereRelation('Orders', 'ordem', 'like', '%'.$this->search.'%');
        })
        ->with('RamalForm.Company', 'RamalForm.User', 'RamalForm.Orders', 'RamalForm.BtzeroEquipment')
        ->paginate(30);
    }

    public function selectNote($id)
    {
        if ($this->selected == $id) {
            $this->selected = '';
            $this->emitTo('btzero.dashboard.list-production-btzero', 'selectNote', $this->selected);
        } else {
            $this->selected = $id;
            $this->emitTo('btzero.dashboard.list-production-btzero', 'selectNote', $this->selected);
        }

    }


    public function render()
    {
        return view(
            'livewire.btzero.listreports',
            [
            'lists' => $this->lists
        ]
        );
    }
}
