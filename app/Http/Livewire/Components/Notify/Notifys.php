<?php

namespace App\Http\Livewire\Components\Notify;

use App\Models\Notify;
use Livewire\Component;

class Notifys extends Component
{
    public $total_notifies = 4;

    public function getNotifyProperty()
    {
        return Notify::where('user_id', Auth()->User()->id)->where('readed', false)->orderBy('created_at', 'DESC')->get();
    }


    public function recognize_all()
    {
        $recognize = $this->notify;

        foreach ($recognize as $notify) {
            $notify->update(['readed' => true]);
        }
    }

    public function readed(Notify $notify)
    {
        try {
            $notify->update(['readed' => true]);

            redirect($notify->link);
        } catch (\Throwable $th) {

        }

    }

    public function render()
    {
        return view('livewire.components.notify.notifys', [
            'notifies' => $this->notify
        ]);
    }
}
