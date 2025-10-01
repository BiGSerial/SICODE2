<?php

namespace App\Http\Livewire\Production\Users;

use App\Models\{Company, Production, Service, User};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OccupationConstruction extends Component
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
        // return DB::table('users')
        //     ->join('productions', 'users.id', '=', 'productions.user_id')
        //     ->join('employees', 'users.id', '=', 'employees.user_id')
        //     ->join('notes', 'productions.note_id', '=', 'notes.id')
        //     ->where('productions.service_id', '=', $this->service->uuid)
        //     ->when(Auth()->User()->contract, function ($q) {
        //         return $q->where('productions.company_id', '=', Auth()->User()->Employee->Contract->company_id);
        //     })
        //     ->when($this->company_s, function ($q) {
        //         return $q->where('productions.company_id', $this->company_s);
        //     })
        //     ->where('productions.completed', '=', false)
        //     ->select('users.id', 'users.name', DB::raw('count(productions.id) as registros'), DB::raw('SUM(CASE WHEN notes.type_note = 2 THEN 1 ELSE 0 END) as ov'), DB::raw('SUM(CASE WHEN notes.type_note = 1 THEN 1 ELSE 0 END) as notes'))
        //     ->groupBy('users.id', 'users.name')
        //     ->get();

        $query = User::with(['Productions', 'Employee.contract'])
        ->join('productions', 'users.id', '=', 'productions.user_id')
        ->join('employees', 'users.id', '=', 'employees.user_id')
        ->join('notes', 'productions.note_id', '=', 'notes.id')
        ->leftJoin('ramal_reports', 'notes.id', '=', 'ramal_reports.note_id')
        ->leftJoin('work_reports', 'notes.id', '=', 'work_reports.note_id')
        ->where('productions.service_id', $this->service->uuid)
        ->when(Auth()->user()->contract, function ($q) {
            return $q->where('productions.company_id', Auth()->user()->employee->contract->company_id);
        })
        ->when($this->company_s, function ($q) {
            return $q->where('productions.company_id', $this->company_s);
        })
        ->where(function ($subQuery) {
            $subQuery->where(function ($q1) {
                $q1->where('productions.status', '!=', 28)
                ->WhereNull('ramal_reports.id')
                ->WhereNotNull('work_reports.id');
            })->orWhere(function ($q2) {
                $q2->where('productions.status', '=', 28)
                ->WhereNotNull('ramal_reports.id')
                ->WhereNotNull('work_reports.id');
            });
        })
        ->where('productions.completed', false)
        ->where(function ($q) {
            $q->whereRelation('Productions', 'status', '!=', 29)
            ->orWhere('productions.confirmed', null);
        })
        ->select('users.id', 'users.name')
        ->selectRaw('count(productions.id) as registros')
        ->selectRaw('SUM(CASE WHEN notes.type_note = 2 THEN 1 ELSE 0 END) as ov')
        ->selectRaw('SUM(CASE WHEN notes.type_note = 1 THEN 1 ELSE 0 END) as notes')
        ->groupBy('users.id', 'users.name')
        ->OrderBy('registros', 'desc')
        ->OrderBy('ov', 'desc')
        ->OrderBy('notes', 'desc')
        ->OrderBy('users.id', 'asc')
        ->get();

        // $query = Production::with(['User', 'Note'])
        //     ->join('users', 'productions.user_id', '=', 'users.id')
        //     ->join('notes', 'productions.note_id', '=', 'notes.id')
        //     ->leftJoin('ramal_reports', 'notes.id', '=', 'ramal_reports.note_id')
        //     ->leftJoin('work_reports', 'notes.id', '=', 'work_reports.note_id')
        //     ->where('productions.service_id', $this->service->uuid)
        //     ->where('productions.completed', false)
        //     ->when($this->company_s, function ($q) {
        //         return $q->where('productions.company_id', $this->company_s);
        //     })
        //     ->where(function ($subQuery) {
        //         $subQuery->where(function ($q1) {
        //             $q1->where('productions.status', '!=', 28)
        //             ->WhereNull('ramal_reports.id')
        //             ->WhereNotNull('work_reports.id');
        //         })->orWhere(function ($q2) {
        //             $q2->where('productions.status', '=', 28)
        //             ->WhereNotNull('ramal_reports.id')
        //             ->WhereNotNull('work_reports.id');
        //         });
        //     })
        //     ->select('users.id', 'users.name')
        //     ->selectRaw('count(*) as registros')
        //     ->selectRaw('SUM(CASE WHEN notes.type_note = 1 THEN 1 ELSE 0 END) as notes')
        //     ->selectRaw('SUM(CASE WHEN notes.type_note = 2 THEN 1 ELSE 0 END) as ov')
        //     ->groupBy('users.id', 'users.name')
        //     ->OrderBy('registros', 'desc')
        //     ->OrderBy('ov', 'desc')
        //     ->OrderBy('notes', 'desc')
        //     ->get();



        // dd($query);

        return $query;

    }

    public function render()
    {
        $this->company_l = Company::whereIn('id', Production::where('confirmed', false)->where('service_id', $this->service->uuid)->get()->pluck('company_id')->unique()->toArray())
            ->orderBy('name')
            ->get();

        return view('livewire.production.users.occupation-construction', [
            'lists' => $this->lists,
        ]);
    }
}
