<?php

namespace App\Http\Livewire\Services\Levantamento;

use App\Jobs\Services\ExportLevantamentoProductionListJob;
use App\Models\{File, Production, QualityProcess, Service};
use App\Services\Files\FileStorageService;
use App\Services\Quality\{QualityBulkSearch, QualityDesignerResolver, QualityWorkflowException, QualityWorkflowService};
use App\Support\SicodeRules;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Livewire\{Component, WithPagination};
use Throwable;

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 30;

    public $search;

    public $analise;

    public string $qualityNotes = '';

    public ?string $exportFeedback = null;

    public bool $exportFailed = false;

    /** @var array<int, array<string, mixed>> */
    public array $qualityCandidates = [];

    /** @var array<int, int|string> */
    public array $selectedQualityProcessIds = [];

    protected $limit_pause = 50;

    private string $filter_group = 'survey';

    private array $filter = [];

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'page'   => ['except' => 1],
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->firstOrFail();
        $this->loadFilters();
    }

    private function loadFilters(): void
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        $filters = session("filter.{$this->filter_group}", []);

        if ((!is_array($filters) || $filters === []) && isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            $filters = $_SESSION['filter'][$this->filter_group];
        }

        $this->filter = is_array($filters) ? $filters : [];
    }

    /** =====================
     * 🔹 EXPORTAÇÃO
     * ===================== */
    public function exportToExcel(): void
    {
        try {
            ExportLevantamentoProductionListJob::dispatch([
                'service_uuid' => $this->service->uuid,
                'user_id'      => Auth::id(),
                'search'       => $this->search,
            ]);
        } catch (Throwable $exception) {
            Log::error('Não foi possível enfileirar exportação de Levantamento.', [
                'service_uuid' => $this->service->uuid,
                'user_id'      => Auth::id(),
                'exception'    => $exception,
            ]);

            $this->exportFailed   = true;
            $this->exportFeedback = 'Não foi possível iniciar a exportação. Tente novamente.';
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Exportação não iniciada',
                'text'     => $this->exportFeedback,
            ]);

            return;
        }

        $this->exportFailed   = false;
        $this->exportFeedback = 'Exportação enfileirada. Você será notificado quando o arquivo estiver pronto.';

        $this->dispatchBrowserEvent('swal', [
            'toast'             => true,
            'position'          => 'top-end',
            'icon'              => 'info',
            'title'             => 'Exportação enfileirada',
            'text'              => $this->exportFeedback,
            'showConfirmButton' => false,
            'timer'             => 4500,
            'timerProgressBar'  => true,
        ]);
    }

    public function export_excel()
    {
        return $this->exportToExcel();
    }

    /** Busca somente processos da empresa ainda disponíveis na fila inicial do N1; nunca o Pool. */
    public function searchQualityAvailable(QualityBulkSearch $bulk, QualityDesignerResolver $designers): void
    {
        $notes                           = $bulk->parse($this->qualityNotes);
        $this->selectedQualityProcessIds = [];

        if (!$notes) {
            $this->qualityCandidates = [];

            return;
        }

        $user      = Auth::user();
        $processes = QualityProcess::query()->with(['Note', 'Company', 'CurrentDesigner'])
            ->whereHas('Note', fn ($q) => $q->where(fn ($w) => $w->whereIn('note', $notes)->orWhereIn('numPedido', $notes)))
            ->where('company_id', $user->company_id)
            ->active()->get()->keyBy('note_id');

        $noteModels = \App\Models\Note::query()->where(fn ($q) => $q->whereIn('note', $notes)->orWhereIn('numPedido', $notes))->get();
        // Notas são números: não use flatMap/mapWithKeys, pois o Collection/PHP renumera chaves numéricas.
        $byValue = [];

        foreach ($noteModels as $note) {
            $byValue['note:' . $note->note] = $note;

            if ($note->numPedido) {
                $byValue['note:' . $note->numPedido] = $note;
            }
        }

        $this->qualityCandidates = collect($notes)->unique()->map(function ($value) use ($processes, $byValue, $designers, $user) {
            $note      = $byValue['note:' . $value] ?? null;
            $process   = $note ? $processes->get($note->id) : null;
            $available = $process
                && $process->state === \App\Enum\QualityProcessState::AWAITING_N1_DISPATCH
                && $designers->isEligible($process, $user->id);

            $reason = match (true) {
                !$process                                                               => 'Não encontrada na fila disponível da Qualidade.',
                $process->state !== \App\Enum\QualityProcessState::AWAITING_N1_DISPATCH => 'Já atribuída a um usuário ou em outra etapa da Qualidade.',
                !$designers->isEligible($process, $user->id)                            => 'Seu usuário não está habilitado na atividade deste ciclo.',
                default                                                                 => null,
            };

            return [
                'process_id' => $process?->id,
                'note'       => $note?->note ?? $value,
                'material'   => $note?->material,
                'company'    => $process?->Company?->name,
                'available'  => $available,
                'reason'     => $reason,
            ];
        })->values()->all();

        $this->selectedQualityProcessIds = collect($this->qualityCandidates)->where('available', true)->pluck('process_id')->all();
    }

    public function claimQualityAvailable(QualityWorkflowService $workflow): void
    {
        $allowed  = collect($this->qualityCandidates)->where('available', true)->pluck('process_id')->map(fn ($id) => (int) $id)->all();
        $selected = collect($this->selectedQualityProcessIds)->map(fn ($id) => (int) $id)->intersect($allowed)->unique();

        if ($selected->isEmpty()) {
            $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'warning', 'title' => 'NENHUMA OBRA SELECIONADA', 'text' => 'Selecione ao menos uma obra disponível.']);

            return;
        }

        $done = 0;

        foreach ($selected as $id) {
            try {
                $workflow->claimN1Dispatch(QualityProcess::findOrFail($id), Auth::user());
                $done++;
            } catch (QualityWorkflowException $e) {
                foreach ($this->qualityCandidates as &$candidate) {
                    if ((int) ($candidate['process_id'] ?? 0) === $id) {
                        $candidate['available'] = false;
                        $candidate['reason']    = $e->getMessage();
                    }
                }
                unset($candidate);
            }
        }

        $this->selectedQualityProcessIds = [];
        $this->dispatchBrowserEvent('hideModal', ['id' => 'quality_available_modal']);
        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => $done ? 'success' : 'warning', 'title' => $done ? 'OBRAS ATRIBUÍDAS' : 'NENHUMA OBRA ATRIBUÍDA', 'text' => $done ? "{$done} obra(s) foram incluídas na sua produção." : 'As obras foram assumidas por outro usuário ou deixaram de estar disponíveis.']);
        $this->emitSelf('$refresh');
    }

    /** =====================
     * 🔹 EVENTOS DE INTERFACE
     * ===================== */
    public function goTransferProd($prod_id)
    {
        if (Production::find($prod_id)?->hasActiveQualityProcess()) {
            $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'warning', 'title' => 'TRANSFERÊNCIA BLOQUEADA', 'html' => 'Atividades da Qualidade só podem ser redirecionadas pelo N1.', 'timer' => 3500]);

            return;
        }

        $this->emit('transfer_production_lev', $prod_id);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    /** =====================
     * 🔹 VERIFICA SE HÁ NOTA EM ATIVIDADE
     * ===================== */
    public function checkOpen()
    {
        $check = Production::where('service_id', $this->service->uuid)
            ->where('user_id', Auth::id())
            ->where('status', 3)
            ->first();

        if ($check) {
            $this->emit('open_analise_lev', [
                'productionId' => $check->id,
                'noteId'       => $check->note_id,
            ]);

            $this->dispatchBrowserEvent('showModal', ['id' => 'analise_form']);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "
                    Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA.
                    <p class='text-bg-light mt-2 p-2'>
                        Existe um limite para interromper notas. Ao atingi-lo, será necessário tratá-las adequadamente.
                    </p>
                ",
            ]);
        }
    }

    /** =====================
     * 🔹 ABERTURA DE ANÁLISE
     * ===================== */
    /** Consulta somente leitura de uma atividade da Qualidade (sem iniciar nem finalizar). */
    public function getQualityDetails($production)
    {
        $this->emitTo('services.desenho.forms.quality-closing', 'openQualityDetails', (int) $production);
        $this->dispatchBrowserEvent('showModal', ['id' => 'quality_closing_form']);
    }

    public function getAnalise($production, $note)
    {
        // Atividade originada pela Qualidade: nunca usa o encerramento normal do Levantamento.
        $qualityProcess = Production::query()->where('id', $production)->where('user_id', Auth::id())->first()?->activeQualityProcess()->first();

        if ($qualityProcess) {
            if ($qualityProcess->state === \App\Enum\QualityProcessState::AWAITING_DESIGNER) {
                $this->emitTo('services.desenho.forms.quality-closing', 'openQualityClosing', (int) $production);
                $this->dispatchBrowserEvent('showModal', ['id' => 'quality_closing_form']);
            } else {
                $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'info', 'title' => 'AGUARDANDO QUALIDADE', 'html' => 'Esta atividade está em análise da Qualidade (' . $qualityProcess->state->label() . ').', 'timer' => 3500]);
            }

            return;
        }

        $this->analise = ['productionId' => $production, 'noteId' => $note];

        $pausedCount = Production::where('status', 4)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', Auth::id())
            ->count();

        $isPaused = Production::find($production)?->status === 4;

        if ($pausedCount >= $this->limit_pause && !$isPaused) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'LIMITE DE PAUSA ATINGIDO',
                'msg'           => "Você já atingiu o limite de pausas neste serviço. Ao iniciar esta nota, não será possível colocá-la em espera. Deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, continuar',
                'btnCanceltxt'  => 'Não, cancelar',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação cancelada.',
            ]);
        } else {
            $this->go_to_analise();
        }
    }

    public function go_to_analise()
    {
        if ($this->analise) {
            $this->emit('open_analise_lev', $this->analise);
            $this->dispatchBrowserEvent('showModal', ['id' => 'analise_form']);
        }
    }

    /** =====================
     * 🔹 DOWNLOAD DE ARQUIVOS
     * ===================== */
    public function downloadFile($id)
    {
        $file = File::find($id);

        $storage = app(FileStorageService::class);

        if (!$file || !$storage->exists($file)) {
            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'error',
                'title' => 'Arquivo inexistente!',
                'timer' => 4000,
            ]);

            return;
        }

        return $storage->download($file, $file->file_name);
    }

    /** =====================
     * 🔹 CONSULTA PRINCIPAL
     * ===================== */
    public function getListsProperty()
    {
        $this->loadFilters();
        $cityFilter = collect((array) ($this->filter['city'] ?? []))
            ->filter(fn ($city) => filled($city))
            ->values()
            ->all();

        $pzoExpr = "
        CASE
            WHEN n.type_note = 1
            AND n.mesalization REGEXP '^M[0-9]{1,2}/[0-9]{4}$' THEN
                CASE
                    WHEN CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) BETWEEN 1 AND 12 THEN
                        DATE_ADD(
                            DATE_ADD(
                                MAKEDATE(
                                    CAST(SUBSTRING_INDEX(n.mesalization, '/', -1) AS UNSIGNED),
                                    1
                                ),
                                INTERVAL (CAST(SUBSTRING(SUBSTRING_INDEX(n.mesalization, '/', 1), 2) AS UNSIGNED) - 1) MONTH
                            ),
                            INTERVAL 27 DAY
                        )
                    ELSE NULL
                END
            WHEN n.type_note = 2 THEN
                DATE_ADD(CURDATE(), INTERVAL COALESCE(n.days_left, 0) DAY)
            ELSE NULL
        END
    ";

        return Production::query()
            ->select([
                'productions.id',
                'productions.note_id',
                'productions.status',
                'productions.priority',
                'productions.att_at',
                'productions.completed',
                'productions.block',
                'productions.block_wpa',
                'productions.transferred',
                'n.note',
                'n.material',
                'n.group1',
                'n.group2',
                'n.rubrica',
                'n.lexp',
                'n.days_left',
                'n.mmgd',
                'n.dt_created',
                DB::raw("$pzoExpr AS pzo"),
            ])
            ->join('notes as n', 'n.id', '=', 'productions.note_id')
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.user_id', Auth::id())
            ->where('productions.completed', false)
            ->where(function ($quality) {
                $quality->whereDoesntHave('activeQualityProcess')
                    ->orWhereHas('activeQualityProcess', fn ($process) => $process->where('state', \App\Enum\QualityProcessState::AWAITING_DESIGNER->value));
            })
            ->when(Auth::user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(Auth::user());

                return count($companyIds)
                    ? $q->whereIn('productions.company_id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->when($this->search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('n.note', 'like', "%{$s}%")
                        ->orWhere('n.material', 'like', "%{$s}%");
                });
            })
            ->when(!empty($cityFilter), fn ($q) => $q->whereIn('n.nexp', $cityFilter))
            ->addSelect('n.dt_created as dt_created')
            ->orderByDesc('productions.priority')
            ->orderBy('n.dt_created')
            ->orderBy('productions.id', 'DESC')
            ->with([
                'Wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'Service:id,uuid,service',
                'User:id,name',
                'Note:id,note,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,dt_created,material,group2',
            ])
            ->paginate($this->perPage);
    }

    /** =====================
     * 🔹 RENDERIZAÇÃO
     * ===================== */
    public function render()
    {
        return view('livewire.services.levantamento.main', [
            'lists' => $this->lists,
        ]);
    }
}
