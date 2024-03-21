<?php

namespace App\Http\Livewire\Monitor\Transfer;

use App\Models\Prodtransfer;
use Livewire\Component;

class Transferlist extends Component
{
    public $user_s;

    protected $listeners = [
        'refreshServiceList' => '$refresh',
        'searchUser'         => 'selUSer',
    ];

    public function selUSer($user)
    {
        // dd($user, 'Trans');

        $this->user_s = $user;
    }

    public function getTransferProperty()
    {
        return Prodtransfer::whereRelation('Production', 'completed', false)
            ->when($this->user_s, function ($q) {
                return $q->where(function ($sq) {
                    return $sq->where('to', $this->user_s)
                        ->orWhere('from', $this->user_s);
                });
            })
            ->orderBy('updated_at', 'DESC')
            ->with('To', 'From', 'Production.Note')
            ->get();
    }

    public function render()
    {
        return view('livewire.monitor.transfer.transferlist', [
            'live_transfer' => $this->transfer,
        ]);
    }
}
