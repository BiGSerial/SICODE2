<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Custom\RuleBuilder;
use App\Exports\Dispatchs\SurveyExportList;
use App\Exports\ExportDDExcel;
use App\Exports\Reports\SurveyListExport;
use App\Models\Bancoupdate;
use App\Models\Company;
use App\Models\Edp_depc\City;
use App\Models\Note;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Models\Wpa;
use App\Services\Dispatch\DispatchException;
use App\Services\Dispatch\DispatchWorkflowService;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpParser\Node\Expr\Empty_;

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 100;
    public $search;
    public $search_user;
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
    public $additionalDataUpd = [];

    public $notes;
    public $filteredLists;

    public $note_type = '';

    // Filtros Municípios
    public $region_l;
    public $region_s = [];
    public $district_l;
    public $district_s = [];
    public $city_l;
    public $city_s = [];


    public $branco = false;

    //Variáveis para DDs
    public $enter_dd;
    public $existDD;

    public $key;
    public $municipio_edit;

    //Botão de exibição de nao atribuído
    public $not_assigned = false;


    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'getCopy' => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch' => 'confirmed_att',
        'confirm_mass_dd' => 'confirmed_mass_dd',
    ];

    public function view_edit($key)
    {
        $this->key = $key;
    }

    public function hide_edit()
    {
        $this->key = "";
    }

    public function municipio_update(Note $note)
    {
        if (trim($this->municipio_edit)) {
            if ($note->update(['lexp' => mb_strtoupper(trim($this->municipio_edit))])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'success',
                    'title' => 'Informação Alterada',
                    'timer' => 2500,
                ]);

                $this->municipio_edit = "";
                $this->hide_edit();
                $this->emit('refresh_dispatch');
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma informação inserida.',
                'timer' => 2500,
            ]);
            $this->municipio_edit = "";
            $this->hide_edit();
            $this->emit('refresh_dispatch');
        }
    }

    public function mount($service)
    {

        $this->service = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        if (!session()->isStarted()) { session()->start(); }
        if (isset($_SESSION['filtro']) && $_SESSION['filtro']) {
            if (isset($_SESSION['filtro']['rubrica'])) {
                $this->rubrica_s = $_SESSION['filtro']['rubrica'];
            }
            if (isset($_SESSION['filtro']['city'])) {
                $this->city_s = $_SESSION['filtro']['city'];
            }
            if (isset($_SESSION['filtro']['district'])) {
                $this->district_s = $_SESSION['filtro']['district'];
            }
            if (isset($_SESSION['filtro']['region'])) {
                $this->region_s = $_SESSION['filtro']['region'];
            }
        }
    }

    public function export_excel()
    {

        if (!count($this->selected)) {
            return (new SurveyExportList($this->getListsProperty(), $this->service->uuid))->download(date('YmdHis-') . 'exportSelectedSurvey.xlsx');
        }

        return (new SurveyListExport($this->getListsProperty()->find($this->selected), $this->service->uuid))->download(date('YmdHis-') . 'exportSelectedSurvey.xlsx');


    }

    public function updatedCompanyS()
    {
        $this->user_s = '';
        $this->loadDispatchUsers();
    }

    public function dispatchCompanyChanged($companyId): void
    {
        $this->company_s = $companyId;
        $this->updatedCompanyS();
    }

    public function loadDispatchCompanies(): void
    {
        if (Auth()->User()?->contract && $this->notes && $this->notes->count()) {
            $companyIds = Production::whereIn('note_id', $this->notes->pluck('id'))
                ->where('service_id', $this->service->uuid)
                ->whereIn('company_id', SicodeRules::visibleCompanyIdsFor(Auth()->User()))
                ->whereNull('user_id')
                ->where('completed', false)
                ->where('confirmed', false)
                ->distinct()
                ->pluck('company_id');

            $this->company_l = Company::whereIn('id', $companyIds)
                ->orderBy('name', 'ASC')
                ->get();

            return;
        }

        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->when(Auth()->User()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(Auth()->User());

                return count($companyIds)
                    ? $q->whereIn('id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function loadDispatchUsers(): void
    {
        $this->user_s = '';

        if (!$this->company_s) {
            $this->user_l = collect();
            return;
        }

        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
            ->where(function ($q) {
                $q->where('company_id', $this->company_s)
                    ->orWhereRelation('Employee.Contract', 'company_id', $this->company_s)
                    ->orWhereRelation('Companies', 'companies.id', $this->company_s);
            })
            ->when($this->search_user, function ($q) {
                return $q->where('name', 'like', '%' . $this->search_user . '%');
            })
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->get();
    }

    private function preselectContractDispatchCompany(): void
    {
        if (!Auth()->User()?->contract || !$this->notes || !$this->notes->count()) {
            return;
        }

        $companyIds = $this->notes
            ->map(fn ($note) => SicodeRules::openCompanyStackProductionFor($note, Auth()->User(), $this->service->uuid)?->company_id)
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($companyIds->count() === 1) {
            $this->company_s = $companyIds->first();
        }
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
            'status' => 'success',
            'menssage' => $msg,
        ]);
    }

    public function filter_save()
    {
        $this->gotoPage(1);
        // session()->put('filtro', $this->rubrica_s);
        if (!session()->isStarted()) { session()->start(); }
        $_SESSION['filtro']['rubrica'] = $this->rubrica_s;
        $_SESSION['filtro']['city'] = $this->city_s;
        $_SESSION['filtro']['district'] = $this->district_s;
        $_SESSION['filtro']['region'] = $this->region_s;
        $this->emit('refresh_service');

    }

    public function filter_clean()
    {
        $this->gotoPage(1);

        $this->rubrica_s = [];
        $this->city_s = [];
        $this->district_s = [];
        $this->region_s = [];

        if (!session()->isStarted()) { session()->start(); }
        if (isset($_SESSION['filtro'])) {
            unset($_SESSION['filtro']);
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
                'icon' => 'warning',
                'title' => 'Nenhuma nota foi selecionada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', array_values($this->selected));
    }

    public function confirm_att()
    {
        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma empresa foi selecionada para despacho!',
                'timer' => 2500,
            ]);

            return;
        }

        if ($this->type === "2") {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon' => 'warning',
                    'title' => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer' => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name." da ".(Company::find($this->company_s))->name;

        } else {
            $para = (Company::find($this->company_s))->name;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' =>  'Confirmar Despachar',
            'msg' => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Despache!',
            'btnCanceltxt' => 'Não, Cancele',
            'action' => "confirm_dispatch",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma entrada para atribuição!',
                'timer' => 5000,
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
        if (!in_array((string) $this->type, ['1', '2'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione o tipo de despacho.',
                'timer' => 2500,
            ]);

            return;
        }

        try {
            $workflow = app(DispatchWorkflowService::class);
            $company = Company::findOrFail($this->company_s);
            $targetUser = (string) $this->type === '2' ? User::findOrFail($this->user_s) : null;
            $actor = Auth()->User();

            DB::transaction(function () use ($workflow, $company, $targetUser, $actor) {
                foreach ($this->notes as $key => $note) {
                    $dd = $this->additionalData[$key] ?? null;

                    if ($targetUser) {
                        $workflow->dispatchToUser($note, $this->service, $company, $targetUser, $actor, $dd);
                    } else {
                        $workflow->dispatchToCompanyStack($note, $this->service, $company, $actor, $dd);
                    }
                }
            });
        } catch (DispatchException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => $e->getMessage(),
                'timer' => 6000,
            ]);

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Erro ao despachar as Notas/OVs.',
                'timer' => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Notas Despachadas com sucesso!',
            'timer' => 2500,
        ]);

        $this->closeall();
        $this->emit('refresh_dispatch');
    }



    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');


        $this->company_s = "";
        $this->selected = [];
        $this->user_s = "";
        $this->user_l = collect();
        // $this->type = "";
        $this->additionalData = [];

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {

        $this->company_s = "";
        $this->enter_dd = "";
        $this->user_s = "";
        $this->search_user = "";
        $this->user_l = collect();
        // $this->type = "";
        $this->additionalData = [];
    }

    public function buscarMulti()
    {

        $this->gotoPage(1);

        if ($this->advanceSearch) {

            $this->search = "";

            $this->multiSearch = explode("\n", $this->advanceSearch);

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(" ", $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(",", $this->advanceSearch);
            }

            if (!count($this->multiSearch)) {
                $this->multiSearch = explode(";", $this->advanceSearch);
            }

            $this->multiSearch = array_map('trim', $this->multiSearch);
        }

        if (count($this->multiSearch)) {
            $this->closeall();
        }
    }


    /**
     * Atribuição de DD em Mmassa.
     *
     * Aqui foi montando uma arquitetura para que seja possível associar uma nota a uma DD, na tabela
     * WPAs. Assim sendo, quando carregar as DD para atribuição em massa, o mesmo ja seja inserido nas
     * correspondentes.
     */


    public function mass_modal()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhum entrada para atribuição',
                'timer' => 5000,
            ]);

            return;
        }

        $additionalData = [];
        $additionalDataUpd = [];

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {
            $count = 0;
            $ok = 0;

            foreach ($linhas as $linha) {

                if ($linha) {
                    $coluna = explode("\t", $linha);

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(";", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(" ", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(",", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon' => 'warning',
                            'title' => "Gentileza separar os valores com alguma forma válida: ' ', ';', ','.",

                        ]);

                        return;
                    }



                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $dd = Wpa::Where('dd', trim($coluna[1]))->first();

                        if ($dd && !$dd->production_id) {

                            $note = Note::find($dd->note_id);

                            if ($note->note != trim($coluna[0])) {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon' => 'warning',
                                    'title' => "A DD {$dd->dd} já foi atribuída a Nota: {$note->note}. (Nenhuma Nota foi associada. Verifique novamente.)",

                                ]);

                                return;
                            }
                        } elseif ($dd && $dd->production_id) {

                            $production = Production::with('Note', 'User')->find($dd->production_id);

                            if ($production) {

                                $name_user = isset($production->User) ? $production->User->name : 'Desconhecido';

                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon' => 'warning',
                                    'title' => "A DD {$dd->dd} já foi atribuída para a nota {$production->Note->note} despachada para {$name_user}",

                                ]);

                            } else {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon' => 'error',
                                    'title' => "A DD {$dd->dd} parece que ja foi despachada anteriormente, porém não encontrei relação de produção. Informe ao ADM.",

                                ]);
                            }

                            return;
                        }

                        $dd = Wpa::WhereRelation('Note', 'note', trim($coluna[0]))->whereNull('production_id')->first();
                        $note = Note::where('note', trim($coluna[0]))->first();

                        if (!$dd && $note) {



                            if (count($additionalData)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalData, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada. <br> Revise novamente as DDs a serem cadastradas.<br> Nenhuma DD foi associada.",

                                    ]);

                                    return;

                                }
                            }

                            if (count($additionalDataUpd)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalDataUpd, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;

                                }
                            }




                            $additionalData[] = [
                                'note_id' => $note->id,
                                'dd' => trim($coluna[1]),
                            ];

                            $ok++;


                        }

                        if (($dd && $note) && ($dd->dd != trim($coluna[1]))) {

                            if (count($additionalData)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalData, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;

                                }
                            }

                            if (count($additionalDataUpd)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalDataUpd, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon' => 'warning',
                                        'title' => "DD Duplicada",
                                        'html' => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;

                                }
                            }

                            $additionalDataUpd[] = [
                                'id' => $dd->id,
                                'dd' => $coluna[1],
                            ];

                            $ok++;
                        }


                    } else {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon' => 'warning',
                            'title' => "Existem Notas/OV ou DD com caracteres inválidos (SOMENTE NÚMEROS SÃO PERMITIDOS). Confira novamente.",

                        ]);

                        return;
                    }

                }


            }

            if (count($additionalData) || count($additionalDataUpd)) {

                $count = count($additionalData) + count($additionalDataUpd);

                $this->additionalData = $additionalData;
                $this->additionalDataUpd = $additionalDataUpd;

                $this->dispatchBrowserEvent('alertar', [
                    'title' =>  'Confirmar Atribuir DD?',
                    'msg' => "Você está prestes a atribuir {$count} de {$ok} notas DD, Deseja Continuar?",
                    'icon' => 'info',
                    'btnOktxt' => 'Sim, Continue!',
                    'btnCanceltxt' => 'Não, Cancele',
                    'action' => "confirm_mass_dd",
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg' => 'Nenhuma Nota Atribuída.',

                ]);
            }

        }
    }

    public function confirmed_mass_dd()
    {


        // if ($count = count($this->additionalData)) {
        //     $error = 0;
        //     foreach ($this->additionalData as $wpa) {
        //         if (!Wpa::Create($wpa)) {
        //             $error++;
        //         }
        //     }
        // }

        // if ($count = count($this->additionalDataUpd)) {
        //     $error = 0;
        //     foreach ($this->additionalDataUpd as $wpa) {
        //         if (!Wpa::Update($wpa)) {
        //             $error++;
        //         }
        //     }
        // }

        // if (!$error) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'success',
        //         'title' => 'Notas DDs associadas com sucesso',
        //         'timer' => 2500,
        //     ]);

        //     $this->closeall();
        // } else {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'error',
        //         'title' => "OOPS!, Ocorreram {$error} de {$count} ao associar as DD às Notas.",
        //         'timer' => 8000,
        //     ]);
        // }


        $count = count($this->additionalData);
        $error = 0;

        foreach ($this->additionalData as $wpa) {
            if (!Wpa::create($wpa)) {
                $error++;
            }
        }

        $countUpd = count($this->additionalDataUpd);
        $errorUpd = 0;

        foreach ($this->additionalDataUpd as $wpa) {
            $idToUpdate = $wpa['id'];
            unset($wpa['id']);

            if (!Wpa::where('id', $idToUpdate)->update($wpa)) {
                $errorUpd++;
            }
        }

        $totalErrors = $error + $errorUpd;
        $totalCount = $count + $countUpd;

        if (!$totalErrors) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Notas DDs associadas com sucesso',
                'timer' => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => "OOPS!, Ocorreram {$totalErrors} de {$totalCount} ao associar as DD às Notas.",
                'timer' => 8000,
            ]);
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
        // dd($this->base);

        $query = Note::query()->excludeCanceledFullDone();
        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            Auth()->User(),
            $this->service->uuid,
            fn ($statusQuery) => RuleBuilder::applyRules($statusQuery, $this->service->Status)
        );


        if (strlen($this->search)) {
            $this->gotoPage(1);
            $this->multiSearch = [];

            $query->where(function ($q) {
                return$q->where('note', 'like', '%' . trim($this->search). '%')
                        ->orWhere('material', 'like', '%' . trim($this->search). '%')
                        ->orWhere('numPedido', 'like', '%' . trim($this->search). '%')
                        ->orWhere('group1', 'like', '%' . trim($this->search). '%')
                        ->orWhere('group2', 'like', '%' . trim($this->search). '%')
                        ->orWhere('group4', 'like', '%' . trim($this->search). '%')
                        ->orWhere('group5', 'like', '%' . trim($this->search). '%');
            });
        }

        if (count($this->multiSearch)) {
            $this->gotoPage(1);
            $this->search = "";

            $query->where(function ($q) {
                return $q->WhereIn('note', $this->multiSearch)
                ->orWhere(function ($q) {
                    $q->whereIn('note', $this->multiSearch)
                        ->where('centerjob', 'PREDSINA');
                });
            });

        } elseif (!$this->search && !count($this->multiSearch) && $this->base) {
            $query->where(function ($q) {
                return $q->whereIn('nexp', $this->base)
                ->orWhereNull('nexp');
            });
        }

        if (count($this->rubrica_s)) {
            $query->where(function ($q) {
                return $q->whereIn('rubrica', $this->rubrica_s)
                ->orWhereNull('rubrica');
            });

        }

        if ($this->note_type) {
            $query->where(function ($q) {
                return $q->where('type_note', $this->note_type)
                    ->orWhereNull('type_note');
            });
        }


        if ($this->not_assigned) {
            $query->where(function ($q) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) {
                        $subquery->where('service_id', $this->service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        $query->with([
            'Productions.User',
            'Productions.Company',
            'Wpas',
        ])
                    ->orderBy('is45', 'DESC')
                    ->orderBy('type_note', 'DESC')
                    ->orderBy('days_left');

        return $query;
    }

    public function getBaseProperty()
    {
        try {
            $query = City::query();
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
        $this->filteredLists = $this->lists->paginate($this->perPage)->filter(function ($list) {
            if (Auth()->User()?->contract) {
                return SicodeRules::hasCompanyDispatchProductionFor($list, Auth()->User(), $this->service->uuid);
            }

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

        // if (!Auth()->User()->contract) {
        //     $this->company_l = Company::orderBy('name', 'ASC')->get();
        // } else {

        //     $this->company_l = Company::where('id', Auth()->User()->Employee->Contract->company_id)->get();
        // }

        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        // Municipios Filtros
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
        ->distinct()
        ->orderBy('cidade')
        ->groupBy('rdMunicipio', 'cidade', 'municipio')
        ->get();



        return view('livewire.dispatchs.survey.main', [
            'lists' => $this->lists->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first()
        ]);
    }
}
