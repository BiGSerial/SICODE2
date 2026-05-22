<?php

namespace App\Http\Livewire\Dispatchs\Payment;

use App\Exports\DispatchDesenhoStack;
use App\Exports\Dispatchs\DispatchPaymentStack;
use App\Models\Edp_depc\City;
use App\Models\FiveNote;
use App\Models\{Analise, Company, Note, Notetimeline, Production, Service, User, Wpa};
use App\Services\D5\D5WorkflowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Stack extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // VAr System
    public $service;

    public $last_update;

    public $search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $perPage = 100;

    public $advanceSearch;

    public $multiSearch = [];

    public $note;

    public $notes;

    public $enter_dd;

    public $filteredLists;

    public $priority;

    public $status_l;

    public $status_s = [];

    public $selectAll;

    public $selected = [];

    public $company_l;

    public $company_s;

    public $company_fs = [];

    public $user_l;

    public $user_s;

    public $user_fl;

    public $user_fs = [];

    public $type = '2';

    public $additionalData = [];

    // Filtros Municípios
    public $region_l;

    public $region_s = [];

    public $district_l;

    public $district_s = [];

    public $city_l;

    public $city_s = [];

    public $note_type = '';

    public $force = true;

    public $forcar = false;

    public $delete;

    public $production;

    public $productions;

    public $audits;

    protected $listeners = [
        'refresh_list'         => '$refresh',
        'confirm_remove_att'   => 'remove_att',
        'confirm_dispatch'     => 'confirmed_att',
        'getCopy'              => 'copy',
        'confirm_des_att_mass' => 'confirm_des_att_mass',
        'filterUser'           => 'filterUser',
        'closeall'             => 'closeall',
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;
    }

    public function filterUser($user_id)
    {
        $this->user_fs = [$user_id];
    }

    public function setSelectAll()
    {
        if (!$this->lists) {
            return;
        }

        $visibleItems = $this->lists->items();

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);

        if ($this->selectAll) {

            foreach ($visibleItems as $note) {

                $id = (int) $note->id;

                if (isset($selectedSet[$id])) {
                    continue;
                }

                $selectedSet[$id] = true;
            }
        } else {
            foreach ($visibleItems as $note) {
                unset($selectedSet[(int) $note->id]);
            }
        }

        $this->selected = array_map('intval', array_keys($selectedSet));

    }

    public function checkAllSelect($items)
    {
        $eligiblePage = [];

        foreach ($items as $note) {
            $eligiblePage[] = (int) $note->id;
        }

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);

        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectAll = false;
                return false;
            }
        }

        $this->selectAll = true;
        return true;
    }

    protected function recomputeSelectAllFor(array $items): void
    {

        $eligiblePage = [];

        foreach ($items as $note) {
            $eligiblePage[] = (int) $note->id;
        }

        // se não há elegíveis na página, não marcar o master
        if (empty($eligiblePage)) {
            $this->selectAll = false;
            return;
        }

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectAll = false;
                return;
            }
        }

        $this->selectAll = true;
    }

    public function export_excel()
    {


        if (!count($this->selected)) {
            return (new DispatchPaymentStack($this->exports, $this->service->uuid))->download(date('YmdHis-') . 'exportControlPayment.xlsx');
        } else {
            $notes = Production::WhereIn('id', $this->selected)->With('Note', 'User', 'Company')->sortBy('Note.days_left');

            return (new DispatchPaymentStack($notes, $this->service->uuid))->download(date('YmdHis-') . 'exportControledPayment.xlsx');
        }
    }

    public function go_priority_mass()
    {
        if (count($this->selected)) {
            $this->emit('setPriority', $this->selected);
        }
    }

    public function go_des_priority_mass()
    {
        if (count($this->selected)) {
            $this->emit('removePriority', $this->selected);
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
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }
        $_SESSION['filtro']['rubrica']  = $this->rubrica_s;
        $_SESSION['filtro']['city']     = $this->city_s;
        $_SESSION['filtro']['district'] = $this->district_s;
        $_SESSION['filtro']['region']   = $this->region_s;
        $_SESSION['filtro']['user']     = $this->user_fs;
        $_SESSION['filtro']['company']  = $this->company_fs;
        $this->emit('refresh_service');
    }

    public function filter_clean()
    {
        $this->gotoPage(1);
        $this->rubrica_s  = [];
        $this->city_s     = [];
        $this->district_s = [];
        $this->region_s   = [];
        $this->status_s   = [];
        $this->company_fs = [];
        $this->user_fs    = [];

        $this->multiSearch = [];

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filtro'])) {
            unset($_SESSION['filtro']);
        }

        $this->emit('refresh_service');
    }

    public function get_single_note($prod, $force = false)
    {
        $this->force    = $force;
        $this->selected = [$prod];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {

        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para atribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->productions = Production::find($this->selected);

        $this->notes = Note::whereHas('Productions', function ($query) {
            return $query->whereIn('id', $this->selected);
        })->get();

        if ($this->notes->count()) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'add_mass_notes',
            ]);
        }
    }

    public function go_des_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para desatribuição!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->productions = Production::with('Note')->find($this->selected);

        $notes_not_valids = 0;

        if ($this->productions) {
            foreach ($this->productions as $production) {
                if (($production->status > 2 && !$this->forcar) || $production->completed) {
                    $notes_not_valids++;
                }
            }
        }

        if ($notes_not_valids > 0) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição Parcial',
                'msg'           => "{$notes_not_valids} das Das {$this->productions->count()} selecionadas, não atende(m) o critério para Desatribuição. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Desatribua!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi Desatribuída.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição em Massa',
                'msg'           => "{$this->productions->count()} NOTAS/OVs estão prontas para serem desatribuídas. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Desatribua!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_des_att_mass',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nota foi Desatribuída.',

            ]);
        }
    }

    public function confirm_des_att_mass()
    {
        $erros = 0;
        $total = 0;

        if ($this->productions) {

            foreach ($this->productions as $production) {
                if (($production->status <= 2 || $this->forcar) && !$production->completed) {
                    $total++;
                    $previousUserId = $production->user_id;

                    if ($analise = Analise::Where('production_id', $production->id)->first()) {
                        $analise->delete();
                    }

                    if ($wpa = Wpa::Where('production_id', $production->id)->get()->last()) {
                        $wpa->update(['production_id' => null]);
                    }

                    if ($previousUserId) {
                        $five = $production->note?->FiveNote
                            ?? FiveNote::where('note_id', $production->note_id)->first();

                        if ($five) {
                            app(D5WorkflowService::class)->onProductionUnassigned(
                                $five,
                                $production,
                                auth()->id(),
                                $previousUserId
                            );
                        }
                    }

                    if (!$production->delete()) {
                        $erros++;
                    }
                }
            }

            if ($erros) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => "{$erros} de {$total} não foram desatribuídos.",
                ]);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => "{$total} Notas/Ovs Desatribídas com sucesso",
                    'timer'    => 2500,
                ]);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum registro de desatribuição. Repita o procedimento.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function confirm_att()
    {
        if ($this->type == '2') {

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
            'title'         => 'Confirmar Atribuir',
            'msg'           => "Você está prestes a Atribuir {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_dispatch',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function confirmed_att()
    {
        $this->force = true;

        if ($this->type == '2' || $this->force) {

            foreach ($this->notes as $key => $note) {

                // $production = Production::create([
                //     'note_id' => $note->id,
                //     'service_id' => $this->service->uuid,
                //     'user_id' => $this->user_s,
                //     'company_id' => $this->company_s,
                //     'dispatch_by' => Auth()->User()->id,
                //     'att_by' => Auth()->User()->id,
                //     'dt_note' => $note->dt_status,
                //     'status_note' => $note->nstats,
                //     'dispatch_at' => date('Y-m-d H:i:s'),
                //     'att_at' => date('Y-m-d H:i:s'),
                //     'status' => 2,
                // ]);

                $production = $this->productions->where('note_id', $note->id)->first();

                if ($production) {
                    $previousUserId = $production->user_id;

                    // $update = Production::find($production->id);

                    // dd($update);

                    if ($production->update([
                        'user_id'    => $this->user_s,
                        'company_id' => $this->company_s,
                        'att_by'     => Auth()->User()->id,
                        'att_at'     => date('Y-m-d H:i:s'),
                        'status'     => $this->user_s ? 2 : 1,
                        'completed'  => false,
                        'block'      => false,
                    ])) {

                        if (trim($this->user_s)) {
                            $user_info = 'Atribuiu a NOTA/OV para: ' . User::find($this->user_s) ? (User::find($this->user_s))->name : 'Desconhecido';
                        } else {
                            $user_info = 'Despachou a NOTA/OV para:' . Company::find($this->company_s) ? (Company::find($this->company_s))->name : 'Desconhecido';
                        }

                        Notetimeline::Create([
                            'note_id'      => $production->id,
                            'service_id'   => $production->service_id,
                            'user_id'      => Auth()->User()->id,
                            'info'         => "{$user_info}",
                            'status'       => $this->user_s ? 2 : 1,
                            'productionId' => $production->id,
                        ]);

                        // Wpa::create([
                        //     'production_id' => $production->id,
                        //     'note_id' => $note->id,
                        //     'dd' => $this->additionalData[$key]
                        // ]);
                        $five = $note->FiveNote ?? FiveNote::where('note_id', $note->id)->first();
                        if ($five) {
                            $five->productions()->syncWithoutDetaching([$production->id]);

                            app(D5WorkflowService::class)->onProductionAssigned(
                                $five,
                                $production,
                                auth()->id(),
                                $previousUserId
                            );
                        }
                    } else {

                        // dd($production);

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Erro ao atribuir as notas!',
                            'timer'    => 2500,
                        ]);

                        return;
                    }
                } else {
                    // dd($production, $note->note);

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'Erro ao atribuir as notas!',
                        'timer'    => 2500,
                    ]);

                    return;
                }
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas Despachadas com sucesso!',
                'timer'    => 2500,
            ]);
        }

        $this->closeall();
    }

    /**
     * Inserir as DDs ás notas em massa
     *
     * @return void
     */
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

    public function to_remove_add($id)
    {
        $this->production = Production::with('User')->find($id);

        if ($this->production) {
            $name = $this->production->User ? $this->production->User->name : 'Desconhecido';

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Desatribuição',
                'msg'           => "Você está prestes a Desatribuir a produção para {$name}. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_remove_att',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        }
    }

    public function remove_att()
    {
        $previousUserId = $this->production->user_id;

        if ($this->production->update(['user_id' => '', 'status' => 1, 'completed' => false])) {
            $five = $this->production->note?->FiveNote
                ?? FiveNote::where('note_id', $this->production->note_id)->first();

            if ($five && $previousUserId) {
                app(D5WorkflowService::class)->onProductionUnassigned(
                    $five,
                    $this->production,
                    auth()->id(),
                    $previousUserId
                );
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Produção foi desatribuída com sucesso',
                'timer'    => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum problema ao tentar remover a produção',
                'timer'    => 6000,
            ]);

            $this->closeall();
        }
    }

    public function getListsProperty()
    {
        return Production::with(['Note'])
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
            ->where('confirmed', false)
            ->where('service_id', $this->service->uuid)
            ->when($this->search, function ($q) {
                return $q->where(function ($query) {
                    $query->whereHas('Note', function ($subquery) {
                        return $subquery->where('note', 'like', '%' . $this->search . '%')
                            ->orWhere('group2', 'like', '%' . $this->search . '%')
                            ->orWhere('group3', 'like', '%' . $this->search . '%')
                            ->orWhere('group4', 'like', '%' . $this->search . '%')
                            ->orWhere('group5', 'like', '%' . $this->search . '%')
                            ->orWhere('numPedido', 'like', '%' . $this->search . '%')
                            ->orWhere('material', 'like', '%' . $this->search . '%')
                            ->orWhere('lexp', 'like', '%' . $this->search . '%')
                            ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                            ->orWhere('centerjob', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when(Auth()->User()->contract, function ($q) {
                return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
            })
            ->when($this->company_fs, function ($q) {
                return $q->whereIn('company_id', $this->company_fs);
            })
            ->when($this->user_fs, function ($q) {
                return $q->whereIn('user_id', $this->user_fs);
            })
            ->when($this->rubrica_s, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    $query->whereIn('rubrica', $this->rubrica_s);
                });
            })
            ->when($this->base, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('nexp', $this->base)
                        ->orwhere('nexp', null)
                        ->orwhere('nexp', '');
                });
            })
            ->when($this->multiSearch, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('note', $this->multiSearch);
                });
            })
            ->when($this->status_s, function ($q) {
                return $q->whereIn('productions.status', $this->status_s);
            })
            ->when($this->note_type, function ($q) {
                return $q->whereRelation('Note', 'type_note', $this->note_type);
            })

            ->select('productions.*', 'notes.dt_created as note_dt_created', 'latest_operation_resps.latest_fimLancado as fimLancado')
            ->orderBy('priority', 'DESC')
            ->orderBy('d5', 'DESC')
            ->orderBy('partial', 'DESC')
            ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
            ->orderBy('fimLancado', 'asc')
            ->orderBy('notes.type_note', 'DESC')
            ->paginate($this->perPage); // Seleciona a coluna 'dt_created' da tabela 'Note' com um alias 'note_dt_created'

    }

    public function getExportsProperty()
    {
        return Production::with(['Note.FiveNote'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('confirmed', false)
            ->where('service_id', $this->service->uuid)
            ->when($this->search, function ($q) {
                return $q->where(function ($query) {
                    $query->whereHas('Note', function ($subquery) {
                        return $subquery->where('note', 'like', '%' . $this->search . '%')
                            ->orWhere('group2', 'like', '%' . $this->search . '%')
                            ->orWhere('group3', 'like', '%' . $this->search . '%')
                            ->orWhere('group4', 'like', '%' . $this->search . '%')
                            ->orWhere('group5', 'like', '%' . $this->search . '%')
                            ->orWhere('numPedido', 'like', '%' . $this->search . '%')
                            ->orWhere('material', 'like', '%' . $this->search . '%')
                            ->orWhere('lexp', 'like', '%' . $this->search . '%')
                            ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                            ->orWhere('centerjob', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when(Auth()->User()->contract, function ($q) {
                return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
            })
            ->when($this->company_fs, function ($q) {
                return $q->whereIn('company_id', $this->company_fs);
            })
            ->when($this->user_fs, function ($q) {
                return $q->whereIn('user_id', $this->user_fs);
            })
            ->when($this->rubrica_s, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    $query->whereIn('rubrica', $this->rubrica_s);
                });
            })
            ->when($this->base, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('nexp', $this->base)
                        ->orwhere('nexp', null)
                        ->orwhere('nexp', '');
                });
            })
            ->when($this->multiSearch, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('note', $this->multiSearch);
                });
            })
            ->when($this->status_s, function ($q) {
                return $q->whereIn('productions.status', $this->status_s);
            })
            ->when($this->note_type, function ($q) {
                return $q->whereRelation('Note', 'type_note', $this->note_type);
            })
            ->orderBy('priority', 'DESC')
            ->orderBy('d5', 'DESC')
            ->orderBy('partial', 'DESC')
            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.days_left', 'asc')
            ->select('productions.*', 'notes.dt_created as note_dt_created'); // Seleciona a coluna 'dt_created' da tabela 'Note' com um alias 'note_dt_created'

    }

    public function getStatusProperty()
    {
        return Production::with(['Note'])
            ->orderBy('priority', 'DESC')
            ->orderBy('d5', 'DESC')
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where('confirmed', false)
            ->where('service_id', $this->service->uuid)
            ->when($this->search, function ($q) {
                return $q->where(function ($query) {
                    $query->whereHas('Note', function ($subquery) {
                        return $subquery->where('note', 'like', '%' . $this->search . '%')
                            ->orWhere('group2', 'like', '%' . $this->search . '%')
                            ->orWhere('group3', 'like', '%' . $this->search . '%')
                            ->orWhere('group4', 'like', '%' . $this->search . '%')
                            ->orWhere('group5', 'like', '%' . $this->search . '%')
                            ->orWhere('numPedido', 'like', '%' . $this->search . '%')
                            ->orWhere('material', 'like', '%' . $this->search . '%')
                            ->orWhere('lexp', 'like', '%' . $this->search . '%')
                            ->orWhere('rubrica', 'like', '%' . $this->search . '%')
                            ->orWhere('centerjob', 'like', '%' . $this->search . '%')
                            ->orWhereRelation('Orders', 'ordem', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when(Auth()->User()->contract, function ($q) {
                return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
            })
            ->when($this->company_fs, function ($q) {
                return $q->whereIn('company_id', $this->company_fs);
            })
            ->when($this->user_fs, function ($q) {
                return $q->whereIn('user_id', $this->user_fs);
            })
            ->when($this->rubrica_s, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    $query->whereIn('rubrica', $this->rubrica_s)->orWhereNull('rubrica');
                });
            })
            ->when($this->base, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('nexp', $this->base)->orWhereNull('nexp');
                });
            })
            ->when($this->multiSearch, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    return $query->whereIn('note', $this->multiSearch)
                        ->orWhereRelation('Orders', function ($q) {
                            $q->whereIn('ordem', $this->multiSearch);
                        });
                });
            })
            ->when($this->status_s, function ($q) {
                return $q->whereIn('productions.status', $this->status_s)->orWhereNull('productions.status');
            })
            ->when($this->note_type, function ($q) {
                return $q->whereRelation('Note', 'type_note', $this->note_type)->orWhereNull('type_note');
            })

            ->orderBy('notes.type_note', 'DESC')
            ->orderBy('notes.days_left', 'asc')
            ->select('productions.*', 'notes.dt_created as note_dt_created'); // Seleciona a coluna 'dt_created' da tabela 'Note' com um alias 'note_dt_created'

    }

    public function filterStatus($status)
    {
        if ($status) {
            $this->status_s   = [];
            $this->status_s[] = $status;
        }
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

        $this->emitSelf('refresh_list');
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
            $this->gotoPage(1);

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


        // if (!Auth()->User()->contract) {
        //     $this->company_l = Company::orderBy('name', 'ASC')->get();
        // } else {
        //     $this->company_l = Company::where('id', Auth()->User()->Employee->Contract->company_id)->get();
        // }

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
        // ->when($this->search_user, function ($q) {
        //     return $q->where('name', 'like', '%' . $this->search_user . '%');
        // })
        ->orderBy('name', 'ASC')->get();

        $this->user_fl = Production::where('service_id', $this->service->uuid)
            ->when(Auth()->user()->contract, function ($q) {
                return $q->where('company_id', Auth()->user()->employee->contract->company_id);
            })
            ->when($this->company_fs, function ($q) {
                return $q->whereIn('company_id', $this->company_fs);
            })
            ->select('user_id')
            ->with('User')
            ->groupBy('user_id')
            ->get();

        $this->status_l = $this->lists->pluck('status')->unique();

        // $this->user_l = User::whereRelation('Employee.Contract', 'company_id', $this->company_s)->orderBy('name')->get();

        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        //Filtros depedentes de bancos externos, testar antes.
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

        $lists = $this->lists;

        $this->recomputeSelectAllFor($lists?->items() ?? []);

        return view('livewire.dispatchs.payment.stack', [
            'allList' => $this->status->get(),
            'lists'   => $this->lists,
        ]);
    }
}
