<?php

namespace App\Http\Livewire\Services\Publication\Accompany;

use App\Models\{File, Note, Production, Service, User};
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

    public $limit_pause = 3;

    public $analise;

    public $user_l;

    public $user_s;

    public $user_search;

    public $production;

    public $note;

    public $typeNote;

    // Filters
    private $filter_group = 'publication_acc';
    public $filters;

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'refresh_list'       => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
        'checkOpen'
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filtro']['analise']['rubrica']) && $_SESSION['filtro']['analise']['rubrica']) {
            $this->rubrica_s = $_SESSION['filtro']['analise']['rubrica'];
        }


    }

    public function blockWaiting($status)
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['waitingForm']) && $status != 27) {
            return true;
        } else {
            return false;
        }
    }

    public function showForm(Production $production)
    {
        if ($production->Note->RamalForm) {
            $this->emitTo('btzero.view.compare-form', 'showCompareForm', $production->Note);
        } elseif ($production->Note->WorkForm) {
            $this->emitTo('partner.show.show-work-form', 'show_form', $production->Note->WorkForm);
        }
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

    public function checkOpen()
    {

        // $check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first();

        if ($check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first()) {

            // $this->emit('open_analise_analise', ['productionId' => $check->id, 'noteId' => $check->note_id]);

            // $this->dispatchBrowserEvent('showModal', [
            //     'id' => 'analise_form',
            // ]);

            $this->emitTo('services.publication.forms.jobform', 'showProduction', $check);

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
        $this->emit('open_analise_analise', $this->analise);
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
            $this->emit('open_analise_analise', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    public function filter_save()
    {

        // if (!(session_status() == PHP_SESSION_ACTIVE)) {
        //     if (!session()->isStarted()) { session()->start(); }
        // }
        // session()->put('filtro', $this->rubrica_s);
        // if (!session()->isStarted()) { session()->start(); }
        // $_SESSION['filtro'] = $this->rubrica_s;
        $this->emit('refresh_service');
    }

    public function visualizar()
    {
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function filter_clean()
    {
        $this->rubrica_s = [];

        // if (!session()->isStarted()) { session()->start(); }
        // if (isset($_SESSION['filtro'])) {
        //     unset($_SESSION['filtro']);
        // }

        $this->emit('refresh_service');
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];
        }

        return Production::where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', auth()->id());
            })
            ->where('completed', false)
            ->when($this->typeNote, function ($q) {
                $q->whereRelation('Note', 'type_note', $this->typeNote);
            })
            ->when($this->search, function ($q, $s) {
                return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'material', 'like', '%' . $s . '%');
            })
            ->when(isset($this->filters['city']), function ($q) {
                $q->whereRelation('Note', 'lexp', $this->filters['city']);
            })
            ->when(isset($this->filters['rubrica']), function ($q) {
                $q->whereRelation('Note', 'rubrica', $this->filters['rubrica']);
            })
            ->where(function ($q) {
                $q->where('productions.status', '!=', 28)
                  ->orWhereHas('Note.WorkForm');
            })
            ->whereDoesntHave('Note.RamalForm', function ($q) {
                $q->where('rejected', true);
            })
            ->select('productions.*', 'notes.is45')
            ->selectRaw("
                CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM ramal_reports
                        WHERE ramal_reports.note_id = productions.note_id
                    ) > 0
                    AND (
                        SELECT COUNT(*)
                        FROM work_reports
                        WHERE work_reports.note_id = productions.note_id
                    ) = 0
                    THEN 1
                    ELSE 0
                END as forms
            ")
            ->join('notes', 'notes.id', '=', 'productions.note_id')
            ->orderBy('priority', 'desc')
            ->orderBy('notes.is45', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('forms', 'desc')
            ->paginate($this->perPage);
    }

    public function getWaitingsProperty()
    {

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];
        }

        return Production::where('service_id', $this->service->uuid)
        ->when($this->user_s, function ($q) {
            return $q->where('user_id', $this->user_s);
        }, function ($q) {
            return $q->where('user_id', auth()->id());
        })
        ->where('completed', false)
        ->when($this->typeNote, function ($q) {
            $q->whereRelation('Note', 'type_note', $this->typeNote);
        })
        ->when($this->search, function ($q, $s) {
            return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                     ->orWhereRelation('Note', 'material', 'like', '%' . $s . '%');
        })
        ->when(isset($this->filters['city']), function ($q) {
            $q->whereRelation('Note', 'lexp', $this->filters['city']);
        })
        ->when(isset($this->filters['rubrica']), function ($q) {
            $q->whereRelation('Note', 'rubrica', $this->filters['rubrica']);
        })
        ->where(function ($q) {

            $q->where('status', 28)
            ->whereDoesntHave('Note.WorkForm');

        })
        ->select('productions.*')
        ->selectRaw("
            CASE
                WHEN (
                    SELECT COUNT(*)
                    FROM ramal_reports
                    WHERE ramal_reports.note_id = productions.note_id
                ) > 0
                AND (
                    SELECT COUNT(*)
                    FROM work_reports
                    WHERE work_reports.note_id = productions.note_id
                ) = 0
                THEN 1
                ELSE 0
            END as forms
        ")
        ->orderBy('priority', 'desc')
        ->orderBy('d5', 'desc')
        ->orderBy('forms', 'desc')


        ->paginate($this->perPage);

    }

    public function sendCopyToExcel()
    {
        $formattedData = $this->lists->map(function ($list) {
            return [
            $list->Note->note,
            $list->Note->orders->pluck('ordem')->toArray(),
            ];
        });



        $this->dispatchBrowserEvent('copyToExcel', [
            'lists' => $formattedData
        ]);
    }

    public function render()
    {


        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.publication.accompany.main', [
            'lists' => $this->lists,
            'waitings' => $this->waitings,
        ]);
    }
}
