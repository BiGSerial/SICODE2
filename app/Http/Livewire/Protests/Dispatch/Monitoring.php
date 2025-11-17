<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\ProtestJob;
use App\Models\User;
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

    /** Filtro por tipo de nota (NA / OU / PR) */
    public ?string $typeNote  = null;

    /** Filtro por SLA (overdue / dueSoon / within) */
    public ?string $slaFilter = null;

    /** Lista de usuários para o select */
    public $userViewerList = [];

    protected $queryString = [
        'perPage'    => ['except' => 50],
        'search'     => ['except' => ''],
        'userViewer' => ['except' => null],
        'typeNote'   => ['except' => null],
        'slaFilter'  => ['except' => null],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount(): void
    {
        $this->loadUserViewerList();
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

    public function goTo($protestNote)
    {
        return redirect()->route('protests.dispatch.view', [
            'protest' => $protestNote,
        ]);
    }

    /** Clicar no card de tipo (NA/OU/PR ou “todos”) */
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

    /** Query base dos jobs */
    protected function baseQuery()
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
            ->orderBy('priority', 'desc');

        // Filtro por responsável / hierarquia
        $query->when($this->userViewer, function ($q) {
            $user = User::find($this->userViewer);

            if (!$user) {
                return;
            }

            $ownerIds = $user->descendantsQuery(true, true)->pluck('users.id')->toArray();

            $q->where(function ($qq) use ($ownerIds) {
                $qq->whereIn('owner_id', $ownerIds)
                    ->orWhereNull('owner_id');
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

        return $query;
    }

    /** Lista paginada */
    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /** Estatísticas para os cards (inclui mensagens e NA/OU/PR) */
    public function getStatsProperty(): array
    {
        $base = $this->baseQuery();

        $total = (clone $base)->count();

        $overdue = (clone $base)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->count();

        $dueSoon = (clone $base)
            ->whereNotNull('sla_due_at')
            ->whereBetween('sla_due_at', [now(), now()->addDays(3)])
            ->count();

        $within = max($total - $overdue - $dueSoon, 0);

        // Contagem por tipo de nota (NA / OU / PR)
        $na = (clone $base)
            ->whereHas('protest', fn ($q) => $q->where('tipoNota', 'NA'))
            ->count();

        $ou = (clone $base)
            ->whereHas('protest', fn ($q) => $q->where('tipoNota', 'OU'))
            ->count();

        $pr = (clone $base)
            ->whereHas('protest', fn ($q) => $q->where('tipoNota', 'PR'))
            ->count();

        // --- Mensagens ---
        $currentUserId   = auth()->id();
        $jobsForMessages = (clone $base)->get();

        $respondedMessages     = 0; // última msg ≠ despachante
        $pendingForYouMessages = 0; // última msg ≠ despachante e ≠ usuário logado

        foreach ($jobsForMessages as $job) {
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

            'na'                       => $na,
            'ou'                       => $ou,
            'pr'                       => $pr,

            'responded_messages'       => $respondedMessages,
            'pending_messages_for_you' => $pendingForYouMessages,
        ];
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function cleanFilters(): void
    {
        $this->reset(['userViewer', 'searchName', 'search', 'typeNote', 'slaFilter']);
        $this->loadUserViewerList();
        $this->resetPage();
    }

    public function exportToExcel(): void
    {
        // TODO: implementar exportação usando baseQuery()->get()
    }

    public function render()
    {
        return view('livewire.protests.dispatch.monitoring', [
            'lists'          => $this->lists,
            'userViewerList' => $this->userViewerList,
            'stats'          => $this->stats,
        ]);
    }
}
