<?php

namespace App\Http\Livewire\Components\Status;

use App\Models\Notetimeline;
use Livewire\Component;

class Statusview extends Component
{
    public int $status;

    public $idstatus;

    public $note_id;

    public $info2;

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    // public function mount($status, $idstatus, $note_id)
    // {
    //     $this->status = $status;
    //     $this->idstatus = $idstatus;
    //     $this->note_id = $note_id;
    // }

    public function open_status()
    {
        $this->info2 = Notetimeline::where('note_id', $this->note_id)->where('status', $this->status)->orderBy('created_at', 'DESC')->with('User')->first();

        if ($this->info2) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'view_status-' . $this->idstatus,
            ]);
        }
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.components.status.statusview', [
            'info' => $this->info2,
        ]);
    }
}
