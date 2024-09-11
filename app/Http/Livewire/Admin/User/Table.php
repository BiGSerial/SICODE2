<?php

namespace App\Http\Livewire\Admin\User;

use App\Exports\Reports\UserListExport;
use App\Helpers\TextFormatter;
use App\Models\{Company, User};
use Livewire\{Component, WithPagination};

class Table extends Component
{
    use TextFormatter;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $users;

    public $perPage = 30;

    public $show_update = false;

    public $user_id;

    public $search;

    public $companies;

    public $company_s;

    public $selectAll;

    public $selected = [];

    public $selectedCompany;

    public $preText;

    public $multiSearch = [];

    public ?User $master = null;

    protected $listeners = [
        'refresh_table_user' => '$refresh',
        'refresh_table_mass_user' => 'refreshAll',

    ];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'pag'],
        'selectedCompany'    => ['except' => '', 'as' => 'empresa'],
    ];

    public function mount()
    {
        $this->master = User::first();
    }

    public function update_user($id)
    {

        $this->user_id     = $id;
        $this->show_update = true;
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'update_modal',
        ]);

    }

    public function multiSearch()
    {
        if ($this->preText) {
            $this->search = '';
            $this->multiSearch = $this->formatTextToArray($this->preText);
            $this->gotoPage(1);
        }

        $this->dispatchBrowserEvent('hideModal');
    }

    public function updatedSearch()
    {
        if ($this->search) {
            $this->multiSearch = [];
            $this->preText = '';
            $this->gotoPage(1);
        }

    }

    public function refreshAll()
    {
        $this->selected = [];
        $this->emitSelf('refresh_table_user');
    }

    public function editInMass()
    {
        $this->emitTo('admin.user.actions.usuario-mass', 'alterUsers', $this->selected);
    }



    public function checkAllSelect($items)
    {


        $items = $items->pluck('id')->toArray();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;

    }

    public function setSelectAll()
    {

        $idsToKeep = $this->user->pluck('id')->toArray();

        if ($this->selectAll) {
            // Adicionar os IDs ausentes de $selected
            foreach ($idsToKeep as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $idsToKeep)) {
                    $newSelected[] = $id;
                }
            }
            $this->selected = $newSelected;
        }

    }

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new UserListExport($this->user->get()))->download(date('YmdHis-') . '_users_list.xlsx');
        } else {
            return (new UserListExport($this->user->find($this->selected)))->download(date('YmdHis-') . '_users_list.xlsx');
        }
    }


    public function getUserProperty()
    {
        return User::when(
            !Auth()->User()->superadm,
            function ($q) {
                return $q->where('superadm', false)
                    ->whererelation('Employee.Contract', 'company_id', Auth()->User()->Employee->Contract->company_id);
            },
            function ($q) {
                return $q->withTrashed();
            }
        )
            ->when($this->search, function ($q, $s) {
                return $q->where('name', 'like', '%' . $s . '%');
            })
            ->when($this->selectedCompany, function ($q, $s) {
                return $q->whererelation('Employee.Contract', 'company_id', $s);
            })
            ->when($this->multiSearch, function ($q) {
                $q->whereIn('id', $this->multiSearch)
                    ->orWhereIn('email', $this->multiSearch);
            })
            ->with('Employee.Contract.Company', 'Watchdog')
            ->orderBy('name');

    }

    public function render()
    {
        $this->companies = Company::orderBy('name')->get();

        return view('livewire.admin.user.table', [
            'users_l' => $this->user->paginate($this->perPage),
        ]);
    }
}
