<?php

namespace App\Http\Livewire\Admin\Company\Contract;

use App\Models\Contract;
use Livewire\{Component, WithPagination};

class Table extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $users;

    public $perPage = 50;

    public $contract_id;

    public $search;

    protected $listeners = [
        'refresh_table_contract' => '$refresh',

    ];

    public function update_contract($id)
    {

        $this->emit('open_contract_update', $id);

    }

    public function getContractsProperty()
    {
        return Contract::when($this->search, function ($q, $s) {
            return $q->where('number', 'like', '%' . $s . '%');
        })
            ->with('Company')
            ->orderBy('number')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.company.contract.table', [
            'contracts_l' => $this->contracts,
        ]);
    }
}
