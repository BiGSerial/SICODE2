<?php

namespace App\Http\Livewire\Services\Supervision;

use App\Custom\Notestatus;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\{BuildsCanceledWorkReportTags, BuildsLegalNoteTags, FinishesCanceledInforme};
use App\Jobs\Services\ExportSupervisionProductionListJob;
use App\Models\{City, File, Production, Service};
use App\Models\WorkReportFlowProduction;
use App\Services\Files\FileStorageService;
use App\Services\Production\CanceledInformeService;
use App\Support\Ops\{Deadline, Tone};
use App\Support\SicodeRules;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use WithNoteListFilters;
    use BuildsLegalNoteTags;
    use BuildsCanceledWorkReportTags;
    use FinishesCanceledInforme;

    protected $paginationTheme = 'bootstrap';

    public $service;           // Service (objeto)

    public $perPage = 25;

    public $search;

    public $note_type = '';

    public $limit_pause = 50;

    public $user_s;            // usuário selecionado (a tela não oferece o filtro; o Excel aceita)

    public $analise;

    public const TABS = ['producao', 'transferencias'];

    /** Aba aberta (na URL como `aba`). A lista de Transferências só é montada quando a aba está aberta. */
    public $tab = 'producao';

    protected $queryString = [
        'search'    => ['except' => '', 'as' => 'buscar'],
        'page'      => ['except' => 1, 'as' => 'p'],
        'perPage'   => ['as' => 'pp'],
        'note_type' => ['except' => '', 'as' => 'tipo'],
        'filters'   => ['except' => [], 'as' => 'f'],
        'tab'       => ['except' => 'producao', 'as' => 'aba'],
    ];

    protected $listeners = [
        'refresh_accomany'        => '$refresh',
        'refresh_list'            => '$refresh',
        'getCopy'                 => 'copy',
        'confirm_getAnalise'      => 'go_to_analise',
        'confirm_finish_canceled' => 'finishCanceled',
        'checkOpen',
    ];

    public function mount($service)
    {
        // Carrega o serviço e traz apenas os status úteis (sem exclusão)
        $this->service = Service::where('uuid', $service)
            ->with(['Status' => function ($q) {
                $q->where('exclusion', false)->select('service_id', 'value');
            }])
            ->firstOrFail();

        $this->tab = in_array($this->tab, self::TABS, true) ? $this->tab : 'producao';
    }

    // ---------- Filtros (WithListFilters): Região → Regional → Município ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return $this->locationFilterOptions();
    }

    protected function listFilterCacheScope(): string
    {
        return 'supervision-service';
    }

    /** Códigos de município (`notes.nexp`) dos filtros de localidade; vazio sem filtro. */
    protected function cityCodes(): array
    {
        $region   = $this->filterValues('region');
        $regional = $this->filterValues('regional');
        $city     = $this->filterValues('city');

        if (!$region && !$regional && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($regional, fn ($q) => $q->whereIn('baseConstrucao', $regional))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all() ?: ['__sem_municipio__'];
    }

    /** "Limpar filtros": filtros, busca e tipo. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters   = [];
        $this->search    = '';
        $this->note_type = '';
        $this->afterListFiltersChanged();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'producao';
    }

    public function exportToExcel()
    {
        ExportSupervisionProductionListJob::dispatch([
            'service_uuid'    => $this->service->uuid,
            'request_user_id' => auth()->id(),
            'target_user_id'  => $this->user_s ?: auth()->id(),
            'search'          => $this->search,
            'note_type'       => $this->note_type,
        ]);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'info',
            'title'    => 'Exportação iniciada!',
            'text'     => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    public function export_excel()
    {
        return $this->exportToExcel();
    }

    public function goTransferProd($prod_id)
    {
        $this->emit('transfer_production_lev', $prod_id);
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
        $check = Production::where('service_id', $this->service->uuid)
            ->where('user_id', auth()->id())
            ->where('status', 3) // em atividade
            ->when(auth()->user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());

                return count($companyIds)
                    ? $q->whereIn('company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->select('id', 'note_id', 'status')
            ->first();

        if ($check) {
            $this->emitTo('services.supervision.forms.jobform', 'showProduction', $check);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA.
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação adequada.
                    </p>",
            ]);
        }
    }

    public function go_to_analise()
    { /* opcional */
    }

    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        $pausedCount = Production::where('status', 4)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', auth()->id())
            ->count();

        $isThisPaused = optional(Production::select('status')->find($production))->status === 4;

        if ($pausedCount >= $this->limit_pause && !$isThisPaused) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você já atingiu o limite de pausa neste serviço. Ao iniciar esta nota, você não poderá colocá-la em espera. Continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação cancelada.',
            ]);
        } else {
            $this->emit('open_analise_lev', $this->analise);
            $this->dispatchBrowserEvent('showModal', ['id' => 'analise_form']);
        }
    }

    public function blockWaiting($status)
    {
        // Sem if (!session()->isStarted()) { session()->start(); } use helper nativo
        $waitingForm = session('waitingForm');

        return $waitingForm && (int)$status !== 27;
    }

    public function updatedNoteType()
    {
        $this->resetPage();
    }

    public function downloadFile($id)
    {
        if (!$file = File::select('path', 'file_name')->find($id)) {
            return;
        }

        $storage = app(FileStorageService::class);

        if ($storage->exists($file)) {
            return $storage->download($file, $file->file_name);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO INEXISTENTE!',
            'timer'    => 5000,
        ]);
    }

    /**
     * Query base (MariaDB).
     * - Calcula dias no SQL com DATEDIFF(CURDATE(), ...).
     * - Faz LEFT JOIN em work_forms para acessar informed_at.
     */
    protected function baseQuery(): Builder
    {
        $cityFilter = $this->cityCodes();

        // Cálculos (MariaDB)
        $daysAssignedExpr  = "DATEDIFF(CURDATE(), productions.att_at)";
        $daysDispatchExpr  = "DATEDIFF(CURDATE(), productions.dispatch_at)";
        $daysLeftExpr      = "IFNULL(DATEDIFF(CURDATE(), linked_work_reports.informed_at), 0)";
        $linkedWorkReports = DB::table('work_report_flow_productions as wrfp')
            ->join('work_reports as wr', 'wr.id', '=', 'wrfp.work_report_id')
            ->where('wrfp.stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
            ->where('wrfp.is_current', true)
            ->groupBy('wrfp.production_id')
            ->selectRaw('wrfp.production_id, MIN(wr.created_at) as created_at, MIN(wr.informed_at) as informed_at');

        return Production::query()
                    ->with([
                        'Note:id,note,material,mmgd,rubrica,lexp,postes,dt_status,type_note',
                        // WorkForm é o nome da relação no modelo Note que aponta para WorkReport (tabela work_reports)
                        'Note.WorkForm:id,note_id,informed_at,rejected',
                        'Note.WorkForm.Adsform:id,work_report_id,note_id,tacit,tacit_due_at,tacit_delivered_at,created_at',
                        // belongsToMany via order_work_report: NÃO existe work_form_id em orders
                        'Note.WorkForm.Orders' => fn ($q) => $q->select('orders.id', 'orders.ordem'),
                        // D5 legada (sem informe) - fallback quando o informe atual nao tem D5 propria.
                        'Note.FiveNote:id,note_id,work_report_id,note_d5,is_completed,is_supervisioned',
                        'Note.OldAds:id,note_id,date',
                        'Note.Adsform:id,work_report_id,note_id,tacit,tacit_due_at,tacit_delivered_at,created_at',
                        'Wpas:id,production_id,dd,created_at',
                        'fiveNotes:five_notes.id,note_id,work_report_id,note_d5,is_completed,is_supervisioned,is_archived',
                        'WorkReportFlowProductions' => fn ($q) => $q
                            ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                            ->where('is_current', true)
                            ->with([
                                'WorkReport:id,note_id,informed_at,created_at,rejected,selected_final_scopes',
                                'WorkReport.Orders' => fn ($orders) => $orders->select('orders.id', 'orders.ordem'),
                                'WorkReport.Adsform:id,work_report_id,note_id,tacit,tacit_due_at,tacit_delivered_at,created_at',
                                // D5 vinculada ao informe atual desta producao.
                                'WorkReport.FiveNote:id,note_id,work_report_id,note_d5,is_completed,is_supervisioned',
                            ]),
                        'Note.Files:id,service_id,note_id,file_name,path,ext',
                        'Note.Partials:id,note_id',
                        'Note.Files.note:id,note',
                        'Note.Files.service:uuid,service',
                        'measurementContext.fiveNote',
                    ])

            // JOIN necessário para created_at e days_left, sem multiplicar por todos os informes da nota.
            ->leftJoinSub($linkedWorkReports, 'linked_work_reports', function ($join) {
                $join->on('linked_work_reports.production_id', '=', 'productions.id');
            })

            ->where('productions.service_id', $this->service->uuid)
            ->when(
                $this->user_s,
                fn ($q) => $q->where('productions.user_id', $this->user_s),
                fn ($q) => $q->where('productions.user_id', auth()->id())
            )
            ->where('productions.completed', false)
            ->when(auth()->user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());

                return count($companyIds)
                    ? $q->whereIn('productions.company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })

            ->when($this->search, function (Builder $q, $search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->whereRelation('Note', 'note', 'like', "%{$search}%")
                        ->orWhereRelation('Note', 'material', 'like', "%{$search}%");
                });
            })
            ->when($this->note_type, function (Builder $q, $noteType) {
                $q->whereRelation('Note', 'type_note', $noteType);
            })
            ->when(!empty($cityFilter), function (Builder $q) use ($cityFilter) {
                $q->whereHas('Note', function (Builder $noteQuery) use ($cityFilter) {
                    $noteQuery->whereIn('nexp', $cityFilter);
                });
            })

            ->orderByDesc('priority')
            ->orderByDesc('partial')
            ->orderBy('work_dt_created', 'ASC')
            ->orderBy('att_at', 'DESC')
            ->orderBy('status', 'ASC')
            ->orderBy('productions.id', 'DESC')

            ->select([
                'productions.id',
                'productions.service_id',
                'productions.user_id',
                'productions.note_id',
                'productions.status',
                'productions.priority',
                'productions.partial',
                'productions.dfive',
                'productions.block',
                'productions.block_wpa',
                'productions.completed',
                'productions.att_at',
                'productions.dispatch_at',
                'productions.transferred',
                'linked_work_reports.created_at as work_dt_created',
                'linked_work_reports.informed_at as work_informed_at',
            ])
            ->selectRaw("$daysAssignedExpr as days_assigned")
            ->selectRaw("$daysDispatchExpr as days_dispatch")
            ->selectRaw("$daysLeftExpr as days_left");
    }

    public function getListsProperty()
    {
        // Retorna a query base (sem paginate)
        return $this->baseQuery();
    }

    /**
     * Tudo o que uma linha mostra, já com tom semântico (a view só desenha). Regras de cor (as mesmas de antes):
     * Informe: verde até 20 dias, âmbar de 21 a 27, vermelho a partir de 28; ADS: verde até 6 dias, âmbar até 9, vermelho acima.
     */
    private function presentRow(Production $list): array
    {
        $note         = $list->Note;
        $flowWorkForm = $list->WorkReportFlowProductions
            ->firstWhere('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
            ?->WorkReport;
        $workForm = $flowWorkForm ?? $note->WorkForm;
        $five     = app(\App\Services\D5\ProductionFiveNoteResolver::class)->resolve($list);
        // D5 (2º ciclo do informe): informe e ADS não são cobrados; ficam em cinza (`secondary`) com "D5" no lugar dos dias.
        $cycleTwo = (bool) ($five?->is_completed && !$five?->is_supervisioned);
        $adsForm  = $note->Adsform ?? $workForm?->Adsform;
        $adsDate  = $adsForm ? $adsForm->created_at : $note->OldAds?->sortByDesc('date')->first()?->date;
        $adsDate  = $adsDate ? Carbon::parse($adsDate) : null;
        $adsDays  = $adsDate ? (int) $adsDate->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : null;
        $tacit    = (bool) ($adsForm?->tacit ?? false);
        $rejected = (bool) $workForm?->rejected;
        $informed = $list->work_informed_at ? Carbon::parse($list->work_informed_at) : null;
        $lastWpa  = $list->Wpas->last();

        $showActions = !$list->block && !$list->block_wpa && !$this->blockWaiting($list->status);
        $partialId   = $list->partial && $note->Partials && $note->Partials->isNotEmpty() ? $note->Partials->last()->id : null;

        $status = match (true) {
            $list->transferred && $list->block_wpa => ['tone' => 'amber', 'label' => 'Aguardando despacho', 'hint' => 'Transferida; aguardando o despacho', 'history' => false],
            $rejected                              => ['tone' => 'amber', 'label' => 'Informe em revisão', 'hint' => 'O informe foi rejeitado e está em revisão', 'history' => false],
            default                                => ['tone' => Tone::forProductionStatus($list->status), 'label' => Notestatus::status($list->status)->status, 'hint' => 'Clique para ver o histórico de status', 'history' => true],
        };

        return [
            'id'           => (int) $list->id,
            'noteId'       => (int) $list->note_id,
            'note'         => $note->note,
            'isPartial'    => (bool) $list->partial,
            'isConnection' => $workForm ? collect($workForm->finalScopeBadges())->pluck('scope')->contains('connection') : false,
            'priority'     => (bool) $list->priority,
            'rejected'     => $rejected,
            'd5'           => $cycleTwo,
            'workReportId' => $workForm?->id,
            'orders'       => $workForm?->Orders ? $workForm->Orders->pluck('ordem')->filter()->values()->all() : [],
            'dd'           => $lastWpa?->dd,
            'files'        => $note->Files,
            'mmgd'         => (bool) $note->mmgd,
            'postes'       => $note->postes,
            'rubrica'      => $note->rubrica,
            'city'         => $note->lexp,
            'material'     => $note->material,
            'dispatchAt'   => $list->dispatch_at ? Carbon::parse($list->dispatch_at) : null,
            'dispatchDays' => $list->dispatch_at ? max(0, (int) $list->days_dispatch) : null,
            'attAt'        => $list->att_at ? Carbon::parse($list->att_at) : null,
            'attDays'      => $list->att_at ? max(0, (int) $list->days_assigned) : null,
            'informedAt'   => $informed,
            'informedDays' => (int) $list->days_left,
            'informedTone' => $informed ? ($cycleTwo ? 'secondary' : Deadline::age((int) $list->days_left, 20, 27)) : 'muted',
            'cycleTwo'     => $cycleTwo,
            'adsAt'        => $adsDate,
            'adsDays'      => $adsDays !== null ? max(0, $adsDays) : null,
            'adsTone'      => $cycleTwo ? 'secondary' : Deadline::age($adsDays, 6, 9),
            'adsTacit'     => $tacit,
            'adsDelivered' => (bool) ($adsForm?->tacit_delivered_at ?? false),
            'adsLegacy'    => !$tacit && $note->OldAds && $note->OldAds->isNotEmpty(),
            'status'       => $status,
            'statusCode'   => (int) $list->status,
            'canPlay'      => $showActions && !$list->completed,
            'canReturn'    => $showActions && $workForm && !$workForm->rejected && !$list->dfive,
            'dblclick'     => $workForm
                ? ['component' => 'partner.show.show-work-form', 'id' => $workForm->id]
                : ($partialId ? ['component' => 'partner.show.show-partial-info', 'id' => $partialId] : null),
        ];
    }

    public function render()
    {
        $lists = $this->lists->paginate($this->perPage);

        // Informe cancelado: a atividade não tem o que executar. Só finalizar (por cancelamento).
        $canceled = app(CanceledInformeService::class)->forProductions(collect($lists->items())->pluck('id')->all());

        return view('livewire.services.supervision.main', [
            'lists' => $lists,
            'rows'  => $lists->getCollection()->map(function (Production $list) use ($canceled) {
                $row = $this->presentRow($list);

                if (isset($canceled[$list->id]) && !$list->completed) {
                    $row['canPlay']   = $row['canReturn'] = false;
                    $row['canFinish'] = true;
                }

                return $row;
            }),
            'canceledByProduction' => $canceled,
            'legalTagsByNoteId'    => $this->buildLegalTagsByNoteIds(collect($lists->items())->pluck('note_id')->all()),
        ]);
    }
}
