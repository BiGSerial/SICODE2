<?php

namespace App\Http\Livewire\Dispatchs\Payment;

use App\Custom\RuleBuilder;
use App\Exports\Dispatchs\DispatchPaymentMain;
use App\Models\Edp_depc\City;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User};
use App\Services\Payment\NoteFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

    public $selectall;

    public $selected = [];

    public $company_l;

    public $company_s;

    public $user_l;

    public $user_s;

    public $type;

    public $additionalData = [];

    public $notes;

    public $enter_dd;

    public $filteredLists;

    public $search_user;

    public $note_type = '';

    // Filtros
    public $region_l;

    public $region_s = [];

    public $district_l;

    public $district_s = [];

    public $city_l;

    public $city_s = [];

    public $group1_l;

    public $group1_s = [];

    public $group2_l;

    public $group2_s = [];

    public $group5_l;

    public $group5_s = [];

    public $not_assigned = false;

    public $typeNote = '';


    // Filters
    private $filter_group = 'payments';
    private $filters;

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
    ];

    protected $noteFilter;

    public function boot(NoteFilter $noteFilter)
    {
        $this->noteFilter = $noteFilter;
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;
    }

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->gotoPage(1);

    }

    public function updatedCompanyS()
    {

        $this->user_s = '';
    }

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new DispatchPaymentMain($this->getListsProperty()->get(), $this->service->uuid))->download(date('YmdHis-') . 'exportPaymentList.xlsx');
        } else {
            return (new DispatchPaymentMain($this->getListsProperty()->whereIn('id', $this->selected)->get(), $this->service->uuid))->download(date('YmdHis-') . 'exportPaymentList.xlsx');
        }
    }

    public function hasProduction(Note $note)
    {
        $production = $note->Productions->where('service_id', $this->service->uuid)->last();

        if ($production) {
            return $production;
        } else {
            return false;
        }
    }

    public function setSelectAll()
    {


        if ($this->selectall) {

            foreach ($this->getListsProperty()->paginate($this->perPage) as $item) {
                $id = $item->id;


                if (!in_array($id, $this->selected)) {

                    $production =  !$item->Productions->isEmpty() ? $item->Productions()
                    ->where(function ($q) {
                        $q->Where('service_id', $this->service->uuid);
                    })->count()
                    : null;

                    if (!$production) {
                        $this->selected[] = $id;
                    }

                }

            }

        } else {
            $visibleIds = $this->lists->pluck('id')->toArray();
            $this->selected = array_filter($this->selected, function ($id) use ($visibleIds) {
                return !in_array($id, $visibleIds);
            });
        }
    }

    public function checkAllSelect($items)
    {

        $items = $items->pluck('id')->toArray();

        $this->selectall = empty(array_diff($items, $this->selected));

        return $this->selectall;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

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

    public function filter_save()
    {
        $this->gotoPage(1);

        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['filtro']['desenho']['rubrica']  = $this->rubrica_s;
        $_SESSION['filtro']['desenho']['city']     = $this->city_s;
        $_SESSION['filtro']['desenho']['district'] = $this->district_s;
        $_SESSION['filtro']['desenho']['region']   = $this->region_s;
        $_SESSION['filtro']['desenho']['group1']   = $this->group1_s;
        $_SESSION['filtro']['desenho']['group2']   = $this->group2_s;
        $_SESSION['filtro']['desenho']['group5']   = $this->group5_s;

        $this->clean();
        $this->emit('refresh_service');
    }

    public function filter_clean()
    {
        $this->gotoPage(1);

        $this->rubrica_s  = [];
        $this->city_s     = [];
        $this->district_s = [];
        $this->region_s   = [];
        $this->group1_s   = [];
        $this->group2_s   = [];
        $this->group5_s   = [];

        $this->multiSearch = [];

        if (!isset($_SESSION)) {
            session_start();
        }

        if (isset($_SESSION['filtro']['desenho'])) {
            unset($_SESSION['filtro']['desenho']);
        }

        $this->emit('refresh_service');
    }

    public function get_single_note($note)
    {
        $this->selected = [$note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {

        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->notes = Note::find($this->selected);

        if ($this->notes->count()) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'add_mass_notes',
            ]);
        }
    }

    public function confirm_att()
    {
        if ($this->type === '2') {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . ' da ' . (Company::find($this->company_s))->name;
        } else {

            if (!$this->company_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = (Company::find($this->company_s))->name;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Despachar',
            'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                'timer'    => 5000,
            ]);

            return;
        }

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {

            foreach ($linhas as $linha) {

                if ($linha) {

                    $coluna = explode("\t", $linha);

                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $index = $this->notes->search(function ($note) use ($coluna) {
                            return $note->note == $coluna[0];
                        });

                        if ($index !== false) {
                            $this->additionalData[$index] = $coluna[1];
                        }
                    }
                }
            }
        }
    }

    public function confirmed_att()
    {

        $erros = [];

        if ($this->type == '2') {

            foreach ($this->notes as $key => $note) {

                if ($partial = $note->Partials && !$note->WorkForm ? $note->Partials->last() : false) {
                    if (!($partial->allow && $partial->supervision && !$partial->payment)) {
                        $partial = false;
                    } else {
                        $partial = true;
                    }
                } else {
                    $partial = false;
                }

                if (!$erro = Production::where('note_id', $note->id)->Where('service_id', $this->service->uuid)->Where('confirmed', false)->first()) {
                    $production = Production::create([
                        'note_id'     => $note->id,
                        'service_id'  => $this->service->uuid,
                        'user_id'     => $this->user_s,
                        'company_id'  => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'att_by'      => Auth()->User()->id,
                        'dt_note'     => $note->dt_status,
                        'status_note' => $note->nstats,
                        'centroTrab'  => $note->centerjob,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'att_at'      => date('Y-m-d H:i:s'),
                        'status'      => 2,
                        'partial'     => $partial,
                    ]);

                    $user = Auth()->User()->name;

                    if (trim($this->user_s)) {
                        $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                    } else {
                        $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                    }

                    if ($production) {
                        Notetimeline::Create([
                            'note_id'      => $production->id,
                            'service_id'   => $production->service_id,
                            'user_id'      => Auth()->User()->id,
                            'info'         => "Usuário {$user} {$user_info}",
                            'status'       => 2,
                            'productionId' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;
                }
            }
        } else {

            foreach ($this->notes as $key => $note) {


                if ($partial = $note->Partials && !$note->WorkForm ? $note->Partials->last() : false) {
                    if (!($partial->allow && $partial->supervision && !$partial->payment)) {
                        $partial = false;
                    } else {
                        $partial = true;
                    }
                } else {
                    $partial = false;
                }

                if (!$erro = Production::where('note_id', $note->id)->Where('service_id', $this->service->uuid)->Where('confirmed', false)->first()) {
                    $production = Production::create([
                        'note_id'     => $note->id,
                        'service_id'  => $this->service->uuid,
                        'company_id'  => $this->company_s,
                        'dispatch_by' => Auth()->User()->id,
                        'dt_note'     => $note->dt_status,
                        'status_note' => $note->nstats,
                        'centroTrab'  => $note->centerjob,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'status'      => 1,
                        'partial'     => $partial,
                    ]);

                    $user = Auth()->User()->name;

                    if (trim($this->user_s)) {
                        $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                    } else {
                        $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                    }

                    if ($production) {
                        Notetimeline::Create([
                            'note_id'      => $production->id,
                            'service_id'   => $production->service_id,
                            'user_id'      => Auth()->User()->id,
                            'info'         => "Usuário {$user} {$user_info}",
                            'status'       => 1,
                            'productionId' => $production->id,
                        ]);
                    }
                } else {
                    $erros[] = $erro;
                }
            }
        }

        if (count($erros)) {

            $info = '<br>';

            foreach ($erros as $err) {
                $info .= $err . ' => ' . isset($err->load('User')->User->name) ? $err->load('User')->User->name : 'Desconhecido' . '\n';
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso parcial!',
                'msg'      => "Foram Despachadas com sucesso, porém, algumas ja se enconram em controle: {$info}",
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso!',
                'timer'    => 2500,
            ]);
        }

        $this->closeall();
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s      = '';
        $this->selected       = [];
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->advanceSearch  = '';
        $this->search         = '';
        $this->gotoPage(1);

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {

        $this->company_s      = '';
        $this->enter_dd       = '';
        $this->user_s         = '';
        $this->type           = '';
        $this->additionalData = [];
        $this->multiSearch    = [];
        $this->advanceSearch  = '';
        $this->search         = '';
    }

    public function buscarMulti()
    {

        if ($this->advanceSearch) {

            $this->gotoPage(1);

            $this->search = '';

            $this->multiSearch = explode("\n", $this->advanceSearch);

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(' ', $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(',', $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(';', $this->advanceSearch);
            }

            $this->multiSearch = array_map('trim', $this->multiSearch);
        }

        if (count($this->multiSearch)) {

            $this->gotoPage(1);

            $this->closeall();
        }
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
        $query = $this->noteFilter->filter($this->search, $this->filter_group);

        if ($this->not_assigned && isset($this->service)) {
            $query->whereDoesntHave('Productions', function ($sq) {
                $sq->where('service_id', $this->service->uuid);
            });
        }

        if ($this->multiSearch) {
            $query->when($this->multiSearch, function ($q) {
                $q->whereIn('note', $this->multiSearch)
                    ->orWhereRelation('Orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
            });
        } else {
            $query->when($this->search, function ($q) {
                $q->where('note', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('Orders', function ($q) {
                        $q->where('ordem', 'like', '%' . $this->search . '%');
                    });
            });
        }


        $query->when($this->typeNote, function ($q) {
            $q->where('type_note', $this->typeNote);
        })
        ->with(['WorkForm' => function ($q) {
            $q->orderBy('informed_at', 'asc');
        }]);

        // Realizando o join com `work_reports` e `orders` e somando `moaberto`
        $query->leftJoin('work_reports', 'notes.id', '=', 'work_reports.note_id')
        ->leftJoin('orders', 'notes.id', '=', 'orders.note_id')
        ->leftJoinSub(
            DB::table('operation_resps')
                ->select('note_id', DB::raw('MAX(fimLancado) as latest_fimLancado'))
                ->groupBy('note_id'),
            'latest_operation_resps',
            'notes.id',
            '=',
            'latest_operation_resps.note_id'
        )
        ->leftJoinSub(
            DB::table('partials')
            ->select('note_id', DB::raw('MAX(id) as latest_partial_id'))
            ->where('allow', true)
            ->where('deny', false)
            ->where('supervision', true)
            ->groupBy('note_id'),
            'latest_partials',
            'notes.id',
            '=',
            'latest_partials.note_id'
        )
        ->leftJoin('partials', 'latest_partials.latest_partial_id', '=', 'partials.id')
        ->select(
            'notes.id',
            'notes.note',
            'notes.lexp',
            'notes.mesalization',
            'notes.days_left',
            'notes.type_note',
            'work_reports.created_at as wCreated_at',
            DB::raw('SUM(orders.moaberto) as total_moaberto'),
            'latest_operation_resps.latest_fimLancado as fimLancado',
            DB::raw('CASE WHEN partials.id IS NOT NULL THEN 1 ELSE 0 END as has_partials'),
        )
        ->groupBy(
            'notes.id',
            'work_reports.created_at',
            'notes.note',
            'notes.lexp',
            'notes.nstats',
            'notes.rubrica',
            'notes.centerjob',
            'notes.mesalization',
            'notes.days_left',
            'notes.type_note',
            'fimLancado'
        )
        ->groupBy('notes.id', 'work_reports.created_at', 'notes.note', 'notes.lexp', 'notes.mesalization', 'notes.days_left', 'notes.type_note', 'fimLancado', 'has_partials')
        ->orderBy('has_partials', 'desc')
        ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
        ->orderBy('fimLancado', 'asc')
        ->orderBy('total_moaberto', 'desc');

        // Debugando o resultado para checar a consulta
        // dd($query->paginate(5));

        return $query;
    }

    public function getBaseProperty()
    {
        try {
            $query          = City::query();
            $filtersApplied = false;

            if (!empty($this->region_s)) {
                $query->whereIn('regiao', $this->region_s);
                $filtersApplied = true;
            }

            if (!empty($this->district_s)) {
                $query->whereIn('baseConstrucao', $this->district_s);
                $filtersApplied = true;
            }

            if (!empty($this->city_s)) {
                $query->whereIn('cidade', $this->city_s);
                $filtersApplied = true;
            }

            if (!$filtersApplied) {
                return [];
            }

            $result = $query->orderBy('cidade')
                ->get()
                ->pluck('rdMunicipio')
                ->toArray();

            return $result;
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function render()
    {
        // $this->filteredLists = $this->lists->paginate($this->perPage)->filter(function ($list) {

        //     return !$list->Productions
        //         ->where('status_note', $list->nstats)
        //         ->where('dt_note', $list->dt_status)
        //         ->first();
        // });

        // if (empty(array_diff($this->filteredLists->pluck('id')->toArray(), $this->selected))) {
        //     $this->selectall = true;
        // } else {
        //     $this->selectall = false;
        // }

        // if (!Auth()->User()->contract) {
        //     $this->company_l = Company::orderBy('name', 'ASC')->get();
        // } else {

        //     $this->company_l = Company::where('id', Auth()->User()->Employee->Contract->company_id)->get();
        // }

        // $this->user_l = User::when($this->search_user, function ($q) {
        //     return $q->where('name', 'like', '%' . $this->search_user . '%');
        // })->whereRelation('Employee.Contract', 'company_id', $this->company_s)->orderBy('name')->get();

        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->orderBy('name', 'ASC')
            ->get();

        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
         ->where(function ($q) {
             $q->whereRelation('Company', 'company_id', $this->company_s)
                 ->orWhereRelation('Employee.Contract.company', 'id', $this->company_s);
         })
        ->when($this->search_user, function ($q) {
            return $q->where('name', 'like', '%' . $this->search_user . '%');
        })
        ->orderBy('name', 'ASC')->get();

        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        // Municipios Filtros
        try {

            $this->region_l = City::select('regiao')->orderBy('regiao')->groupBy('regiao')->get();

            $this->district_l = City::when($this->region_s, function ($q) {
                return $q->whereIn('regiao', $this->region_s);
            })->select('baseConstrucao')->orderBy('baseConstrucao')->groupBy('baseConstrucao')->get();
            $this->city_l = City::when($this->region_s, function ($q) {
                return $q->whereIn('regiao', $this->region_s);
            })
                ->when($this->district_s, function ($q) {
                    return $q->whereIn('baseConstrucao', $this->district_s);
                })
                ->select('rdMunicipio', 'cidade', 'municipio')
                ->orderBy('cidade')
                ->groupBy('rdMunicipio', 'cidade', 'municipio')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {

            $this->region_l   = [];
            $this->district_l = [];
            $this->city_l     = [];
        }

        return view('livewire.dispatchs.payment.main', [
            'lists'  => $this->lists->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
