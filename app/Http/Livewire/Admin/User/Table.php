<?php

namespace App\Http\Livewire\Admin\User;

use App\Models\{Company, User};
use Livewire\{Component, WithPagination};

class Table extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $users;

    public $perPage = 50;

    public $show_update = false;

    public $user_id;

    public $search;

    public $companies;

    public $company_s;

    protected $listeners = [
        'refresh_table_user' => '$refresh',

    ];

    public function update_user($id)
    {

        $this->user_id     = $id;
        $this->show_update = true;
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'update_modal',
        ]);

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
            ->when($this->company_s, function ($q, $s) {
                return $q->whererelation('Employee.Contract', 'company_id', $s);
            })
            ->with('Employee.Contract.Company')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $this->companies = Company::orderBy('name')->get();

        return view('livewire.admin.user.table', [
            'users_l' => $this->user,
        ]);
    }
}
