<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Enum\ProtestJobStatus;
use App\Enum\ProtestType;
use App\Helpers\TextFormatter;
use App\Jobs\Protests\ExportClosedProtestJobsJob;
use App\Models\ProtestJob;
use App\Models\User;
use App\Traits\WildcardFormmater;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\Exportable;

class Closeds extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;
    use WildcardFormmater;

    protected $paginationTheme = 'bootstrap';

    public int $perPage   = 200;

    /** Busca principal (nota da reclamação) */
    public string $search = '';

    /** Tipo de nota (NA / OU / ambos) */
    public string $typeNote = '';

    /** 1 = FORA SLA, 2 = DENTRO SLA, '' = todos */
    public $inPrazo = '';

    /** Filtro por usuário (hierarquia) */
    public string $searchName = '';
    public $userViewer = null;
    public $userViewerList = [];

    public string $protestTypeFilter = 'without_btzero';

    private string $filter_group = 'oexterno';
    private $filter;

    protected $queryString = [
        'typeNote'   => ['except' => '', 'as' => 'tipo'],
        'search'     => ['except' => '', 'as' => 'buscar'],
        'page'       => ['except' => 1, 'as' => 'p'],
        'perPage'    => ['as' => 'pp'],
        'inPrazo'    => ['except' => '', 'as' => 'emPrazo'],
        'userViewer' => ['except' => null, 'as' => 'usr'],
        'protestTypeFilter' => ['except' => 'without_btzero', 'as' => 'ptype'],
    ];

    public function mount($protestTypeFilter = null)
    {
        if (!is_null($protestTypeFilter)) {
            $this->protestTypeFilter = $protestTypeFilter;
        }

        $this->sanitizeProtestTypeFilter();
        $this->loadUserViewerList();
    }

    protected function loadUserViewerList(): void
    {
        $this->userViewerList = User::query()
            ->when($this->searchName !== '', function ($q) {
                $q->where('name', 'ilike', '%'.$this->searchName.'%');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedSearchName($value): void
    {
        $this->loadUserViewerList();
    }

    public function updatedProtestTypeFilter(): void
    {
        $this->sanitizeProtestTypeFilter();
        $this->resetPage();
    }

    public function goTo($protestNote)
    {
        return redirect()->route('protests.dispatch.view', [
            'protest' => $protestNote,
        ]);
    }

    /**
     * Histórico: jobs concluídos e cancelados.
     */
    protected function baseQuery()
    {
        $query = ProtestJob::query()
            ->whereIn('status', [
                ProtestJobStatus::DONE->value,
                ProtestJobStatus::CANCELED->value,
            ])
            ->where(function ($q) {
                $q->where('status', ProtestJobStatus::CANCELED->value)
                    ->orWhere(function ($done) {
                        $done->where('status', ProtestJobStatus::DONE->value)
                            ->where('confirmed', true);
                    });
            })
            ->with([
                'protest.Notes',
                'protest.medProtests',
                'medProtest',
                'creator:id,name',
                'owner:id,name,company_id',
                'owner.company:id,name',
                'closer:id,name',
            ]);

        // Filtro hierárquico por usuário (dono do job)
        $query->when($this->userViewer, function ($q) {
            $user = User::find($this->userViewer);
            if (!$user) {
                return;
            }

            $ownerIds = $user->descendantsQuery(true, true)
                ->pluck('users.id')
                ->toArray();

            if (empty($ownerIds)) {
                // garante que não retorna nada se não houver descendentes
                $q->whereRaw('1 = 0');
                return;
            }

            $q->whereIn('owner_id', $ownerIds);
        });

        // Tipo de nota (NA / OU / ambos)
        $query->when($this->typeNote !== '', function ($q) {
            $q->whereHas('protest', function ($sub) {
                $sub->where('tipoNota', $this->typeNote);
            });
        });

        // Prazo SLA
        $query->when($this->inPrazo !== '', function ($q) {
            if ((int) $this->inPrazo === 1) {
                // FORA do SLA
                $q->whereNotNull('finished_at')
                    ->whereNotNull('sla_due_at')
                    ->whereColumn('finished_at', '>', 'sla_due_at');
            } elseif ((int) $this->inPrazo === 2) {
                // DENTRO do SLA
                $q->whereNotNull('finished_at')
                    ->whereNotNull('sla_due_at')
                    ->whereColumn('finished_at', '<=', 'sla_due_at');
            }
        });

        // Busca por nota da reclamação
        $query->when($this->search !== '', function ($q) {
            $formatted = $this->formatWithWildcard($this->search);

            $q->whereHas('protest', function ($sub) use ($formatted) {
                $sub->where('nota', $formatted->type, $formatted->search);
            });
        });

        if ($this->protestTypeFilter === 'only_btzero') {
            $query->whereHas('protest.medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', ProtestType::BTZERO->value);
            });
        } elseif ($this->protestTypeFilter === 'without_btzero') {
            $query->whereDoesntHave('protest.medProtests', function ($q) {
                $q->where('statusSist', 'MEDA')
                    ->where('protest_type', ProtestType::BTZERO->value);
            });
        }

        return $query
            ->orderByDesc('finished_at')
            ->orderByDesc('sent_at');
    }

    private function sanitizeProtestTypeFilter(): void
    {
        $allowed = ['only_btzero', 'without_btzero', 'all'];

        if (!in_array($this->protestTypeFilter, $allowed, true)) {
            $this->protestTypeFilter = 'without_btzero';
        }
    }

    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /**
     * Estatísticas para os cards de resumo.
     */
    public function getStatsProperty(): array
    {
        $base  = $this->baseQuery();
        $total = (clone $base)->count();

        $jobs = (clone $base)->get();

        $diffDays = [];
        $withinContract = 0;
        $outContract    = 0;
        $withinSla      = 0;
        $outSla         = 0;

        foreach ($jobs as $job) {
            $protest    = $job->protest;
            $openedAt   = $protest?->dtAberturaNota;
            $dueAt      = $protest?->dtConclusaoDesej;
            $finishedAt = $job->finished_at;
            $slaDueAt   = $job->sla_due_at;

            if ($openedAt && $finishedAt) {
                $diffDays[] = $openedAt->startOfDay()->diffInDays($finishedAt->startOfDay());
            }

            if ($dueAt && $finishedAt) {
                if ($finishedAt->gt($dueAt)) {
                    $outContract++;
                } else {
                    $withinContract++;
                }
            }

            if ($slaDueAt && $finishedAt) {
                if ($finishedAt->gt($slaDueAt)) {
                    $outSla++;
                } else {
                    $withinSla++;
                }
            }
        }

        $avgClosingDays = count($diffDays)
            ? round(array_sum($diffDays) / count($diffDays), 1)
            : null;

        $pct = function ($value) use ($total) {
            return $total > 0 ? round(($value / $total) * 100) : 0;
        };

        return [
            'total'                   => $total,
            'avg_closing_days'        => $avgClosingDays,

            'within_contract'         => $withinContract,
            'within_contract_pct'     => $pct($withinContract),
            'out_contract'            => $outContract,
            'out_contract_pct'        => $pct($outContract),

            'within_sla'              => $withinSla,
            'within_sla_pct'          => $pct($withinSla),
            'out_sla'                 => $outSla,
            'out_sla_pct'             => $pct($outSla),
        ];
    }

    public function exportToExcel(): void
    {
        $filters = [
            'typeNote'           => $this->typeNote,
            'search'             => $this->search,
            'inPrazo'            => $this->inPrazo,
            'userViewer'         => $this->userViewer,
            'protestTypeFilter'  => $this->protestTypeFilter,
        ];

        ExportClosedProtestJobsJob::dispatch($filters, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => "<div class='card'><div class='card-body'>
            <p>Seu arquivo está sendo gerado.</p>
            <p class='mb-0'><strong>Quando concluir, o link aparecerá na sua Central de Notificações.</strong></p>
        </div></div>",
            'timer'    => 5000,
        ]);
    }

    public function render()
    {
        return view('livewire.protests.dispatch.closeds', [
            'lists'        => $this->lists,
            'stats'        => $this->stats,
            'userViewerList' => $this->userViewerList,
        ]);
    }
}
