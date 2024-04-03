<?php

namespace App\Http\Livewire\Dispatchs;

use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnD5 extends Component
{
    public $service;

    public $companies;
    public $company_s;
    public $users;
    public $user_s;
    public $search;

    protected $listeners = [
        'refresh_list' => '$refresh'
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();

    }

    public function returnD5(Note $note, Reclaim $retorno)
    {
        // dd($note, $retorno);

        DB::beginTransaction();

        try {

            $production = Production::create([
                'note_id'     => $note->id,
                'service_id'  => $this->service->uuid,
                'company_id'  => $this->company_s,
                'dispatch_by' => Auth()->User()->id,
                'user_id'     => $this->user_s,
                'dt_note'     => $note->dt_status,
                'status_note' => $note->nstats,
                'centroTrab'  => $note->centerjob,
                'dispatch_at' => date('Y-m-d H:i:s'),
                'status'      => 2,
                'd5'          => true,
            ]);

            if ($production) {
                $retorno->update(['production_id' => $production->id]);

                if ($retorno->Viabilities->count()) {
                    foreach ($retorno->Viabilities as $viab) {
                        $viab->update([
                            'status' => 12
                        ]);
                    }
                }
            }

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'D5 Criada com sucesso!',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_list');

        } catch (\Throwable $th) {
            DB::rollback();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum erro ao tentar criar a D5! <br>'.$th->getMessage(),
                'timer'    => 2500,
            ]);

            return;
        }

    }

    public function getListsProperty()
    {
        $this->companies = Company::whereRelation('contracts', 'service', true)->orderBy('name')->get();

        $this->users = User::when($this->search, function ($q) {
            return $q->where('name', 'like', '%'.$this->search.'%');
        })
                ->whereRelation('Employee.Contract.company', 'id', $this->company_s)->orderBy('name')->get();



        return Reclaim::Where('service_id', $this->service->uuid)->where('completed', false)->with('Note.Files', 'Production', 'Comments')->paginate(50);
    }

    public function render()
    {





        return view('livewire.dispatchs.return-d5', [
            'lists' => $this->lists
        ]);
    }
}
