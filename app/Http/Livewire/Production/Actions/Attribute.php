<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\{Company, Note, Production, Service, User, Wpa};
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

        $this->user_l    = User::with('Company')->orderBy('name')->get();
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
        if ($chave !== $this->chave || !$this->dd) {
            return;
        }

        $dd = trim((string) ($this->additionalData[0] ?? ""));
        $check = $dd !== "" ? Wpa::where("dd", $dd)->where("note_id", "!=", $this->production->note_id)->first() : null;

        if ($check) {
            $this->dispatchBrowserEvent("swal", [
                "position" => "center",
                "icon" => "warning",
                "title" => "DD ja utilizada",
                "html" => "A DD <strong>{$dd}</strong> ja esta associada a outra Nota/OV.",
                "timer" => 5000,
            ]);
            return;
        }

        $this->dispatchBrowserEvent("alertar", [
            "title" => "Confirmar Atribuicao",
            "msg" => "Deseja atribuir a DD a Nota <strong>{$this->production->Note->note}</strong>?",
            "icon" => "warning",
            "btnOktxt" => "Sim, Atribua!",
            "btnCanceltxt" => "Nao, Cancele",
            "action" => "confirm_att",
            "chave" => $this->chave,
            "cancel_titulo" => "Cancelado!",
            "cancel_msg" => "Nenhuma nota foi atribuida.",
        ]);
    }

    public function confirmed_alter_dd($chave)
    {
        if ($chave !== $this->chave) {
            return;
        }

        $this->dispatchBrowserEvent("swal", ["position" => "center", "icon" => "warning", "title" => "DD ja associada a outra Nota/OV.", "timer" => 5000]);
    }
    public function confirmed_att($chave)
    {
        if ($chave !== $this->chave) {
            return;
        }

        try {
            $this->production->loadMissing("Note");
            app(\App\Services\Dispatch\DdAssignmentService::class)->assign(
                $this->production->Note,
                $this->production,
                $this->additionalData[0] ?? null
            );
            $this->production->update(["block_wpa" => false]);
            $this->dispatchBrowserEvent("swal", ["position" => "center", "icon" => "success", "title" => "DD atribuída com sucesso.", "timer" => 2500]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatchBrowserEvent("swal", ["position" => "center", "icon" => "error", "title" => $e->getMessage(), "timer" => 5000]);
        }
    }

    public function render()
    {
        $users_list = $this->user_l->filter(function ($usuario) {

            return (string) $usuario->company_id === (string) $this->company_s;

        });

        return view('livewire.production.actions.attribute', [
            'users' => $users_list,
        ]);
    }
}
