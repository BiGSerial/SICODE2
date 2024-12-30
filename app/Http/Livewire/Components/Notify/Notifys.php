<?php

namespace App\Http\Livewire\Components\Notify;

use App\Models\Notify;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Notifys extends Component
{
    public $total_notifies = 4;

    public function getNotifyProperty()
    {
        return Notify::where('user_id', Auth()->User()->id)->orderBy('created_at', 'DESC')->limit(10)->get();
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
            if ($notify->status === 4) {
                $notify->update(['readed' => true]);

                if (Storage::disk('public')->exists($notify->link)) {

                    $file = explode('/', $notify->link);

                    return response()->streamDownload(function () use ($notify) {
                        echo Storage::disk('public')->get($notify->link);
                    }, $file[1]);

                } else {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'ARQUIVO INEXISTENTE!',
                        'timer'    => 5000,
                    ]);
                    return;
                }
            } else {

                $notify->update(['readed' => true]);

                if ($notify->link == null) {
                    return;
                }

                redirect($notify->link);

            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function render()
    {
        return view('livewire.components.notify.notifys', [
            'notifies' => $this->notify,
        ]);
    }
}
