<?php

namespace App\Http\Livewire\Components\Count;

use App\Models\Production;
use App\Models\Service;
use Livewire\Component;

class Countnotes extends Component
{
    public $service;

    public $onlyuser;

    public $status;

    public $geral;

    public function mount($service = null, $status = null, $onlyuser = true, $geral = false)
    {
        $this->service  = $service;
        $this->onlyuser = $onlyuser;
        $this->status   = $status;
        $this->geral    = $geral;
    }

    public function getCountProperty()
    {
        $query = Production::query()
            ->when($this->service, function ($q) {
                return $q->where('service_id', $this->service);
            })
            ->when($this->onlyuser, function ($q) {
                return $q->where('user_id', Auth()->User()->id);
            });

        if (!$this->status && $this->isDesignService()) {
            return $query
                ->where(function ($q) {
                    $q->where(function ($assignedQuery) {
                        $assignedQuery
                            ->where('completed', false)
                            ->where('status', 2);
                    })
                        ->orWhere('status', Production::STATUS_REJECTED_PROJECT_REVIEW);
                })
                ->count();
        }

        return $query
            ->where('completed', false)
            ->when($this->status, function ($q, $s) {
                return $q->where('status', $s);
            })
            ->count();
    }

    private function isDesignService(): bool
    {
        if (!$this->service) {
            return false;
        }

        return Service::where('uuid', $this->service)
            ->where('folder', 'desenho')
            ->exists();
    }

    public function render()
    {
        return view('livewire.components.count.countnotes', [
            'count' => $this->count,
        ]);
    }
}
