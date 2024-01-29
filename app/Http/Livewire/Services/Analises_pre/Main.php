<?php

namespace App\Http\Livewire\Services\Analises_pre;

use App\Custom\RuleBuilder;
use App\Models\Bancoupdate;
use App\Models\Note;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 100;
    public $search;
    public $rubrica_s = [];
    public $rubrica_l;
    public $note;
    public $last_update;

    //Botão de  nao atribuído.
    public $not_assigned = false;


    protected $listeners = [
        'refresh_service' => '$refresh',
        'getCopy' => 'copy',
        'confirm_accompany' => 'add_to_accompany'
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;


        session_start();
        if (isset($_SESSION['filtro']['rubrica']) && $_SESSION['filtro']['rubrica']) {
            $this->rubrica_s = $_SESSION['filtro']['rubrica'];
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status' => 'success',
            'menssage' => $msg,
        ]);
    }

    public function to_accompany(Note $note)
    {
        $this->note = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title' =>  'Atribuir Tarefa',
            'msg' => "
            Você deseja atribuir a NOTA/OV para você?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p><strong>NOTA/OV estará disponível em acompanhamento como
            sua tarefa e nenhum outro usuário poderá atribuir pra si.</p> 
            </div>
            </div>  
            ",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Atribua!',
            'btnCanceltxt' => 'Não, Cancele!',
            'action' => "confirm_accompany",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhum serviço foi atribuído.',

        ]);
    }

    public function add_to_accompany()
    {
        $user = User::with('Employee.Contract')->find(Auth()->User()->id);

        $check = Production::where('note_id', $this->note->id)->where(function ($q) {
            return $q->where('completed', false)
                    ->orWhere('dt_note', $this->note->dt_status);
        })->with('User', 'Service')->first();

        if($check) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => "OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO",
                'html' => "<strong>{$this->note->note}</strong> foi ou está em Tratamento em {$check->Service->service} por <strong>{$check->User->name}</strong>",

            ]);

            return;
        }

        $production = Production::Create([
            'note_id' => $this->note->id,
            'service_id' => $this->service->uuid,
            'user_id' => $user->id,
            'company_id' => $user->Employee->Contract->company_id,
            'dispatch_by' => $user->id,
            'att_by' => $user->id,
            'dt_note' => $this->note->dt_status,
            'status_note' => $this->note->nstats,
            'dispatch_at' => date('Y-m-d H:i:s'),
            'att_at' => date('Y-m-d H:i:s'),
            'status' => 2,
            'dhstats' => $this->note->dt_status,
        ]);

        if($production) {

            Notetimeline::Create([
                'note_id' => $this->note->id,
                'service_id' => $production->service_id,
                'user_id' => Auth()->User()->id,
                'info' => "Usuário {$user->name} atribuiu a Nota/OV.",
                'status' => 2,
                'productionId' => $production->id,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => "{$this->note->note} foi atribuído a você com sucesso.",
                'timer' => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => "Erro ao tentar atribuir {$this->note->note}.",
                'timer' => 2500,
            ]);
        }
    }

    public function filter_save()
    {

        session_start();
        $_SESSION['filtro']['rubrica'] = $this->rubrica_s;
        $this->emit('refresh_service');

    }

    public function filter_clean()
    {
        $this->rubrica_s = [];
        session_start();
        if (isset($_SESSION['filtro'])) {
            unset($_SESSION['filtro']);
        }
        $this->emit('refresh_service');
    }

    public function filterStatus()
    {
        if ($this->not_assigned) {
            $this->not_assigned = false;
        } else {
            $this->not_assigned = true;
        }

    }

    public function getListsProperty()
    {
        $query = Note::query();

        RuleBuilder::applyRules($query, $this->service->Status);


        $query->when($this->search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('material', 'like', '%' . $s . '%')
                    ->orWhere('numPedido', 'like', '%' . $s . '%');
            });
        });

        if ($this->not_assigned) {
            $query->where(function ($q) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) {
                        $subquery->where('service_id', $this->service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }



        $query->when($this->rubrica_s, function ($q) {
            return $q->where(function ($query) {
                $query->where('rubrica', $this->rubrica_s)
                      ->orWhereNull('rubrica');
            });
        })
                ->with('Productions.User')
                ->orderBy('pze_parecer', 'DESC')
                ->orderBy('dt_created');

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.analises_pre.main', [
            'lists' => $this->lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first()
        ]);
    }
}
