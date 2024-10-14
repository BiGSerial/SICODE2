<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use ZipArchive;

class Edit extends Component
{
    public ?Viability $viability = null;
    public $companies;
    public $users = [];
    public $rehiring = false;
    public $newsend = false;

    public $user_s;
    public $company_s;

    protected $listeners = [
        'edit_hiring' => 'editHiring',
        'alter_viability',
    ];

    public function mount()
    {
        $this->users = User::where('responsible', true)->select('id', 'name')->orderBy('name')->get();
        $this->companies = Company::orderBy('name')->get();
    }

    public function updatedCompanyS($company_s)
    {
        $this->users = User::whereHas('Companies', function ($query) use ($company_s) {
            $query->where('companies.id', $company_s);
        })
        ->where('users.responsible', true)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();
    }

    public function recontratar()
    {
        if ($this->rehiring) {
            $this->rehiring = false;
        } else {
            $this->rehiring = true;
        }
    }

    public function editHiring(Viability $viability)
    {
        $this->viability = $viability;

        if ($this->viability) {



            $this->user_s = isset($this->viability->Engineer->id) ? $this->viability->Engineer->id : '';
            $this->company_s = isset($this->viability->Company->id) ? $this->viability->Company->id : '';

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

        $oldUser = isset($this->viability->Engineer->id) ? $this->viability->Engineer->name : null;
        $oldCompany = isset($this->viability->Company->id) ? $this->viability->Company->name : null;




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

        if (!$this->viability->completed) {

            DB::beginTransaction();

            try {

                $this->viability->update([
                    'engineer_id' => $this->user_s,
                    'company_id' => $this->company_s,
                ]);

                DB::commit();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Alterado com sucesso!.',
                    'timer'    => 5000,
                ]);

                $this->closeAll();

                $this->emitUp('refresh_list');


            } catch (\Throwable $th) {

                DB::rollback();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOPS! Algo deu errado.',
                    'timer'    => 5000,
                ]);

                return;
            }

        } elseif ($this->rehiring) {

            DB::beginTransaction();

            try {

                $this->viability->update([
                    'engineer_id' => $this->user_s,
                    'company_id' => $this->company_s,
                    'rehired' => $this->rehiring,
                    'sended_at' => $this->newsend ? date('Y-m-d H:i:s') : $this->viability->sended_at,
                    'tacit' => $this->newsend ? false : $this->viability->tacit,
                    'approved' => $this->newsend ? false : $this->viability->approved,
                    'rejected' => $this->newsend ? false : $this->viability->rejected,
                    'status' => $this->newsend ? 1 : $this->viability->status,
                    'tacit_at' => $this->newsend ? null : $this->viability->tacit_at,
                    'completed_at' => $this->newsend ? null : $this->viability->completed_at,
                    'replica' => $this->newsend ? false : $this->viability->replica,
                    'treplica' => $this->newsend ? false : $this->viability->treplica,
                    'inActivity' => $this->newsend ? false : $this->viability->inActivity,
                    'returned_at' => $this->newsend ? null : $this->viability->returned_at,

                ]);

                DB::commit();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Alterado com sucesso!.',
                    'timer'    => 5000,
                ]);

                $this->closeAll();

                $this->emitUp('refresh_list');


            } catch (\Throwable $th) {
                DB::rollback();

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

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }



    public function closeAll()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->user_s = null;
        $this->company_s = null;
        $this->rehiring = false;
        $this->newsend = false;

    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.edit');
    }
}
