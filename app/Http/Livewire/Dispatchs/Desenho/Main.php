<?php

namespace App\Http\Livewire\Dispatchs\Desenho;

use App\Custom\RuleBuilder;
use App\Exports\DispatchDesenhoMain;
use App\Models\Edp_depc\City;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User};
use App\Services\Design\BlockEvaluator;
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

    // TODO: 27 Dias Status Temporário - Remover no Futuro
    public $only_27 = false;

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    protected $queryString = [
        'search'         => ['except' => '', 'as' => 'busca'],

        'note_type'      => ['except' => '', 'as' => 'tipo'],


        'multiSearch',
    ];

    public function mount($service)
    {

        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        $this->group1_l = $this->lists->orderBy('group1')->get()->pluck('group1')->unique();
        $this->group2_l = $this->lists->orderBy('group2')->get()->pluck('group2')->unique();
        $this->group5_l = $this->lists->orderBy('group5')->get()->pluck('group5')->unique();

        if (!session()->isStarted()) { session()->start(); }

        if (isset($_SESSION['filtro']['desenho']) && $_SESSION['filtro']['desenho']) {
            if (isset($_SESSION['filtro']['desenho']['rubrica'])) {
                $this->rubrica_s = $_SESSION['filtro']['desenho']['rubrica'];
            }

            if (isset($_SESSION['filtro']['desenho']['city'])) {
                $this->city_s = $_SESSION['filtro']['desenho']['city'];
            }

            if (isset($_SESSION['filtro']['desenho']['district'])) {
                $this->district_s = $_SESSION['filtro']['desenho']['district'];
            }

            if (isset($_SESSION['filtro']['desenho']['region'])) {
                $this->region_s = $_SESSION['filtro']['desenho']['region'];
            }

            if (isset($_SESSION['filtro']['desenho']['group1'])) {
                $this->group1_s = $_SESSION['filtro']['desenho']['group1'];
            }

            if (isset($_SESSION['filtro']['desenho']['group2'])) {
                $this->group2_s = $_SESSION['filtro']['desenho']['group2'];
            }

            if (isset($_SESSION['filtro']['desenho']['group5'])) {
                $this->group5_s = $_SESSION['filtro']['desenho']['group5'];
            }
        }
    }

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new DispatchDesenhoMain($this->lists->get(), $this->service->uuid))->download(date('YmdHis-') . 'exportNotesDesenho.xlsx');
        } else {
            $notes = Note::WhereIn('id', $this->selected)->orderBy('days_left')->get();

            return (new DispatchDesenhoMain($notes, $this->service->uuid))->download(date('YmdHis-') . 'exportNotesDesenho.xlsx');
        }
    }

    public function check_mmgd(Note $note)
    {
        $note->mmgd = !$note->mmgd;
        $note->save();

        // $this->emitSelf('refresh_dispatch');
    }

    public function check_is45(Note $note)
    {
        $note->is45 = !$note->is45;
        $note->save();

        // $this->emitSelf('refresh_dispatch');
    }



    public function updatedCompanyS()
    {

        $this->user_s = '';
    }

    public function updatedSelectall($val)
    {

        $idsToKeep = $this->filteredLists->pluck('id')->toArray();

        if ($val) {
            // Adicionar os IDs ausentes de $selected
            foreach ($idsToKeep as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            // Criar um novo array $selected com os IDs que devem ser mantidos
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $idsToKeep)) {
                    $newSelected[] = $id;
                }
            }
            $this->selected = $newSelected;
        }

    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function filter_save()
    {
        $this->gotoPage(1);

        if (!isset($_SESSION)) {
            if (!session()->isStarted()) { session()->start(); }
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
            if (!session()->isStarted()) { session()->start(); }
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
                    ]);

                    $user = Auth()->User()->name;

                    $user_info = $this->dispatchRecipientInfo();

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
                    ]);

                    $user = Auth()->User()->name;

                    $user_info = $this->dispatchRecipientInfo();

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
                $info .= $this->productionAssignmentLabel($err) . '<br>';
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
        $query = Note::query()->excludeCanceledFullDone();

        if (count($this->multiSearch)) {
            $query->whereIn('note', $this->multiSearch);
        } else {

            RuleBuilder::applyRules($query, $this->service->Status);

            if ($this->not_assigned) {
                $query->where(function ($q) {
                    $q->doesntHave('Productions')
                        ->orWhereDoesntHave('Productions', function ($subquery) {
                            $subquery->where('service_id', $this->service->uuid)
                                ->where('confirmed', false);
                        });
                });
            }

            $query->when($this->search, function ($q, $s) {
                $this->gotoPage(1);

                return $q->where(function ($query) use ($s) {
                    $query->where('note', 'like', '%' . $s . '%')
                        ->orWhere('material', 'like', '%' . $s . '%')
                        ->orWhere('numPedido', 'like', '%' . $s . '%')
                        ->orWhere('group4', 'like', '%' . $s . '%')
                        ->orWhere('group5', 'like', '%' . $s . '%');
                });
            })->when($this->rubrica_s, function ($q) {
                return $q->where(function ($query) {
                    $query->whereIn('rubrica', $this->rubrica_s)
                        ->orWhereNull('rubrica');
                });
            })
                ->when($this->note_type, function ($q) {
                    return $q->where(function ($query) {
                        $query->where('type_note', $this->note_type)
                            ->orWhereNull('type_note');
                    });
                })
                ->when($this->group1_s, function ($q) {
                    return $q->where(function ($query) {
                        return $query->whereIn('group1', $this->group1_s)
                            ->orWhere('group1', '')
                            ->orWhere('group1', null);
                    });
                })
                ->when($this->group2_s, function ($q) {
                    return $q->where(function ($query) {
                        return $query->whereIn('group2', $this->group2_s)
                            ->orWhere('group2', '')
                            ->orWhere('group2', null);
                    });
                })
                ->when($this->group5_s, function ($q) {
                    return $q->where(function ($query) {
                        return $query->whereIn('group5', $this->group5_s)
                            ->orWhere('group5', '')
                            ->orWhere('group5', null);
                    });
                })
                ->when($this->base, function ($q) {
                    return $q->where(function ($query) {
                        return $query->whereIn('nexp', $this->base)
                            ->orWhere('nexp', '')
                            ->orWhere('nexp', null);
                    });
                })
                // NOTE: Remover no futuro.
                ->when($this->only_27, function ($q) {
                    $q->where('days_left', '<=', 3)
                      // exige que a ÚLTIMA production (da relação acima) seja desse serviço e confirmada
                    ->whereHas(
                        'lastProduction',
                        fn ($r) =>
                        $r->where('service_id', $this->service->uuid)
                            ->where('confirmed', true)
                    );
                });
        }

        $query->with('Productions.User')
            ->orderBy('is45', 'DESC')
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left', 'ASC')
            ->orderBy('dt_status');

        return $query;

    }

    public function needBlock(Note $note): array
    {
        $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);
        // retorna estrutura pra view usar diretamente
        return $eval;
    }

    public function getBaseProperty()
    {
        try {
            $query  = City::query();
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

    private function dispatchRecipientInfo(): string
    {
        if (trim((string) $this->user_s)) {
            $userName = User::find($this->user_s)?->name ?? 'Desconhecido';

            return "Atribuiu a NOTA/OV para: {$userName}";
        }

        $companyName = Company::find($this->company_s)?->name ?? 'Desconhecido';

        return "Despachou a NOTA/OV para: {$companyName}";
    }

    private function productionAssignmentLabel(Production $production): string
    {
        $production->loadMissing(['Note', 'User', 'Company']);

        $note = $production->Note?->note ?? $production->note_id;

        if ($production->User) {
            return "{$note} => {$production->User->name}";
        }

        if ($production->Company) {
            return "{$note} => {$production->Company->name} (sem usuário atribuído)";
        }

        return "{$note} => Desconhecido";
    }

    public function render()
    {
        $this->filteredLists = $this->lists->paginate($this->perPage)->filter(function ($list) {

            return !$list->Productions
                ->where('status_note', $list->nstats)
                ->where('dt_note', $list->dt_status)
                ->first();
        });

        if (empty(array_diff($this->filteredLists->pluck('id')->toArray(), $this->selected))) {
            $this->selectall = true;
        } else {
            $this->selectall = false;
        }

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

        return view('livewire.dispatchs.desenho.main', [
            'lists'  => $this->lists->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
