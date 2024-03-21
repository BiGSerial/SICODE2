<?php

namespace App\Http\Livewire\Admin\Company;

use App\Models\Company;
use Livewire\{Component, WithPagination};

class Table extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $users;

    public $perPage = 50;

    public $show_update = false;

    public $company_id;

    public $search;

    protected $listeners = [
        'refresh_table_company' => '$refresh',

    ];

    public function update_company($id)
    {

        $this->company_id  = $id;
        $this->show_update = true;
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'update_modal',
        ]);

    }

    public function getCompaniesProperty()
    {
        return Company::when(
            Auth()->User()->superadm,
            function ($q) {
                return $q->withTrashed();
            }
        )
        // ->when(
        //     Auth()->User()->contract,
        //     function ($q) {
        //         return $q->where('superadm', false);
        //     }
        // )
            ->when($this->search, function ($q, $s) {
                return $q->where('name', 'like', '%' . $s . '%');
            })
            ->with('Address')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.company.table', [
            'companies_l' => $this->companies,
        ]);
    }
}
