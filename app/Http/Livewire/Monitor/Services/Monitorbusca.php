<?php

namespace App\Http\Livewire\Monitor\Services;

use App\Models\Production;
use Livewire\Component;

class Monitorbusca extends Component
{
    public $user_s;

    public $service_s;

    public $company_s;

    public function getUsersProperty()
    {
        if (Auth()->User()->contract) {
            $this->company_s = Auth()->User()->Employee->Contract->company->id;
        }

        return Production::whereNotNull('user_id')
            ->when($this->company_s, function ($q) {
                return $q->where('company_id', $this->company_s);
            })
            ->with('User')
            ->select('user_id')
            ->groupBy('user_id');
    }

    public function getCompanyProperty()
    {
        return Production::select('company_id')
            ->groupBy('company_id');
    }

    public function getServicesProperty()
    {
        return Production::select('service_id')
            ->groupBy('service_id');
    }

    public function Search()
    {
        $this->emit('searchUser', $this->user_s);
        $this->emit('searchService', $this->service_s);
        $this->emit('searchCompany', $this->company_s);

    }

    public function render()
    {
        return view('livewire.monitor.services.monitorbusca', [
            'company_l' => $this->company->with('Company')->get(),
            'user_l'    => $this->users->get(),
            'service_l' => $this->services->with('Service')->get(),
        ]);
    }
}
