<?php

namespace App\Http\Livewire\Services\Analises_pre;

use App\Custom\Notestatus;
use App\Custom\RuleBuilder;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Carbon\Carbon;
use Livewire\{Component, WithPagination};

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

    public $advanceSearch;

    public $multiSearch = [];

    public $selectAll = false;

    public $selected = [];

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $assigned_mmgd = false;

    protected $listeners = [
        'refresh_service'   => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_accompany_mass' => 'add_to_accompany_mass',
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        if (!session()->isStarted()) { session()->start(); }

        if (isset($_SESSION['filtro']['rubrica']) && $_SESSION['filtro']['rubrica']) {
            $this->rubrica_s = $_SESSION['filtro']['rubrica'];
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function filterMMGD()
    {
        if ($this->assigned_mmgd) {
            $this->assigned_mmgd = false;
        } else {
            $this->assigned_mmgd = true;
        }

    }

    public function to_accompany(Note $note)
    {
        $this->note = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Atribuir Tarefa',
            'msg'   => "
            Você deseja atribuir a NOTA/OV para você?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p><strong>NOTA/OV estará disponível em acompanhamento como
            sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',

        ]);
    }

    public function add_to_accompany()
    {
        $user = User::with('Employee.Contract')->find(Auth()->User()->id);
        $result = $this->assignNoteToCurrentUser($this->note, $user);

        if ($result['ok']) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => "{$this->note->note} foi atribuído a você com sucesso.",
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO',
                'html'     => $result['message'],
            ]);
        }
    }

    public function go_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota selecionada',
                'timer'    => 2500,
            ]);

            return;
        }

        $count = count($this->selected);
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Atribuir em massa',
            'msg'           => "Você deseja atribuir <strong>{$count}</strong> Nota(s)/OV(s) para você?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany_mass',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',
        ]);
    }

    public function add_to_accompany_mass()
    {
        $user = User::with('Employee.Contract')->find(Auth()->User()->id);
        $notes = Note::whereIn('id', $this->selected)->get();

        $success = 0;
        $errors = [];

        foreach ($notes as $note) {
            $result = $this->assignNoteToCurrentUser($note, $user);
            if ($result['ok']) {
                $success++;
            } else {
                $errors[] = "{$note->note}: {$result['plain']}";
            }
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->emit('refresh_service');

        if (count($errors)) {
            $msg = implode('<br>', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $msg .= '<br>...';
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => $success ? 'warning' : 'error',
                'title'    => "Atribuição concluída ({$success} sucesso, " . count($errors) . ' falha)',
                'html'     => $msg,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "Atribuição em massa concluída ({$success})",
            'timer'    => 2500,
        ]);
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->gotoPage(1);
            $this->search = '';
            $this->multiSearch = preg_split('/[\s,;]+/', trim($this->advanceSearch), -1, PREG_SPLIT_NO_EMPTY);
            $this->multiSearch = array_map('trim', $this->multiSearch);
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch = [];
        }
    }

    public function setSelectAllFiltered()
    {
        $ids = $this->baseQuery()->pluck('id')->toArray();

        if ($this->selectAll) {
            foreach ($ids as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            $this->selected = array_values(array_diff($this->selected, $ids));
        }
    }

    public function checkAllSelect()
    {
        $ids = $this->baseQuery()->pluck('id')->toArray();
        if (!count($ids)) {
            $this->selectAll = false;

            return false;
        }

        $this->selectAll = empty(array_diff($ids, $this->selected));

        return $this->selectAll;
    }

    public function filter_save()
    {

        if (!session()->isStarted()) { session()->start(); }
        $_SESSION['filtro']['rubrica'] = $this->rubrica_s;
        $this->emit('refresh_service');

    }

    public function filter_clean()
    {
        $this->rubrica_s = [];
        $this->advanceSearch = null;
        $this->multiSearch = [];
        $this->selected = [];
        $this->selectAll = false;
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

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

    private function baseQuery()
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $this->service->Status);

        if (count($this->multiSearch)) {
            $query->where(function ($q) {
                $q->whereIn('note', $this->multiSearch)
                    ->orWhereIn('numPedido', $this->multiSearch);
            });
        }

        $query->when($this->search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%');
                // ->orWhere('material', 'like', '%' . $s . '%')
                // ->orWhere('numPedido', 'like', '%' . $s . '%');
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
                $query->whereIn('rubrica', $this->rubrica_s)
                    ->orWhereNull('rubrica');
            });
        })
            ->when($this->assigned_mmgd, function ($q) {
                return $q->whereIn('num_material', [21, 78, 79]);
            })
            ->with('Productions.User')
            ->orderBy('pze_parecer', 'DESC')
            ->orderBy('dt_created');

        return $query;
    }

    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.analises_pre.main', [
            'lists'  => $this->lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }

    private function assignNoteToCurrentUser(Note $note, User $user): array
    {
        $check = Production::where('note_id', $note->id)
            ->where('dt_note', $note->dt_status)
            ->where('service_id', $this->service->uuid)
            ->where(function ($q) {
                $q->where('completed_at', '>', Carbon::now()->subHours(24))
                    ->orWhere('completed', false);
            })->first();

        if ($check) {
            $check->loadMissing(['User', 'Company', 'Service']);

            $name = $check->User?->name ?? ($check->Company ? "{$check->Company->name} (sem usuário atribuído)" : 'Desconhecido');
            $status = Notestatus::status($check->status)->status;

            return [
                'ok' => false,
                'plain' => "já está {$status} por {$name}",
                'message' => "<p><strong>{$note->note}</strong> está sinalizada como <strong>{$status}</strong> em <strong>{$check->Service->service}</strong> por <strong>{$name}</strong></p>
                    <p class='text-bg-info p-2 mt-2'>Verifique com um gestor para atribuir manualmente esta NOTA/OV caso considere um erro atribuição.</p>",
            ];
        }

        $production = Production::Create([
            'note_id'     => $note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->Employee->Contract->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->nstats,
            'dispatch_at' => date('Y-m-d H:i:s'),
            'att_at'      => date('Y-m-d H:i:s'),
            'status'      => 2,
            'dhstats'     => $note->dt_status,
        ]);

        if (!$production) {
            return [
                'ok' => false,
                'plain' => 'erro ao criar produção',
                'message' => "Erro ao tentar atribuir {$note->note}.",
            ];
        }

        Notetimeline::Create([
            'note_id'      => $note->id,
            'service_id'   => $production->service_id,
            'user_id'      => Auth()->User()->id,
            'info'         => "Usuário {$user->name} atribuiu a Nota/OV.",
            'status'       => 2,
            'productionId' => $production->id,
        ]);

        return ['ok' => true, 'plain' => '', 'message' => ''];
    }
}
