<?php

namespace App\Http\Livewire\Components\Pausenote;

use App\Models\{Note, Notetimeline, Production, Service};
use Livewire\Component;

class Pausenote extends Component
{
    public $show_pause = false;

    public $note;

    public $production;

    public $service;

    public $count;

    public $limit_pause = 5000;

    public $info;

    protected $listeners = [
        'stop_note' => 'to_stopNote',
    ];

    /**
     * Undocumented function
     *
     * @param ['productionId' => $check->id, 'noteId' => $check->note_id] $data
     * @return void
     */
    public function to_stopNote($data)
    {
        if (isset($data['limit']) && $data['limit']) {
            $this->limit_pause = $data['limit'];
        }

        $this->note       = Note::find($data['noteId']);
        $this->production = Production::find($data['productionId']);
        $this->count      = Production::Where('status', 4)->Where('service_id', $this->production->service_id)->Where('user_id', Auth()->User()->id)->count();

        if ($this->production && $this->note) {
            $this->service    = Service::Where('uuid', $this->production->service_id)->first();
            $this->show_pause = true;
        }
    }

    public function go_pause()
    {
        if (strlen(trim($this->info)) < 3) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÃO OBRIGATÓRIA.',
                'html'     => '<strong> (MOTIVO) </strong> A informação do motivo é obrigatório. Seja Claro e Objetivo.',
                'timer'    => 10000,
            ]);

            return;
        }

        $chk = $this->production->update([
            'status' => 4,
        ]);

        if ($chk) {
            $hist = Notetimeline::Create([
                'note_id'       => $this->production->note_id,
                'service_id'    => $this->production->service_id,
                'user_id'       => Auth()->User()->id,
                'info'          => $this->info,
                'status'        => $this->production->status,
                'production_id' => $this->production->id,
            ]);

            $this->clean();
            $this->close();
        }
    }

    public function clean()
    {
        $this->show_pause = false;
        $this->note       = null;
        $this->production = null;
        $this->service    = null;
        $this->count      = 0;
        $this->info       = null;
    }

    public function close()
    {
        $this->clean();
        $this->dispatchBrowserEvent('hideModal');
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_accomany');
    }

    public function render()
    {
        return view('livewire.components.pausenote.pausenote');
    }
}
