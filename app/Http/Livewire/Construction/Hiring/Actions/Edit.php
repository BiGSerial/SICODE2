<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Edit extends Component
{
    public ?Note $note = null;
    public $companies;
    public $users;

    public $user_s;
    public $company_s;

    protected $listeners = [
        'edit_hiring' => 'editHiring',
        'alter_viability',
    ];

    public function mount()
    {
        $this->users = User::where('engineer', true)->orderBy('name')->get();
        $this->companies = Company::orderBy('name')->get();
    }

    public function editHiring(Note $note)
    {
        $this->note = $note;

        if ($this->note) {

            $this->user_s = isset($this->note->Viabilities->last()->Engineer->id) ? $this->note->Viabilities->last()->Engineer->id : '';
            $this->company_s = isset($this->note->Viabilities->last()->Company->id) ? $this->note->Viabilities->last()->Company->id : '';

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_edit_hiring',
            ]);
        }
    }

    public function toAlterViability()
    {
        if ($this->user_s == '' || $this->company_s == '') {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'É PRECISO DEFINIR EMPRESA E RESPONSÁVEL PARA ALTERAÇÃO',
                'timer'    => 5000,
            ]);
            return;
        }

        $oldUser = isset($this->note->Viabilities->last()->Engineer->id) ? $this->note->Viabilities->last()->Engineer->name : null;
        $oldCompany = isset($this->note->Viabilities->last()->Company->id) ? $this->note->Viabilities->last()->Company->name : null;




        $newUser = User::find($this->user_s)->name;
        $newCompany = Company::find($this->company_s)->name;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => "ALTERAR VIABILIDADE",
            'msg'           => "
                <p>Deseja Alterar as informações de destino da viabilidade?</p>

                <div class='card'>
                    <table class='table table-sm'>
                        <thead>
                            <th class='text-center align-middle'>Empresa Origem</th>
                            <th class='text-center align-middle'></th>
                            <th class='text-center align-middle'>Empresa Destino</th>
                        </thead>
                        <tbody>
                            <tr>
                            <td class='text-center align-middle'>{$oldCompany}</td>
                            <td class='text-center align-middle'> => </td>
                            <td class='text-center align-middle'>{$newCompany}</td>
                            </tr>
                        </tbody>
                    </table>

                    <table class='table table-sm'>
                        <thead>
                            <th class='text-center align-middle'>Responsável Origem</th>
                            <th class='text-center align-middle'></th>
                            <th class='text-center align-middle'>Responsável Destino</th>
                        </thead>
                        <tbody>
                            <tr>
                            <td class='text-center align-middle'>{$oldUser}</td>
                            <td class='text-center align-middle'> => </td>
                            <td class='text-center align-middle'>{$newUser}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'alter_viability',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma obra teve a Viabilidade Alterada!',

        ]);

    }

    public function alter_viability()
    {
        if ($this->note->Viabilities->where('completed', false)->count()) {
            DB::beginTransaction();
            $error = false;
            foreach ($this->note->Viabilities->where('completed', false) as $viability) {
                try {

                    $viability->update([
                        'engineer_id' => $this->user_s,
                        'company_id' => $this->company_s,
                    ]);

                } catch (\Throwable $th) {
                    $error = true;
                    DB::rollback();
                    break;
                }
            }

            if (!$error) {

                DB::commit();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Alterado com sucesso!.',
                    'timer'    => 5000,
                ]);

                $this->closeAll();

                $this->emitUp('refresh_list');

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOPS! Algo deu errado.',
                    'timer'    => 5000,
                ]);
                return;
            }
        }
    }

    public function closeAll()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->user_s = null;
        $this->company_s = null;

    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.edit');
    }
}
