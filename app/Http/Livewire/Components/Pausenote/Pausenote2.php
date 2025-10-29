<?php

namespace App\Http\Livewire\Components\Pausenote;

use App\Models\{Note, Notetimeline, Production, Service};
use Livewire\Component;

class Pausenote2 extends Component
{
    public ?Production $production = null;
    public ?Notetimeline $pause = null;


    public $limite = 100;
    public $count = 0;
    public $info;
    public $category;

    protected $listeners = [
        'stop_note' => 'to_stopNote',
    ];

    /**
     * Undocumented function
     *
     * @param ['productionId' => $check->id, 'noteId' => $check->note_id] $data
     * @return void
     */
    public function to_stopNote(Production $production)
    {
        $this->count = Production::Where('service_id', $production->service_id)->where('user_id', Auth()->User()->id)->where('status', 4)->count();

        if ($this->count >= $this->limite) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'LIMITE ATINGIDO.',
                'html'     => 'Você atingiu o limite permitido para PAUSA. É necessario finalizar as anterirores.',
                'timer'    => 10000,
            ]);

            return;
        }

        $this->production = $production;

        if ($this->production) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => "pauseModal",
            ]);
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

        $this->production = null;
        $this->count      = 0;
        $this->info       = null;
    }

    public function close()
    {
        $this->clean();
        $this->dispatchBrowserEvent('hideModal');
        $this->dispatchBrowserEvent('hideModal');
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_list');
        $this->emitTo('services.publication.accompany.main', 'refresh_list');
        $this->emit('refresh_accomany');
    }



    public function render()
    {
        return view('livewire.components.pausenote.pausenote2');
    }
}
