<?php

namespace App\Http\Livewire\Services\Desenho;

use App\Models\{File, Note, Production, Service, User};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public $note_type;

    public $limit_pause = 3;

    public $analise;

    public $user_l;

    public $user_s;

    public $user_search;

    public $production;

    public $note;

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function visualizar()
    {

    }

    public function goTransferProd($prod_id)
    {
        $this->emit('transfer_production', $prod_id);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }
    }

    public function checkOpen()
    {

        $check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first();

        if ($check) {

            $this->emit('open_analise_draw', ['productionId' => $check->id, 'noteId' => $check->note_id]);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                        adequada.
                    </p>
                ",
            ]);

        }

    }

    public function go_to_analise()
    {
        $this->emit('open_analise_draw', $this->analise);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_form',
        ]);
    }

    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        if ($this->limit_pause === Production::Where('status', 4)->Where('service_id', $this->service->uuid)->Where('user_id', Auth()->User()->id)->count() && (Production::find($production))->status != 4) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você ja atingiu o limite de pausa neste serviço, ao iniciar esta nota, você não poderá colocar esta NOTA/OV em espera. \n Tem certeza que deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
            $this->emit('open_analise_draw', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    public function filter_save()
    {
        // session()->put('filtro', $this->rubrica_s);
        // session_start();
        // $_SESSION['filtro'] = $this->rubrica_s;
        $this->emit('refresh_service');

    }

    public function filter_clean()
    {
        $this->rubrica_s = [];

        // session_start();
        // if (isset($_SESSION['filtro'])) {
        //     unset($_SESSION['filtro']);
        // }

        $this->emit('refresh_service');
    }

    public function getListsProperty()
    {

        $this->user_l = User::when($this->user_search, function ($q) {
            return $q->where('name', 'like', '%' . $this->user_search . '%');
        })->orderBy('name')->get();

        // return Production::Where('service_id', $this->service->uuid)

        //                 ->where('user_id', Auth()->User()->id)
        //                 ->where('completed', false)
        //                 ->when($this->search, function ($q, $s) {
        //                     return $q->where(function ($query) use ($s) {
        //                         $query->whereRelation('Note', 'note', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'material', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'group1', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'group2', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'group3', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'group4', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'group5', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'lexp', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'rubrica', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'numPedido', 'like', '%'.$s.'%')
        //                             ->orWhereRelation('Note', 'centerjob', 'like', '%'.$s.'%');
        //                     });
        //                 })
        //                 ->when($this->note_type, function ($q) {
        //                     return $q->whereRelation('Note', 'type_note', $this->note_type);
        //                 })
        //                 ->with(['Note' => function ($query) {
        //                     $query->orderBy('dt_status', 'asc');
        //                 }])
        //                 ->orderBy('priority', 'DESC')

        //                 ->paginate($this->perPage);

        return Production::where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('completed', false)
            ->when($this->search, function ($q, $s) {
                return $q->where(function ($query) use ($s) {
                    $query->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'material', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group1', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group2', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group3', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group4', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group5', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'lexp', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'rubrica', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'numPedido', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'centerjob', 'like', '%' . $s . '%');
                });
            })
            ->when($this->note_type, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    $query->where('type_note', $this->note_type);
                });
            })
            ->with(['Note' => function ($query) {
                $query->orderBy('dt_status', 'asc')
                    ->orderBy('type_note', 'desc');
            }])
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('notes.type_note', 'desc')
            ->orderBy('notes.days_left', 'asc')
            ->orderBy('productions.id', 'asc')
            ->select('productions.*', 'notes.dt_status', 'notes.is45', 'notes.type_note', 'notes.days_left')
            ->paginate($this->perPage);

    }

    public function render()
    {
        return view('livewire.services.desenho.main', [
            'lists' => $this->lists,
        ]);
    }
}
