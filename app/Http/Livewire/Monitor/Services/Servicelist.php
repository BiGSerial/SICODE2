<?php

namespace App\Http\Livewire\Monitor\Services;

use App\Models\Production;
use Livewire\Component;

class Servicelist extends Component
{
    public $user_s;

    public $service_s;

    public $company_s;

    protected $listeners = [
        'refreshServiceList' => '$refresh',
        'searchService'      => 'selService',
        'searchUser'         => 'selUSer',
        'searchCompany'      => 'selCompany',
    ];

    public function selUSer($user)
    {
        // dd($user, 'Service');

        $this->user_s = $user;
    }

    public function selCompany($company)
    {
        $this->company_s = $company;
    }

    public function selService($service)
    {
        // dd($service, 'Service');

        $this->service_s = $service;
    }

    public function getTasksProperty()
    {
        return Production::whereNotNull('user_id')
            ->where('status', '>', 1)
            ->where('completed', false)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            })
            ->when($this->service_s, function ($q) {
                return $q->where('service_id', $this->service_s);
            })
            ->when($this->company_s, function ($q) {
                return $q->where('company_id', $this->company_s);
            })
            ->when(
                Auth()->User()->contract,
                function ($q) {
                    return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
                },
                function ($q) {
                    return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
                }
            );
    }

    public function render()
    {
        return view('livewire.monitor.services.servicelist', [
            'live_tasks' => $this->tasks
                ->where('confirmed', false)
                ->with('User', 'Company', 'Service', 'Note', 'Analise')
                ->orderBy('updated_at', 'DESC')
                ->get(),

        ]);
    }
}
