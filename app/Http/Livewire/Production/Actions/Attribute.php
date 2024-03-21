<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\{Company, Note, Production, Service, User, Wpa};
use Carbon\Carbon;
use Livewire\Component;

class Attribute extends Component
{
    public $production;

    public $chave;

    public $dd;

    public $selected = [];

    public $service;

    public $company_l;

    public $company_s;

    public $user_l;

    public $user_s;

    public $additionalData = [];

    public $notes;

    public $alter_dd_wpa;

    public $listeners = [
        'confirm_alter_dd' => 'confirmed_alter_dd',
        'confirm_att'      => 'confirmed_att',
    ];

    public function mount(Production $production, $chave, $dd = false)
    {
        $this->production = $production;
        $this->chave      = $chave;
        $this->dd         = $dd;
        $this->service    = Service::find($this->production->service_id);

        $this->user_l    = User::with('Employee.Contract')->orderBy('name')->get();
        $this->company_l = Company::orderBy('name')->get();
    }

    public function get_single_note()
    {
        if ($this->dd) {
            $chk_dd = Wpa::where('production_id', $this->production->id)->orderBy('created_at', 'DESC')->first();

            if ($chk_dd) {
                $this->additionalData[0] = $chk_dd->dd;
            }
        }

        $this->notes = Note::where('id', $this->production->note_id)->get();

        if ($this->production) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'att_' . $this->chave,
            ]);
        }
    }

    public function go_att($chave)
    {
        if ($chave !== $this->chave) {
            return;
        }

        if ($this->dd) {
            $check = Wpa::Where('dd', trim($this->additionalData[0]))->first();

            if ($check && $check->note_id !== $this->production->note_id) {
                if ($this->production->completed) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'DD JÁ UTILIZADA',
                        'html'     => "A DD <strong>{$this->check->dd}</strong> JA FOI UTILIZADA E NÃO PODERÁ SER ASSOCIADA NESTA NOTA/OV.",
                        'timer'    => 5000,
                    ]);

                    return;

                } elseif (Carbon::now()->diffInMinutes($check->created_at) < 60 && $this->production->user_id === null) {
                    $this->dispatchBrowserEvent('alertar', [
                        'title'         => 'Confirmar Alterar DD',
                        'msg'           => "A DD <strong>{$this->check->dd}</strong>, foi atribuída recentemente a outra nota, você deseja alterar para esta nota?",
                        'icon'          => 'warning',
                        'btnOktxt'      => 'Sim, Altere!',
                        'btnCanceltxt'  => 'Não, Cancele',
                        'action'        => 'confirm_alter_dd',
                        'chave'         => $this->chave,
                        'cancel_titulo' => 'Cancelado!',
                        'cancel_msg'    => 'Nenhuma nota DD foi atribuída.',

                    ]);

                    $this->alter_dd_wpa = $check;

                    return;
                }
            } else {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Atrinuição',
                    'msg'           => "Deseja atribuir a Nota <strong>{$this->production->Note->note}</strong>?",
                    'icon'          => 'warning',
                    'btnOktxt'      => 'Sim, Atribua!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'confirm_att',
                    'chave'         => $this->chave,
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhuma nota foi atribuída.',

                ]);
            }
        }

    }

    public function confirmed_alter_dd($chave)
    {
        if ($chave !== $this->chave) {
            return;
        }

        if ($this->alter_dd_wpa->update([
            'production_id' => $this->production->id,
            'note_id'       => $this->production->note_id,
        ])) {
            $this->confirmed_att($this->chave);
        }
    }

    public function confirmed_att($chave)
    {
        dd($chave, $this->chave);
    }

    public function render()
    {
        $users_list = $this->user_l->filter(function ($usuario) {

            return $usuario->Employee->Contract->where('company_id', $this->company_s) ? $usuario->Employee->Contract->where('company_id', $this->company_s) : false;

        });

        return view('livewire.production.actions.attribute', [
            'users' => $users_list,
        ]);
    }
}
