<?php

namespace App\Http\Livewire\Dispatchs\Default;

use App\Custom\RuleBuilder;
use App\Exports\ExportDDExcel;
use App\Models\Edp_depc\City;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User, Wpa};
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

    public $note_type = '';

    // Filtros Municípios
    public $region_l;

    public $region_s = [];

    public $district_l;

    public $district_s = [];

    public $city_l;

    public $city_s = [];

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
    ];

    public function mount($service)
    {

        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

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
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para Exportar!',
                'timer'    => 2500,
            ]);

            return;
        }

        return (new ExportDDExcel())->exportDD($this->selected, $this->service->service)->download(date('YmdHis-') . 'exportDD.xlsx');
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
        // session()->put('filtro', $this->rubrica_s);
        if (!session()->isStarted()) { session()->start(); }
        $_SESSION['filtro']['rubrica']  = $this->rubrica_s;
        $_SESSION['filtro']['city']     = $this->city_s;
        $_SESSION['filtro']['district'] = $this->district_s;
        $_SESSION['filtro']['region']   = $this->region_s;
        $this->emit('refresh_service');

    }

    public function filter_clean()
    {
        $this->gotoPage(1);
        $this->rubrica_s  = [];
        $this->city_s     = [];
        $this->district_s = [];
        $this->region_s   = [];

        $this->multiSearch = [];

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
            'cancel_msg'    => 'Nenhuma nota/ov foi despachada.',

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

        if ($this->type == '2') {

            foreach ($this->notes as $key => $note) {

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
                    'centroTrab'  => $note->centerjob,
                ]);

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
                        'info'         => "Usuário {$user_info}",
                        'status'       => 2,
                        'productionId' => $production->id,
                    ]);
                }

                // if ($production) {
                //     Wpa::create([
                //         'production_id' => $production->id,
                //         'note_id' => $note->id,
                //         'dd' => $this->additionalData[$key]
                //     ]);
                // }
            }
        } else {
            foreach ($this->notes as $key => $note) {

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
                    'centroTrab'  => $note->centerjob,
                ]);

            }
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas Despachadas com sucesso!',
            'timer'    => 2500,
        ]);

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
    }

    public function buscarMulti()
    {

        if ($this->advanceSearch) {

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

    public function getListsProperty()
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $this->service->Status);

        $query->when($this->search, function ($q, $s) {
            $this->gotoPage(1);

            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('material', 'like', '%' . $s . '%')
                    ->orWhere('numPedido', 'like', '%' . $s . '%');
            });
        })->when($this->rubrica_s, function ($q) {
            return $q->where(function ($query) {
                $query->where('rubrica', $this->rubrica_s)
                    ->orWhereNull('rubrica');
            });
        })
            ->when($this->note_type, function ($q) {
                return $q->where(function ($query) {
                    $query->where('type_note', $this->note_type)
                        ->orWhereNull('type_note');
                });
            })
            ->when($this->base, function ($q) {
                return $q->where(function ($query) {
                    return $query->whereIn('nexp', $this->base)
                        ->orWhere('nexp', '')
                        ->orWhere('nexp', null);
                });
            })
            ->with('Productions.User')
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left')
            ->orderBy('dt_status');

        if (count($this->multiSearch)) {
            $query = Note::query()->excludeCanceledFullDone();
            $query->when($this->multiSearch, function ($q) {
                return $q->WhereIn('note', $this->multiSearch);
            })
                ->with('Productions.User')
                ->orderBy('type_note', 'DESC')
                ->orderBy('days_left')
                ->orderBy('dt_status');
        }

        return $query->paginate($this->perPage);
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
        $this->filteredLists = $this->lists->filter(function ($list) {

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

        $this->user_l = User::whereRelation('Employee.Contract', 'company_id', $this->company_s)->orderBy('name')->get();

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
            ->orderBy('cidade')
            ->groupBy('rdMunicipio', 'cidade', 'municipio')
            ->get();

        return view('livewire.dispatchs.default.main', [
            'lists'  => $this->lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
