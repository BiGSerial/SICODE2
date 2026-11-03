<?php

namespace App\Http\Livewire\Dispatchs\Payment;

use App\Exports\Dispatchs\DispatchPaymentStack;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Analise, Company, Note, Notetimeline, Production, Service, User, Wpa};
use App\Models\{City, FiveNote, WorkReportFlowProduction};
use App\Models\Company as CompanyModel;
use App\Services\D5\D5WorkflowService;
use App\Services\Payment\MeasurementDeadlinePolicy;
use App\Services\Production\ProductionActions;
use App\Services\WorkReports\{WorkReportFinalScopeOptions, WorkReportFlowProductionLinker};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Stack extends Component
{
    use WithPagination;
    use WithNoteListFilters;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    // VAr System
    public $service;

    public $last_update;

    public $search;

    public $measurementDeadlineStatus = '';

    public $perPage = 100;

    public $advanceSearch;

    public $multiSearch = [];

    public $note;

    public $notes;

    public $enter_dd;

    public $filteredLists;

    public $priority;

    public $selectAll;

    public $selected = [];

    public $company_s;

    public $user_s;

    public $type = '2';

    public $additionalData = [];

    public array $finalScopeOptions = [];

    public array $finalScopeSelections = [];

    // Filtros Municípios
    public $note_type = '';

    public $force = true;

    public $forcar = false;

    public $delete;

    public $production;

    public $productions;

    public $audits;

    protected $queryString = [
        'search'                    => ['except' => '', 'as' => 'buscar'],
        'page'                      => ['except' => 1, 'as' => 'p'],
        'perPage'                   => ['as' => 'pp'],
        'note_type'                 => ['except' => '', 'as' => 'tipo'],
        'measurementDeadlineStatus' => ['except' => '', 'as' => 'prazo'],
        'filters'                   => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_list'         => '$refresh',
        'confirm_remove_att'   => 'remove_att',
        'confirm_dispatch'     => 'confirmed_att',
        'getCopy'              => 'copy',
        'confirm_des_att_mass' => 'confirm_des_att_mass',
        'filterUser'           => 'filterUser',
        'confirm_reatt'        => 'confirmReattribute',
        'confirm_delete'       => 'confirmRemove',
        'closeall'             => 'closeall',
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;
    }

    /** Clique numa pessoa da Ocupação (menu lateral): filtra a pilha por ela e reflete no filtro "Usuário". */
    public function filterUser($user_id)
    {
        $this->filters['user'] = [(string) $user_id];
        $this->afterListFiltersChanged();
    }

    /** Production alvo de uma confirmação pendente (Re-atribuir / Remover despacho). */
    public ?int $pendingProductionId = null;

    private function productionInControl(int $id): Production
    {
        // Só age em Production visível neste controle (atividade, empresa e filtros de contrato).
        return $this->controlQuery(false)->with(['Note:id,note', 'User:id,name'])->findOrFail($id);
    }

    public function askReattribute(int $id): void
    {
        $production = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canReattribute($production), 403);

        $this->pendingProductionId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Re-atribuir',
            'msg'           => "Você deseja reatribuir a Nota/Ov <strong>{$production->Note->note}</strong> para <strong>{$production->User?->name}</strong>?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Re-atribua!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_reatt',
            'chave'         => hash('sha512', (string) $id),
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/OV foi reatribuída.',
        ]);
    }

    public function confirmReattribute($chave): void
    {
        $id = (int) $this->pendingProductionId;

        if (!$id || !hash_equals(hash('sha512', (string) $id), (string) $chave)) {
            return;
        }

        $this->pendingProductionId = null;
        $production                = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canReattribute($production), 403);

        $ok = app(ProductionActions::class)->reattribute($production);

        $this->dispatchBrowserEvent('swal', $ok
            ? ['position' => 'center', 'icon' => 'success', 'title' => 'Nota/Ov Re-atribuída com sucesso!', 'timer' => 2500]
            : ['position' => 'center', 'icon' => 'error', 'title' => 'Ocorreu um erro ao tentar re-atribuir a nota/ov.', 'timer' => 5000]);
    }

    public function askRemove(int $id): void
    {
        $production = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canRemove($production), 403);

        $this->pendingProductionId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'REMOVER DESPACHO',
            'msg'           => "Você está prestes a remover {$production->Note->note} da produção. Esteja ciente ao fazer isso de forma inadequada poderá prejudicar a medição do usuário ou empresa.\nLembrando que a exclusão também removerá do LOG do BI.\nDeseja Continuar?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_delete',
            'chave'         => hash('sha512', (string) $id),
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum despacho foi removido.',
        ]);
    }

    public function confirmRemove($chave): void
    {
        $id = (int) $this->pendingProductionId;

        if (!$id || !hash_equals(hash('sha512', (string) $id), (string) $chave)) {
            return;
        }

        $this->pendingProductionId = null;
        $production                = $this->productionInControl($id);
        abort_unless(app(ProductionActions::class)->canRemove($production), 403);

        $ok = app(ProductionActions::class)->remove($production);

        $this->dispatchBrowserEvent('swal', $ok
            ? ['position' => 'center', 'icon' => 'success', 'title' => 'Produção removida com sucesso', 'timer' => 2500]
            : ['position' => 'center', 'icon' => 'error', 'title' => 'Ocorreu algum problema ao tentar remover a produção', 'timer' => 6000]);
    }

    public function updatedNoteType(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    /** Cartão de status do painel: liga/desliga o status no filtro "Status". */
    public function toggleStatus(int $status): void
    {
        $current = $this->filterValues('status');
        $key     = (string) $status;

        $this->filters['status'] = in_array($key, $current, true)
            ? array_values(array_diff($current, [$key]))
            : [...$current, $key];

        if (!$this->filters['status']) {
            unset($this->filters['status']);
        }

        $this->afterListFiltersChanged();
    }

    public function clearAllCriteria(): void
    {
        $this->filters                   = [];
        $this->search                    = '';
        $this->multiSearch               = [];
        $this->advanceSearch             = '';
        $this->note_type                 = '';
        $this->measurementDeadlineStatus = '';
        $this->afterListFiltersChanged();
    }

    public function updatedMeasurementDeadlineStatus(): void
    {
        $this->resetPage();
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
            $notes = $this->exports->whereIn('productions.id', $this->selected);

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

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForProductions', array_values($this->selected));
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
        abort_unless(!auth()->user()?->usesContractDispatch(), 403);
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

        foreach ($this->notes as $note) {
            if (!$this->hasValidFinalScopeSelection($note)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => "Selecione o escopo medido para a nota {$note->note}.",
                    'timer'    => 6000,
                ]);

                return;
            }
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
                            'note_id'       => $production->note_id,
                            'service_id'    => $production->service_id,
                            'user_id'       => Auth()->User()->id,
                            'info'          => "{$user_info}",
                            'status'        => $this->user_s ? 2 : 1,
                            'production_id' => $production->id,
                        ]);

                        app(WorkReportFlowProductionLinker::class)->linkPaymentForScopes(
                            $production,
                            $this->selectedFinalScopesForNote($note),
                            'dispatch_payment_stack'
                        );

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
        abort_unless(!auth()->user()?->usesContractDispatch(), 403);
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

    protected function listFilterDefinitions(): array
    {
        $defs = [];

        if (!Auth()->User()->usesContractDispatch()) {
            $defs['company'] = ['label' => 'Empresa'];
        }

        return $defs + [
            'user'     => ['label' => 'Usuário'],
            'status'   => ['label' => 'Status'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        $user = Auth()->User();

        return static::class . $this->service->uuid . ($user->usesContractDispatch() ? md5(json_encode(\App\Support\SicodeRules::visibleCompanyIdsFor($user))) : 'all');
    }

    protected function listFilterOptions(): array
    {
        $user     = Auth()->User();
        $contract = $user->usesContractDispatch();
        $visible  = $contract ? \App\Support\SicodeRules::visibleCompanyIdsFor($user) : null;
        $uuid     = $this->service->uuid;

        // Quem tem Production na atividade (sem cache: a Ocupação filtra por gente que acabou de receber).
        $users = Production::query()
            ->where('service_id', $uuid)
            ->where('confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('company_id', $visible))
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $statuses = Production::query()
            ->where('service_id', $uuid)
            ->where('confirmed', false)
            ->when($visible, fn ($q) => $q->whereIn('company_id', $visible))
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return [
            'company' => $this->rememberFilterOptions('company', fn () => CompanyModel::query()
                ->whereHas('toUsers', fn ($q) => $q->whereRelation('ToServices', fn ($s) => $s->where('service_id', $uuid)->where('service', true)))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->all()),
            'user' => User::withTrashed()->whereIn('id', $users)->orderBy('name')->get(['id', 'name', 'deleted_at'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => $u->name . ($u->deleted_at ? ' (inativo)' : '')])->all(),
            'status'  => $statuses->map(fn ($s) => ['v' => (string) $s, 'l' => \App\Custom\Notestatus::status($s)->status])->values()->all(),
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => DB::table('productions')
                ->join('notes', 'notes.id', '=', 'productions.note_id')
                ->where('productions.service_id', $uuid)->where('productions.confirmed', false)
                ->whereNotNull('notes.rubrica')->where('notes.rubrica', '!=', '')
                ->distinct()->orderBy('notes.rubrica')->pluck('notes.rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    public function getBaseProperty(): array
    {
        $region = $this->filterValues('region');
        $local  = $this->filterValues('regional');
        $city   = $this->filterValues('city');

        if (!$region && !$local && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($local, fn ($q) => $q->whereIn('baseConstrucao', $local))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    /**
     * Consulta única da lista, do Excel e dos cartões de status: o mesmo recorte nos três (antes eram três cópias, com
     * regras diferentes). `$withStatus = false` ignora o filtro de status, para os cartões contarem todos os status.
     */
    private function controlQuery(bool $withStatus = true): Builder
    {
        $user    = Auth()->User();
        $company = $this->filterValues('company');
        $users   = $this->filterValues('user');
        $status  = $this->filterValues('status');
        $rubrica = $this->filterValues('rubrica');
        $base    = $this->base;
        $term    = $this->search;
        $multi   = $this->multiSearch;

        return Production::query()
            ->where('productions.confirmed', false)
            ->where('productions.service_id', $this->service->uuid)
            ->when($user->usesContractDispatch(), fn ($q) => $q->whereIn('productions.company_id', \App\Support\SicodeRules::visibleCompanyIdsFor($user)))
            ->when($company, fn ($q) => $q->whereIn('productions.company_id', $company))
            ->when($users, fn ($q) => $q->whereIn('productions.user_id', $users))
            ->when($withStatus && $status, fn ($q) => $q->whereIn('productions.status', $status))
            ->when($rubrica, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('rubrica', $rubrica)))
            ->when($base, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(fn ($w) => $w->whereIn('nexp', $base)->orWhereNull('nexp')->orWhere('nexp', ''))))
            ->when($multi, fn ($q) => $q->whereHas('Note', fn ($n) => $n->whereIn('note', $multi)->orWhereRelation('Orders', fn ($o) => $o->whereIn('ordem', $multi))))
            ->when(!$multi && $term, fn ($q) => $q->whereHas('Note', fn ($n) => $n->where(function ($w) use ($term) {
                $like = '%' . $term . '%';
                $w->where('note', 'like', $like)
                    ->orWhere('group2', 'like', $like)->orWhere('group3', 'like', $like)->orWhere('group4', 'like', $like)->orWhere('group5', 'like', $like)
                    ->orWhere('numPedido', 'like', $like)->orWhere('material', 'like', $like)->orWhere('lexp', 'like', $like)
                    ->orWhere('rubrica', 'like', $like)->orWhere('centerjob', 'like', $like)
                    ->orWhereRelation('Orders', 'ordem', 'like', $like);
            })))
            ->when($this->note_type, fn ($q) => $q->whereRelation('Note', 'type_note', $this->note_type));
    }

    public function getListsProperty()
    {
        $query = $this->controlQuery()->with([
            'User',
            'Company',
            'Note.FiveNote',
            'Note.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
            'Note.WorkForm:id,note_id,company_id,date,informed_at,created_at,rejected,selected_final_scopes',
            'Note.WorkForm.Company:id,name,deleted_at',
            'Note.WorkForm.Note:id,type_note',
            'Note.WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
            'Note.WorkForm.Orders.Pep:objeto,disponivel',
            'Note.WorkForm.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
            'Note.Partials.Company:id,name,deleted_at',
            'Note.Partials.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
            'Note.Partials.Orders.Pep:objeto,disponivel',
            'WorkReportFlowProductions' => fn ($q) => $q
                ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                ->where('is_current', true)
                ->with([
                    'WorkReport:id,note_id,company_id,date,informed_at,created_at,rejected,selected_final_scopes',
                    'WorkReport.Company:id,name,deleted_at',
                    'WorkReport.Note:id,type_note',
                    'WorkReport.Orders' => fn ($orders) => $orders->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
                    'WorkReport.Orders.Pep:objeto,disponivel',
                    'WorkReport.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
                    'WorkReport.FiveNote',
                ]),
        ])
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
            ->select('productions.*', 'notes.dt_created as note_dt_created', 'latest_operation_resps.latest_fimLancado as fimLancado')
            ->orderBy('priority', 'DESC')
            ->orderBy('d5', 'DESC')
            ->orderBy('partial', 'DESC')
            ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
            ->orderBy('fimLancado', 'asc')
            ->orderBy('notes.type_note', 'DESC');

        return $this->paginateDeadlineFiltered($query);
    }

    public function getExportsProperty()
    {
        return $this->controlQuery()
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->with(['Note.FiveNote'])
            ->select('productions.*', 'notes.dt_created as note_dt_created');
    }

    /** Contagem por status (cartões do painel), no mesmo recorte da lista mas sem o filtro de status. */
    public function getStatusCountsProperty(): array
    {
        return $this->controlQuery(false)
            ->reorder()
            ->selectRaw('productions.status as status, count(*) as total')
            ->groupBy('productions.status')
            ->pluck('total', 'status')
            ->all();
    }

    private function prepareFinalScopeSelection(Note $note): void
    {
        $options                               = app(WorkReportFinalScopeOptions::class)->forNote($note);
        $this->finalScopeOptions[$note->id]    = $options;
        $this->finalScopeSelections[$note->id] = [];

        if (count($options) === 1) {
            $this->finalScopeSelections[$note->id][$options[0]['scope']] = true;
        }
    }

    private function selectedFinalScopesForNote(Note $note): array
    {
        $selected = collect($this->finalScopeSelections[$note->id] ?? [])
            ->filter(fn ($enabled) => (bool) $enabled)
            ->keys()
            ->all();

        return app(WorkReportFinalScopeOptions::class)
            ->validScopesForNote($note, $selected);
    }

    private function hasValidFinalScopeSelection(Note $note): bool
    {
        $available = app(WorkReportFinalScopeOptions::class)->forNote($note);

        return count($available) <= 1 || !empty($this->selectedFinalScopesForNote($note));
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s            = '';
        $this->selected             = [];
        $this->user_s               = '';
        $this->type                 = '';
        $this->additionalData       = [];
        $this->finalScopeOptions    = [];
        $this->finalScopeSelections = [];
        $this->advanceSearch        = '';
        $this->gotoPage(1);

        $this->emitSelf('refresh_list');
    }

    public function clean()
    {

        $this->company_s            = '';
        $this->enter_dd             = '';
        $this->user_s               = '';
        $this->type                 = '';
        $this->additionalData       = [];
        $this->finalScopeOptions    = [];
        $this->finalScopeSelections = [];
        $this->multiSearch          = [];
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->gotoPage(1);
            $this->multiSearch = array_values($this->formatTextToArray($this->advanceSearch));
        } else {
            $this->multiSearch = [];
        }

        if (count($this->multiSearch)) {
            $this->selected  = [];
            $this->selectAll = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function deadline(Note $note)
    {
        $days       = 10;
        $date_forms = $note->WorkForm ? $note->WorkForm->informed_at : null;

        if ($date_forms) {

            $deadline_date = Carbon::parse($date_forms)->addDays($days);

            return Carbon::now()->diffInDays($deadline_date, false);
        } else {
            return 0;
        }
    }

    private ?MeasurementDeadlinePolicy $deadlinePolicy = null;

    /** @var array<int, ?array> Prazo por Production, calculado uma vez por requisição (tela e filtro de prazo leem o mesmo). */
    private array $deadlineMemo = [];

    public function measurementDeadlineFor(Production $production): ?array
    {
        return array_key_exists($production->id, $this->deadlineMemo)
            ? $this->deadlineMemo[$production->id]
            : $this->deadlineMemo[$production->id] = $this->computeMeasurementDeadline($production);
    }

    private function computeMeasurementDeadline(Production $production): ?array
    {
        $partial    = $production->partial ? $production->Note?->Partials?->last() : null;
        $workReport = $production->WorkReportFlowProductions
            ->firstWhere('stage', WorkReportFlowProduction::STAGE_PAYMENT)
            ?->WorkReport ?? $production->Note?->WorkForm;
        $informedAt = $partial?->supervision_at ?? $workReport?->informed_at;

        if (!$informedAt) {
            return null;
        }

        $policy        = $this->deadlinePolicy ??= app(MeasurementDeadlinePolicy::class);
        $dueAt         = $policy->dueAt(Carbon::parse($informedAt));
        $remainingDays = $policy->remainingDays($dueAt);

        return [
            'due_at'         => $dueAt,
            'remaining_days' => $remainingDays,
            'status'         => $policy->statusForRemainingDays($remainingDays),
        ];
    }

    private function paginateDeadlineFiltered($query): LengthAwarePaginator
    {
        if (!$this->measurementDeadlineStatus) {
            return $query->paginate($this->perPage);
        }

        $items = $query->get()->filter(function (Production $production): bool {
            return ($this->measurementDeadlineFor($production)['status'] ?? null) === $this->measurementDeadlineStatus;
        })->values();

        return new LengthAwarePaginator(
            $items->forPage($this->page, $this->perPage)->values(),
            $items->count(),
            $this->perPage,
            $this->page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
    private function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    public function render()
    {
        $lists = $this->lists;

        $this->recomputeSelectAllFor($lists?->items() ?? []);

        return view('livewire.dispatchs.payment.stack', [
            'lists'        => $lists,
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('note_id')->all()),
            'statusCounts' => $this->statusCounts,
        ]);
    }
}
