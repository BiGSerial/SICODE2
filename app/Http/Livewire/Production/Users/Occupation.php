<?php

namespace App\Http\Livewire\Production\Users;

use App\Models\{Company, Production, Service};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Occupation extends Component
{
    public $service;

    public $company_l;

    public $company_s;

    public function mount($service_id)
    {
        $this->service = Service::where('uuid', $service_id)->first();

    }

    public function getListsProperty()
    {
        return DB::table('users')
            ->join('productions', 'users.id', '=', 'productions.user_id')
            ->join('employees', 'users.id', '=', 'employees.user_id')
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('productions.service_id', '=', $this->service->uuid)
            ->when(Auth()->User()->contract, function ($q) {
                return $q->where('productions.company_id', '=', Auth()->User()->Employee->Contract->company_id);
            })
            ->when($this->company_s, function ($q) {
                return $q->where('productions.company_id', $this->company_s);
            })
            ->where('productions.completed', '=', false)
            ->select('users.id', 'users.name', DB::raw('count(productions.id) as registros'), DB::raw('SUM(CASE WHEN notes.type_note = 2 THEN 1 ELSE 0 END) as ov'), DB::raw('SUM(CASE WHEN notes.type_note = 1 THEN 1 ELSE 0 END) as notes'))
            ->groupBy('users.id', 'users.name')
            ->get();
    }

    public function render()
    {
        $this->company_l = Company::whereIn('id', Production::where('confirmed', false)->where('service_id', $this->service->uuid)->get()->pluck('company_id')->unique()->toArray())
            ->orderBy('name')
            ->get();

        return view('livewire.production.users.occupation', [
            'lists' => $this->lists,
        ]);
    }
}
