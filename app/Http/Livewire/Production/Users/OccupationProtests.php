<?php

namespace App\Http\Livewire\Production\Users;

use App\Models\{Company, Production, Service, User, UserAssignment};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OccupationProtests extends Component
{
    public $service;

    public $search = '';

    // public function mount($service_id)
    // {
    //     $this->service = Service::where('uuid', $service_id)->first();

    // }

    public function getListsProperty()
    {
        return UserAssignment::query()
                ->when($this->search, fn ($query) => $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%")))
                ->select([
                    'user_id',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN user = true THEN 1 ELSE 0 END) as total_user'),
                    DB::raw('SUM(CASE WHEN monitoring = true THEN 1 ELSE 0 END) as total_monitoring'),
                ])
                ->where('assignable_type', 'App\Models\MedProtest')
                ->where('completed', false)
                ->where('responsible', false)
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->orderBy('user_id', 'desc')
                ->with('user:id,name', 'user.Watchdog:id,user_id,watchdog')

                ->get();

    }

    public function render()
    {

        return view('livewire.production.users.occupation-protests', [
            'lists' => $this->lists,
        ]);
    }
}
