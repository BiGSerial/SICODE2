<?php

namespace App\Http\Livewire\Audits;

use App\Models\Audit;
use Livewire\Component;

class Info extends Component
{
    public $auditprod;

    protected $listeners = [
        'audit_prod' => 'audit_prod',
    ];

    public function audit_prod($prod)
    {
        $this->auditprod = '';

        $this->auditprod = Audit::where('after->id', $prod['production_id'])->where('after->service_id', $prod['service_id'])->with('User')->orderBy('created_at')->get();

        if ($this->auditprod) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'audit_info',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.audits.info');
    }
}
