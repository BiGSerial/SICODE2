<?php

namespace App\Http\Livewire\Services\Desenho;

use App\Custom\Notestatus;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Models\{File, Production, Service};
use App\Services\Dispatchs\DesignListRowPresenter;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithListFilters;
    use WithPagination;
    use BuildsLegalNoteTags;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note_type = '';

    public $limit_pause = 3;

    public $analise;

    public $user_s;

    public $production;

    public $note;

    public $statusFilter = '';

    public bool $reviewCanFinish = false;

    public bool $notificationReviewHandled = false;

    public bool $showProjectReviewInProgress = false;

    public const TABS = ['producao', 'transferencias'];

    /** Aba aberta (na URL como `aba`): estado do servidor, para sobreviver a qualquer re-render. */
    public $tab = 'producao';

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'note_type'    => ['except' => '', 'as' => 'tipo'],
        'statusFilter' => ['except' => '', 'as' => 'status'],
        'filters'      => ['except' => [], 'as' => 'f'],
        'tab'          => ['except' => 'producao', 'as' => 'aba'],
    ];

    protected $listeners = [
        'refresh_accomany'                  => '$refresh',
        'getCopy'                           => 'copy',
        'confirm_getAnalise'                => 'go_to_analise',
        'force_check_open'                  => 'checkOpen',
        'openProjectReviewFromNotification' => 'openProjectReviewFromNotification',
    ];

    public function mount($service)
    {
        $this->service                     = Service::where('uuid', $service)->first();
        $this->showProjectReviewInProgress = (bool) session('desenho.show_project_review_in_progress', false);
        $this->tab                         = in_array($this->tab, self::TABS, true) ? $this->tab : 'producao';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'producao';
    }

    public function visualizar()
    {

    }

    public function goTransferProd($prod_id)
    {
        $production = Production::query()
            ->where('id', $prod_id)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->id())
            ->first();

        if (!$production) {
            return;
        }

        if ($production->hasActiveQualityProcess()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TRANSFERÊNCIA BLOQUEADA',
                'html'     => 'Atividades da Qualidade só podem ser redirecionadas pelo N1.',
                'timer'    => 3500,
            ]);

            return;
        }

        if ($this->isProjectReviewTracked($production)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TRANSFERÊNCIA BLOQUEADA',
                'html'     => 'Esta atividade está em tratativa de Análise de Projeto e não pode ser transferida.',
                'timer'    => 3800,
            ]);

            return;
        }

        $this->emit('transfer_production', $prod_id);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }
    }

    public function checkOpen()
    {
        if (!$this->notificationReviewHandled) {
            $this->notificationReviewHandled = true;

            $shouldOpenReview = request()->boolean('open_project_review');
            $productionId     = (int) request()->query('production', 0);
            $noteId           = (int) request()->query('note', 0);

            if ($shouldOpenReview && $productionId > 0) {
                $this->openProjectReviewFromNotification($productionId, $noteId > 0 ? $noteId : 0);

                return;
            }
        }

        $this->openActiveProductionModal(true);
    }

    private function openActiveProductionModal(bool $showLimitWarning): bool
    {
        $check = Production::where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->id())
            ->where('status', 3)
            ->first();

        if (!$check) {
            return false;
        }

        $this->emit('open_analise_draw', ['productionId' => $check->id, 'noteId' => $check->note_id]);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_form',
        ]);
        $this->emit('refresh_accomany');

        if ($showLimitWarning) {
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

        return true;
    }

    public function go_to_analise()
    {
        $this->emit('open_analise_draw', $this->analise);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_form',
        ]);
    }

    /** Consulta somente leitura de uma atividade da Qualidade (sem iniciar nem finalizar). */
    public function getQualityDetails($production)
    {
        $this->emitTo('services.desenho.forms.quality-closing', 'openQualityDetails', (int) $production);
        $this->dispatchBrowserEvent('showModal', ['id' => 'quality_closing_form']);
    }

    public function getAnalise($production, $note)
    {
        $this->reviewCanFinish = false;

        $productionModel = Production::query()
            ->where('id', $production)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->id())
            ->first();

        if (!$productionModel) {
            return;
        }

        $qualityProcess = $productionModel->activeQualityProcess()->first();

        if ($qualityProcess) {
            // Atividade originada pela Qualidade: nunca usa o encerramento normal do Desenho.
            if ($qualityProcess->state === \App\Enum\QualityProcessState::AWAITING_DESIGNER) {
                $this->emitTo('services.desenho.forms.quality-closing', 'openQualityClosing', (int) $productionModel->id);
                $this->dispatchBrowserEvent('showModal', ['id' => 'quality_closing_form']);

                return;
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'AGUARDANDO QUALIDADE',
                'html'     => 'Esta atividade está em análise da Qualidade (' . $qualityProcess->state->label() . ').',
                'timer'    => 3500,
            ]);

            return;
        }

        if (
            $this->isProjectReviewTracked($productionModel)
            && (int) $productionModel->status === Production::STATUS_IN_PROJECT_REVIEW
        ) {
            $this->openProjectReviewReadonly($productionModel->id, (int) $productionModel->note_id);

            return;
        }

        $this->analise = ['productionId' => $production, 'noteId' => $note];

        if ($this->limit_pause === Production::Where('status', 4)->Where('service_id', $this->service->uuid)->Where('user_id', Auth()->User()->id)->count() && $productionModel->status != 4) {
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
            $this->emit('open_analise_draw', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    public function openProjectReviewFromNotification(int $production, int $note): void
    {
        $productionModel = Production::query()
            ->where('id', $production)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->id())
            ->first();

        if (!$productionModel) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ATIVIDADE NÃO DISPONÍVEL',
                'html'     => 'A atividade indicada na notificação não está disponível para sua pilha atual.',
                'timer'    => 3400,
            ]);

            return;
        }

        if ($this->hasProjectReviewCycle($productionModel)) {
            $this->openProjectReviewReadonly($productionModel->id, (int) $productionModel->note_id, true);

            return;
        }

        $this->getAnalise($productionModel->id, (int) $productionModel->note_id);
    }

    public function openProjectReviewReadonly(int $production, int $note, bool $allowHistory = false): void
    {
        $productionModel = Production::query()
            ->where('id', $production)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', Auth()->id())
            ->first();

        $canOpenReadonly = $productionModel
            && ($allowHistory
                ? $this->hasProjectReviewCycle($productionModel)
                : $this->isProjectReviewTracked($productionModel));

        if (!$canOpenReadonly) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'VISUALIZAÇÃO INDISPONÍVEL',
                'html'     => 'A atividade não está disponível para visualização da Análise de Projeto.',
                'timer'    => 3200,
            ]);

            return;
        }

        $this->reviewCanFinish = in_array((int) $productionModel->status, [
            Production::STATUS_REJECTED_PROJECT_REVIEW,
            Production::STATUS_RELEASED_TO_FINISH,
        ], true);

        $this->analise = [
            'productionId'              => $productionModel->id,
            'noteId'                    => $productionModel->note_id,
            'viewOnlyProjectReview'     => true,
            'allowProjectReviewHistory' => $allowHistory,
        ];

        $this->emit('open_analise_draw', $this->analise);
        $this->dispatchBrowserEvent('openProjectReviewModalFromServer', [
            'payload' => $this->analise,
        ]);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_review_form',
        ]);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status = ''): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function toggleProjectReviewInProgress(): void
    {
        $this->showProjectReviewInProgress = !$this->showProjectReviewInProgress;
        session(['desenho.show_project_review_in_progress' => $this->showProjectReviewInProgress]);

        if (!$this->showProjectReviewInProgress && (int) $this->statusFilter === Production::STATUS_IN_PROJECT_REVIEW) {
            $this->statusFilter = '';
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedNoteType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /** "Limpar tudo": filtros, busca, tipo e status. Não mexe na quantidade por página nem no "mostrar em análise". */
    public function clearAllCriteria(): void
    {
        $this->filters      = [];
        $this->search       = '';
        $this->note_type    = '';
        $this->statusFilter = '';
        $this->afterListFiltersChanged();
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'noteStatus' => ['label' => 'Status da nota'],
            'location'   => ['label' => 'Localização'],
            'rubrica'    => ['label' => 'Rubrica'],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid . Auth()->id();
    }

    /** Opções vindas da própria pilha de quem está logado (sem cache: mudam a cada atividade que entra ou sai). */
    protected function listFilterOptions(): array
    {
        $options = fn (string $expression, array $except) => (clone $this->baseListQuery(true, $except))
            ->selectRaw($expression . ' as value')
            ->whereRaw($expression . ' IS NOT NULL')
            ->whereRaw($expression . " <> ''")
            ->groupBy(DB::raw($expression))
            ->orderBy('value')
            ->pluck('value')
            ->map(fn ($value) => ['v' => (string) $value, 'l' => (string) $value])
            ->all();

        return [
            'noteStatus' => $options($this->noteStatusExpression(), ['noteStatus']),
            'location'   => $options('notes.lexp', ['location']),
            'rubrica'    => $options('notes.rubrica', ['rubrica']),
        ];
    }

    public function getStatusFilterOptionsProperty(): array
    {
        $counts = (clone $this->baseListQuery(false))
            ->selectRaw('productions.status as status, COUNT(*) as total')
            ->groupBy('productions.status')
            ->pluck('total', 'status')
            ->map(fn ($count) => (int) $count)
            ->toArray();

        if (!count($counts)) {
            return [];
        }

        $options = [];

        foreach ($counts as $status => $count) {
            $statusId   = (int) $status;
            $statusMeta = Notestatus::status($statusId);
            $options[]  = [
                'value' => (string) $statusId,
                'label' => $statusMeta?->status ?? ('Status ' . $statusId),
                'count' => $count,
                'tone'  => \App\Support\Ops\Tone::forProductionStatus($statusId),
            ];
        }

        usort($options, function ($a, $b) {
            return strcmp((string) $a['label'], (string) $b['label']);
        });

        array_unshift($options, [
            'value' => '',
            'label' => 'Todos',
            'count' => array_sum($counts),
            'tone'  => 'teal',
        ]);

        return $options;
    }

    public function getProjectReviewInProgressCountProperty(): int
    {
        return (clone $this->baseListQuery(false, [], false))
            ->where('productions.status', Production::STATUS_IN_PROJECT_REVIEW)
            ->count();
    }

    private function baseListQuery(bool $applyStatusFilter = true, array $exceptFilters = [], bool $respectProjectReviewInProgressPreference = true)
    {
        return Production::where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->id());
            })
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->where(function ($q) {
                $q->where(function ($activeQuery) {
                    $activeQuery
                        ->where('productions.completed', false)
                        ->where(function ($statusQuery) {
                            $statusQuery
                                ->whereNull('productions.status')
                                ->orWhere('productions.status', '<>', 5);
                        });
                })
                    ->orWhere('productions.status', 4)
                    ->orWhere(function ($reviewQuery) {
                        $reviewQuery
                            ->whereIn('productions.status', [
                                Production::STATUS_IN_PROJECT_REVIEW,
                                Production::STATUS_REJECTED_PROJECT_REVIEW,
                                Production::STATUS_RELEASED_TO_FINISH,
                            ])
                            ->whereHas('ProjectReviewCycles');
                    });
            })
            ->when($respectProjectReviewInProgressPreference && !$this->showProjectReviewInProgress, function ($q) {
                return $q->where(function ($statusQuery) {
                    $statusQuery
                        ->whereNull('productions.status')
                        ->orWhere('productions.status', '<>', Production::STATUS_IN_PROJECT_REVIEW);
                });
            })
            ->when($this->search, function ($q, $s) {
                return $q->where(function ($query) use ($s) {
                    $query->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'material', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group1', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group2', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group3', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group4', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'group5', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'lexp', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'rubrica', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'numPedido', 'like', '%' . $s . '%')
                        ->orWhereRelation('Note', 'centerjob', 'like', '%' . $s . '%');
                });
            })
            ->where(function ($qualityQuery) {
                $qualityQuery
                    ->whereDoesntHave('activeQualityProcess')
                    ->orWhereHas('activeQualityProcess', fn ($processQuery) => $processQuery->where('state', \App\Enum\QualityProcessState::AWAITING_DESIGNER->value));
            })
            ->when($this->note_type, function ($q) {
                return $q->whereHas('Note', function ($query) {
                    $query->where('type_note', $this->note_type);
                });
            })
            ->when($applyStatusFilter && $this->statusFilter !== '', function ($q) {
                return $q->where('productions.status', (int) $this->statusFilter);
            })
            ->when(!in_array('noteStatus', $exceptFilters, true) && $this->filterValues('noteStatus'), function ($q) {
                return $q->whereIn(DB::raw($this->noteStatusExpression()), $this->filterValues('noteStatus'));
            })
            ->when(!in_array('location', $exceptFilters, true) && $this->filterValues('location'), function ($q) {
                return $q->whereIn('notes.lexp', $this->filterValues('location'));
            })
            ->when(!in_array('rubrica', $exceptFilters, true) && $this->filterValues('rubrica'), function ($q) {
                return $q->whereIn('notes.rubrica', $this->filterValues('rubrica'));
            });
    }

    private function noteStatusExpression(): string
    {
        return 'CASE WHEN notes.type_note = 1 THEN notes.centerjob ELSE notes.nstats END';
    }

    public function getListsProperty()
    {
        return $this->baseListQuery(true)
            ->with([
                'Note',
                'Note.Files.Note:id,note',
                'Note.Files.Service:uuid,service',
                'activeQualityProcess',
            ])
            ->orderBy('priority', 'desc')
            ->orderByRaw('CASE WHEN productions.status = ? THEN 1 ELSE 0 END ASC', [Production::STATUS_IN_PROJECT_REVIEW])
            ->orderBy('d5', 'desc')
            ->orderBy('notes.type_note', 'desc')
            ->orderBy('notes.days_left', 'asc')
            ->orderBy('productions.id', 'asc')
            ->select('productions.*', 'notes.dt_status', 'notes.is45', 'notes.type_note', 'notes.days_left')
            ->withExists(['ProjectReviewCycles as has_project_review_cycle'])
            ->paginate($this->perPage);
    }

    public function render()
    {
        $lists     = $this->lists;
        $presenter = app(DesignListRowPresenter::class);

        return view('livewire.services.desenho.main', [
            'lists'               => $lists,
            'rows'                => $lists->getCollection()->map(fn (Production $production) => $presenter->mine($production)),
            'legalTagsByNoteId'   => $this->buildLegalTagsByNoteIds(collect($lists->items())->pluck('note_id')->all()),
            'statusFilterOptions' => $this->statusFilterOptions,
        ]);
    }

    private function isProjectReviewTracked(Production $production): bool
    {
        if (!in_array((int) $production->status, [
            Production::STATUS_IN_PROJECT_REVIEW,
            Production::STATUS_REJECTED_PROJECT_REVIEW,
            Production::STATUS_RELEASED_TO_FINISH,
        ], true)) {
            return false;
        }

        return $this->hasProjectReviewCycle($production);
    }

    private function hasProjectReviewCycle(Production $production): bool
    {
        return $production->ProjectReviewCycles()->exists();
    }
}
