<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobStatus;
use App\Jobs\Protests\ExportMonitoringProtestJobsJob;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Monitoring extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;

    /** Filtros */
    public string $search     = '';
    public string $searchName = '';
    public $userViewer        = null;
    public bool $onlySelectedUser = false;

    /** Filtro por tipo de nota (NA / OU / PR) */
    public ?string $typeNote  = null;

    /** Filtro por SLA (overdue / dueSoon / within) */
    public ?string $slaFilter = null;

    /** Lista de usuários para o select */
    public $userViewerList = [];
    public array $noteTypeOptions = [];

    public bool $showOnlyBtzero = false;
    public bool $hideBtzero = true;
    public ?string $deadlineCardFilter = null;

    protected $queryString = [
        'perPage'    => ['except' => 50],
        'search'     => ['except' => ''],
        'userViewer' => ['except' => null],
        'onlySelectedUser' => ['except' => false],
        'typeNote'   => ['except' => null],
        'slaFilter'  => ['except' => null],
        'deadlineCardFilter' => ['except' => null],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
        'refreshComponent' => '$refresh',
    ];

    public function mount($showOnlyBtzero = null, $hideBtzero = null): void
    {
        if (!is_null($showOnlyBtzero)) {
            $this->showOnlyBtzero = (bool) $showOnlyBtzero;
        }

        if (!is_null($hideBtzero)) {
            $this->hideBtzero = (bool) $hideBtzero;
        }

        if ($this->showOnlyBtzero) {
            $this->hideBtzero = false;
        }

        $this->loadUserViewerList();
        $this->loadNoteTypeOptions();
    }

    protected function loadUserViewerList(): void
    {
        $this->userViewerList = User::query()
            ->when($this->searchName !== '', function ($q) {
                $q->where('name', 'like', '%'.$this->searchName.'%');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedSearchName($value): void
    {
        $this->loadUserViewerList();
    }

    public function updatedTypeNote($value): void
    {
        $this->typeNote = $value ?: null;
        $this->resetPage();
    }

    public function updatedUserViewer($value): void
    {
        if (empty($value)) {
            $this->onlySelectedUser = false;
        }

        $this->resetPage();
    }

    public function updatedOnlySelectedUser(): void
    {
        $this->resetPage();
    }

    protected function loadNoteTypeOptions(): void
    {
        $this->noteTypeOptions = Protest::query()
            ->select('tipoNota')
            ->whereNotNull('tipoNota')
            ->distinct()
            ->orderBy('tipoNota')
            ->pluck('tipoNota')
            ->filter()
            ->values()
            ->toArray();
    }

    public function goTo($protestNote)
    {
        return redirect()->route('protests.dispatch.view', [
            'protest' => $protestNote,
        ]);
    }

    /** Ajusta o filtro por tipo de nota */
    public function setTypeNote(?string $type = null): void
    {
        $this->typeNote = $type ?: null;
        $this->resetPage();
    }

    /** Clicar no card de SLA (total/overdue/dueSoon/within) */
    public function setSlaFilter(?string $mode = null): void
    {
        $this->slaFilter = $mode ?: null;
        $this->resetPage();
    }

    public function setDeadlineCardFilter(?string $filter = null): void
    {
        if ($this->deadlineCardFilter === $filter) {
            $this->deadlineCardFilter = null;
        } else {
            $this->deadlineCardFilter = $filter;
        }

        $this->resetPage();
    }

    /** Query base dos jobs */
    protected function baseQuery(bool $ignoreDeadlineFilter = false)
    {
        $query = ProtestJob::query()
            ->with([
                'medProtest',
                'medProtest.Comments' => function ($q) {
                    $q->orderByDesc('created_at'); // última mensagem primeiro
                },
                'protest',
                'owner:id,name,company_id',
                'owner.company:id,name',
                'creator:id,name',
                'closer:id,name',
            ])
            ->where('confirmed', '!=', true)
            ->orderBy('priority', 'desc')
            ->orderBy('sla_due_at')
            ->orderBy('id');

        if ($this->showOnlyBtzero) {
            $query->whereHas('medProtest', function ($q) {
                $q->identifiedAsBtzero();
            });
        } elseif ($this->hideBtzero) {
            $query->where(function ($sub) {
                $sub->whereNull('med_protest_id')
                    ->orWhereHas('medProtest', function ($q) {
                        $q->notIdentifiedAsBtzero();
                    });
            });
        }

        // Filtro por responsável / hierarquia
        $query->when($this->userViewer, function ($q) {
            $user = User::find($this->userViewer);

            if (!$user) {
                return;
            }

            $ownerIds = $this->onlySelectedUser
                ? [$user->id]
                : $user->descendantsQuery(true, true, true)->pluck('users.id')->toArray();

            $onlySelectedUser = $this->onlySelectedUser;

            $q->where(function ($qq) use ($ownerIds, $onlySelectedUser) {
                $qq->whereIn('owner_id', $ownerIds);

                if (!$onlySelectedUser) {
                    $qq->orWhereNull('owner_id');
                }
            });
        });

        // Busca geral (topo)
        $query->when($this->search, function ($q) {
            $term = '%'.$this->search.'%';

            $q->where(function ($qq) use ($term) {
                $qq->where('id', 'like', $term)
                    ->orWhereHas('protest', function ($sub) use ($term) {
                        $sub->where('nota', 'like', $term)
                            ->orWhere('cidade', 'like', $term)
                            ->orWhere('txtGrpCodificacao', 'like', $term)
                            ->orWhere('codecodf', 'like', $term);
                    })
                    ->orWhereHas('owner', function ($sub) use ($term) {
                        $sub->where('name', 'like', $term);
                    });
            });
        });

        // Filtro por tipo de nota (NA / OU / PR)
        $query->when($this->typeNote, function ($q) {
            $type = $this->typeNote;

            $q->whereHas('protest', function ($sub) use ($type) {
                $sub->where('tipoNota', $type);
            });
        });

        // Filtro por SLA
        $query->when($this->slaFilter, function ($q) {
            $now = now();

            $q->whereNotNull('sla_due_at');

            if ($this->slaFilter === 'overdue') {
                $q->where('sla_due_at', '<', $now);
            } elseif ($this->slaFilter === 'dueSoon') {
                $q->whereBetween('sla_due_at', [$now, $now->clone()->addDays(3)]);
            } elseif ($this->slaFilter === 'within') {
                $q->where('sla_due_at', '>', $now->clone()->addDays(3));
            }
        });

        if (!$ignoreDeadlineFilter && $this->deadlineCardFilter) {
            $today = now()->toDateString();

            if ($this->deadlineCardFilter === 'due_today') {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', $today);
                    });
                });
            } elseif ($this->deadlineCardFilter === 'overdue') {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('protest', function ($sub) use ($today) {
                        $sub->where('tipoNota', 'NA')
                            ->whereDate('dtConclusaoDesej', '<', $today);
                    })->orWhereHas('medProtest', function ($sub) use ($today) {
                        $sub->whereDate('dtFimMedidaDesej', '<', $today);
                    });
                });
            } elseif ($this->deadlineCardFilter === 'finished_pending') {
                $query->where('status', ProtestJobStatus::DONE->value);
            }
        }

        return $query;
    }

    /** Lista paginada */
    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /** Estatisticas para os cards (inclui mensagens e prazos desejados) */
    public function getStatsProperty(): array
    {
        $base = $this->baseQuery();
        $jobs = (clone $base)->get();
        $total = $jobs->count();

        $overdue = 0;
        $dueSoon = 0;
        $within = 0;
        $referenceDate = now();

        $currentUserId = auth()->id();
        $respondedMessages = 0; // Ultima msg nao e do despachante
        $pendingForYouMessages = 0; // Ultima msg nao e do despachante e nao e do usuario logado

        foreach ($jobs as $job) {
            $desiredDate = $this->resolveDesiredDate($job);

            if ($desiredDate) {
                $diffInDays = $referenceDate->diffInDays($desiredDate, false);

                if ($diffInDays < 0) {
                    $overdue++;
                } elseif ($diffInDays <= 3) {
                    $dueSoon++;
                } else {
                    $within++;
                }
            } else {
                $within++;
            }

            $creatorId = $job->created_by
                ?? $job->creator_id
                ?? optional($job->creator)->id;

            if (!$creatorId) {
                continue;
            }

            $lastComment = $job->medProtest?->Comments?->first();

            if (!$lastComment) {
                continue;
            }

            $authorId = $lastComment->user_id;

            if (!$authorId) {
                continue;
            }

            $isFromDispatcher  = $authorId === $creatorId;
            $isFromCurrentUser = $currentUserId && $authorId === $currentUserId;

            if (!$isFromDispatcher) {
                $respondedMessages++;

                if (!$isFromCurrentUser) {
                    $pendingForYouMessages++;
                }
            }
        }

        $pct = function ($value) use ($total) {
            return $total > 0 ? round(($value / $total) * 100) : 0;
        };

        return [
            'total'                    => $total,
            'overdue'                  => $overdue,
            'overdue_pct'              => $pct($overdue),
            'dueSoon'                  => $dueSoon,
            'dueSoon_pct'              => $pct($dueSoon),
            'within'                   => $within,
            'within_pct'               => $pct($within),
            'responded_messages'       => $respondedMessages,
            'pending_messages_for_you' => $pendingForYouMessages,
        ];
    }

    public function getDeadlineSummaryProperty(): array
    {
        $jobs = $this->baseQuery(true)->get();
        $today = now()->startOfDay();

        $dueToday = 0;
        $overdue = 0;
        $finishedPending = 0;

        foreach ($jobs as $job) {
            if ($job->status === ProtestJobStatus::DONE) {
                $finishedPending++;
            }

            $desiredDate = $this->resolveDesiredDate($job);

            if (!$desiredDate) {
                continue;
            }

            $desired = $desiredDate instanceof Carbon
                ? $desiredDate->copy()->startOfDay()
                : Carbon::parse($desiredDate)->startOfDay();

            if ($desired->equalTo($today)) {
                $dueToday++;
            } elseif ($desired->lessThan($today)) {
                $overdue++;
            }
        }

        return [
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'finished_pending' => $finishedPending,
        ];
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function cleanFilters(): void
    {
        $this->reset(['userViewer', 'searchName', 'search', 'typeNote', 'slaFilter', 'deadlineCardFilter', 'onlySelectedUser']);
        $this->loadUserViewerList();
        $this->resetPage();
    }

    public function exportToExcel(): void
    {
        $filters = [
            'search' => $this->search,
            'userViewer' => $this->userViewer,
            'onlySelectedUser' => $this->onlySelectedUser,
            'typeNote' => $this->typeNote,
            'slaFilter' => $this->slaFilter,
            'showOnlyBtzero' => $this->showOnlyBtzero,
            'hideBtzero' => $this->hideBtzero,
            'deadlineCardFilter' => $this->deadlineCardFilter,
        ];

        ExportMonitoringProtestJobsJob::dispatch($filters, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO INICIADA',
            'text'     => 'A exportação foi iniciada, você receberá uma notificação quando estiver pronta.',
            'timer'    => 5000,
        ]);
    }

    public function render()
    {
        return view('livewire.protests.dispatch.monitoring', [
            'lists'          => $this->lists,
            'userViewerList' => $this->userViewerList,
            'noteTypeOptions' => $this->noteTypeOptions,
            'deadlineSummary' => $this->deadlineSummary,
        ]);
    }

    protected function resolveDesiredDate($job)
    {
        if ($job->protest?->tipoNota === 'NA') {
            return $job->protest?->dtConclusaoDesej;
        }

        return $job->medProtest?->dtFimMedidaDesej;
    }
}
