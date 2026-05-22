<?php

namespace App\Http\Livewire\Dispatchs\Publication;

use App\Custom\RuleBuilder;
use App\Exports\DispatchDesenhoMain;
use App\Exports\Dispatchs\PublicationExportList;
use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User};
use App\Repositories\PublishRepository;
use App\Services\Publication\NoteFilter;
use App\Traits\WildcardFormmater;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormmater;

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

    public $items = [];

    public $not_assigned = false;

    public $all_services = false;

    private $filter_group = 'publishing';
    private $filters;

    public $btzeroform = true;


    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    private $publishRepository;

    public function boot(PublishRepository $publishRepository)
    {
        $this->publishRepository = $publishRepository;
    }

    public function mount($service)
    {

        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

    }

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new PublicationExportList($this->getListsProperty(), $this->service))->download(date('YmdHis-') . 'PublicationExportList.xlsx');
        } else {


            return (new PublicationExportList($this->getListsProperty()->whereIn('notes.id', $this->selected), $this->service))->download(date('YmdHis-') . 'PublicationExportListSelected.xlsx');
        }
    }


    protected $noteFilter;

    // public function boot(NoteFilter $noteFilter)
    // {
    //     $this->noteFilter = $noteFilter;
    // }


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

    public function updatedCompanyS()
    {

        $this->user_s = '';
    }

    public function btzeroform()
    {
        $this->btzeroform = !$this->btzeroform;
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function hasPublication(Note $note)
    {
        $production = $note->Productions->where('service_id', $this->service->uuid)->last();

        if ($production) {
            return $production;
        } else {
            return false;
        }
    }

    public function hasPublicationCount(Note $note)
    {
        return $note->Productions->where('service_id', $this->service->uuid)->count();
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
        $this->gotoPage(1);

        $this->search = '';

        $this->multiSearch = $this->formatTextToArray((string)$this->advanceSearch);

        $this->advanceSearch = '';

        $this->dispatchBrowserEvent('hideModal');
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
        // Inicialização da sessão (movida para fora da função, idealmente em um middleware ou construtor)

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];
        }

        if (!count($this->multiSearch)) {
            $this->all_services = false;
        }


        $query = $this->publishRepository->getBaseQuery($this->all_services);

        // Scope Local para WorkForm (Melhora a Legibilidade e Reusabilidade)
        if (!$this->all_services) {
            $query->where(function ($q) {
                $q->where(function ($wq) {
                    $wq->whereHas('WorkForm', function ($sq) {
                        $sq->where('rejected', false);
                    })->orWhere(function ($sq) {
                        if ($this->btzeroform) {
                            $sq->doesntHave('WorkForm')
                               ->whereHas('RamalForm');
                        }
                    });
                });
            });
        }


        // Filtro de Rubrica
        if (isset($this->filters['rubrica'])) {
            $rubricas = $this->filters['rubrica'];
            $query->where(function ($q) use ($rubricas) {
                $q->whereIn('rubrica', $rubricas)
                    ->orWhereNull('rubrica');
            });
        }

        // Filtro de Cidade (lexp)
        if (isset($this->filters['city'])) {
            $cities = $this->filters['city'];
            $query->where(function ($q) use ($cities) {
                $q->whereIn('lexp', $cities)
                    ->orWhereNull('lexp');
            });
        }


        // Filtro de Companhia (company_id) no WorkForm
        if (isset($this->filters['company'])) {
            $companies = $this->filters['company'];
            $query->whereHas('WorkForm', function ($q) use ($companies) {
                $q->whereIn('company_id', $companies);
            });
        }

        // MultiSearch (Notas ou Ordens)
        if ($this->multiSearch) {
            $multiSearchTerms = $this->multiSearch;
            $query->where(function ($q1) use ($multiSearchTerms) {
                $q1->whereIn('note', $multiSearchTerms)
                    ->orWhereHas('Orders', function ($q2) use ($multiSearchTerms) {
                        $q2->whereIn('ordem', $multiSearchTerms);
                    });
            });
        }

        // Pesquisa Geral (search)
        if ($this->search) {
            $this->multiSearch = [];

            $search = $this->formatWithWildcard($this->search);

            $query->where(function ($q) use ($search) {
                $q->where('note', $search->type, $search->search)
                    ->orWhere('material', $search->type, $search->search)
                    ->orWhere('numPedido', $search->type, $search->search)
                    ->orWhere('group2', $search->type, $search->search);
            });
        }


        // Eager Loading e Seleção de Colunas
        $query->with('Productions', 'WorkForm', 'RamalForm')
            ->select([
                'notes.*',
                    DB::raw("
                        CASE
                            WHEN notes.type_note = 2 THEN DATE_ADD(CURDATE(), INTERVAL notes.days_left DAY)
                            WHEN notes.type_note = 1 THEN STR_TO_DATE(CONCAT('28/', SUBSTRING(notes.mesalization, 2, 2), '/', SUBSTRING(notes.mesalization, 5)), '%d/%m/%Y')
                            ELSE NULL
                        END as prazo_final
                    ")
            ]);

        // Condição para Notas Não Atribuídas
        $serviceUuid = $this->service->uuid; // Cache do valor
        $query->when($this->not_assigned, function ($q) use ($serviceUuid) {
            $q->whereDoesntHave('Productions', function ($q) use ($serviceUuid) {
                $q->where('service_id', $serviceUuid)
                    ->whereNotNull('user_id');
            })->orWhereHas('Productions', function ($q) use ($serviceUuid) {
                $q->where('service_id', $serviceUuid)
                    ->where(function ($q) {
                        $q->whereNull('user_id')
                            ->orWhere('user_id', '');
                    });
            });
        });


        // Ordenação
        $query->orderByRaw('
            exists (
                select 1
                from ramal_reports
                where ramal_reports.note_id = notes.id
            )
            and not exists (
                select 1
                from work_reports
                where work_reports.note_id = notes.id
            ) desc
        ')
        ->orderBy('is45', 'DESC')
        ->orderBy('prazo_final', 'ASC');


        return $query;
    }

    // public function getBaseProperty()
    // {
    //     try {
    //         $query          = City::query();
    //         $filtersApplied = false;

    //         if (!empty($this->region_s)) {
    //             $query->whereIn('regiao', $this->region_s);
    //             $filtersApplied = true;
    //         }

    //         if (!empty($this->district_s)) {
    //             $query->whereIn('baseConstrucao', $this->district_s);
    //             $filtersApplied = true;
    //         }

    //         if (!empty($this->city_s)) {
    //             $query->whereIn('cidade', $this->city_s);
    //             $filtersApplied = true;
    //         }

    //         if (!$filtersApplied) {
    //             return [];
    //         }

    //         $result = $query->orderBy('cidade')
    //             ->get()
    //             ->pluck('rdMunicipio')
    //             ->toArray();

    //         return $result;
    //     } catch (\Throwable $th) {
    //         return [];
    //     }
    // }

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



        if (!Auth()->User()->contract) {
            $this->company_l = Company::orderBy('name', 'ASC')->get();
        } else {

            $this->company_l = Company::where('id', Auth()->User()->Employee->Contract->company_id)->get();
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

        // $this->user_l = User::when($this->search_user, function ($q) {
        //     return $q->where('name', 'like', '%' . $this->search_user . '%');
        // })->whereRelation('Employee.Contract', 'company_id', $this->company_s)->orderBy('name')->get();

        // $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        // Municipios Filtros
        // try {

        //     $this->region_l = City::select('regiao')->orderBy('regiao')->groupBy('regiao')->get();

        //     $this->district_l = City::when($this->region_s, function ($q) {
        //         return $q->whereIn('regiao', $this->region_s);
        //     })->select('baseConstrucao')->orderBy('baseConstrucao')->groupBy('baseConstrucao')->get();
        //     $this->city_l = City::when($this->region_s, function ($q) {
        //         return $q->whereIn('regiao', $this->region_s);
        //     })
        //         ->when($this->district_s, function ($q) {
        //             return $q->whereIn('baseConstrucao', $this->district_s);
        //         })
        //         ->select('rdMunicipio', 'cidade', 'municipio')
        //         ->orderBy('cidade')
        //         ->groupBy('rdMunicipio', 'cidade', 'municipio')
        //         ->get();
        // } catch (\Illuminate\Database\QueryException $e) {

        //     $this->region_l   = [];
        //     $this->district_l = [];
        //     $this->city_l     = [];
        // }

        return view('livewire.dispatchs.publication.main', [
            'lists'  => $this->lists->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
