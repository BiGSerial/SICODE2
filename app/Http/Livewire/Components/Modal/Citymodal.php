<?php

namespace App\Http\Livewire\Components\Modal;

use App\Models\Edp_depc\City;
use App\Models\Note;
use Livewire\Component;

class Citymodal extends Component
{
    public $note;

    public $regiao;

    public $regiao_l;

    public $municipio;

    public $setMunicipio;

    protected $listeners = [
        'confirmEditCity' => 'confirmMunicipio',
        'editMunicipio'   => 'getNote',

    ];

    public function mount()
    {
        $this->regiao_l = $this->cities->get()->pluck('regiao')->unique()->sort();
    }

    public function getNote(Note $note)
    {
        if ($note) {
            $this->note = $note;

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'cityModal',
            ]);
        }
    }

    public function getCitiesProperty()
    {
        try {

            return City::Where('rdMunicipio', '!=', '1234')
                ->Where('rdMunicipio', '!=', '36744')
                ->When($this->regiao, function ($q) {
                    return $q->where('regiao', $this->regiao);
                })
                ->orderBy('cidade');

        } catch (\Exception $e) {

            return false;

        }
    }

    public function setMunicipio()
    {
        if ($this->note) {

            $this->setMunicipio = City::where('rdMunicipio', $this->municipio)->first();

            if ($this->note && $this->setMunicipio) {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Edição',
                    'msg'           => "Deseja confirmar o município {$this->setMunicipio->municipio} para Nota/OV {$this->note->note}",
                    'icon'          => 'warning',
                    'btnOktxt'      => 'Sim, Confirmar!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirmEditCity',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhum nota/ov foi editada com o municipio.',

                ]);
            }
        }
    }

    public function confirmMunicipio()
    {
        if ($this->note->update(['lexp' => $this->setMunicipio->municipio, 'nexp' => $this->setMunicipio->rdMunicipio])) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Municipio adicionado com sucesso.',
                'timer'    => 2500,
            ]);

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao tentar adicionar o município.',
                'timer'    => 3500,
            ]);
        }

        $this->note         = '';
        $this->setMunicipio = '';
        $this->regiao       = '';
        $this->municipio    = '';

        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_dispatch');
    }

    public function render()
    {
        return view('livewire.components.modal.citymodal', [
            'cities' => $this->cities->get(),
        ]);
    }
}
