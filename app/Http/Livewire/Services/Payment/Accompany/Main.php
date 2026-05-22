<?php

namespace App\Http\Livewire\Services\Payment\Accompany;

use App\Exports\Dispatchs\DispatchPaymentStack;
use App\Exports\Services\Payment\D5tolistExport;
use App\Exports\Services\ServicePaymentStack;
use App\Models\{File, Note, Production, Service, User};
use Carbon\Carbon;
use App\Helpers\TextFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\{Component, WithPagination};
use Maatwebsite\Excel\Concerns\Exportable;

class Main extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;

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


    public $advanceSearch;

    public $multiSearch = [];

    // Filters
    private $filter_group = 'payments_acc';
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

    public function export_excel()
    {
        return (new DispatchPaymentStack($this->lists, $this->service->uuid))->download(date('YmdHis-') . 'exportControlPayment.xlsx');
    }

    public function export_d5tolist()
    {
        return (new D5tolistExport($this->service, auth()->user()->id))->download(date('YmdHis-') . 'exportD5tolist.xlsx');
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch = [];
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

        $check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first();

        if ($check) {

            // $this->emit('open_analise_analise', ['productionId' => $check->id, 'noteId' => $check->note_id]);

            // $this->dispatchBrowserEvent('showModal', [
            //     'id' => 'analise_form',
            // ]);

            $this->emitTo('services.payment.forms.jobform', 'showProduction', $check);

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

    // public function go_to_analise()
    // {
    //     $this->emit('open_analise_analise', $this->analise);
    //     $this->dispatchBrowserEvent('showModal', [
    //         'id' => 'analise_form',
    //     ]);
    // }

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

        return Production::Where('service_id', $this->service->uuid)
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->leftJoinSub(
                DB::table('operation_resps')
                    ->select('note_id', DB::raw('MAX(fimLancado) as latest_fimLancado'))
                    ->groupBy('note_id'),
                'latest_operation_resps',
                'notes.id',
                '=',
                'latest_operation_resps.note_id'
            )
            ->when($this->multiSearch, function ($q) {
                $q->whereRelation('note', function ($sq) {
                    $sq->whereIn('note', $this->multiSearch)
                    ->orWhereRelation('Orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
                });

            })
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', false)
            ->when($this->search, function ($q, $s) {
                return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                    ->orwhereRelation('Note', 'material', 'like', '%' . $s . '%');
            })
            ->when(isset($this->filters['company']), function ($q, $s) {
                $q->whereIn('company_id', $this->filters['company']);
            })
            ->when(isset($this->filters['city']), function ($q, $s) {
                return $q->whereRelation('Note', function ($q) {
                    $q->whereIn('lexp', $this->filters['city']);
                });
            })
            ->when(isset($this->filters['rubrica']), function ($q, $s) {
                return $q->whereRelation('Note', function ($q) {
                    $q->whereIn('rubrica', $this->filters['rubrica']);
                });
            })
            ->with(['Note' => function ($query) {
                $query->orderBy('dt_status', 'asc');
            }])
            ->select('productions.*', 'notes.dt_created as note_dt_created', 'latest_operation_resps.latest_fimLancado as fimLancado')
            ->orderBy('priority', 'DESC')
            ->orderBy('d5', 'DESC')
            ->orderBy('partial', 'desc')
            ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
            ->orderBy('fimLancado', 'asc')
            ->orderBy('notes.type_note', 'DESC');

    }

    // Rules Days Left
    public function deadline(Note $note)
    {
        $days = 10;
        $date_forms = $note->WorkForm ? $note->WorkForm->informed_at : null;

        if ($date_forms) {

            $deadline_date = Carbon::parse($date_forms)->addDays($days);

            return Carbon::now()->diffInDays($deadline_date, false);
        } else {
            return 0;
        }
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.payment.accompany.main', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
