<?php

namespace App\Http\Livewire\Protests\Partner;

use App\Models\ProtestJob;
use App\Models\User;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WildcardFormmater;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;
    public string $search = '';
    public ?string $dt_start = null;
    public ?string $dt_end = null;
    public ?string $month = null;

    protected ?Collection $authorizedUserIds = null;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    protected $queryString = [
        'perPage' => ['as' => 'pagina'],
    ];

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['perPage', 'search', 'dt_start', 'dt_end', 'month'], true)) {
            $this->resetPage();
        }
    }

    public function getListProperty(): LengthAwarePaginator
    {
        $userIds = $this->authorizedUserIds();

        if ($userIds->isEmpty()) {
            return ProtestJob::query()->whereRaw('1 = 0')->paginate($this->perPage);
        }

        $query = ProtestJob::query()
            ->with([
                'owner:id,name',
                'creator:id,name',
                'medProtest' => function ($q) {
                    $q->with([
                        'protest.notes',
                        'notes',
                    ]);
                },
            ])
            ->whereIn('owner_id', $userIds)
            ->whereNotNull('closed_at');

        $query->when($this->search, function ($q) {
            $term = $this->formatWithWildcard($this->search);

            $q->where(function ($sub) use ($term) {
                $sub->whereHas('medProtest.protest', function ($inner) use ($term) {
                    $inner->where('nota', $term->type, $term->search)
                        ->orWhere('txtGrpCodificacao', $term->type, $term->search);
                })
                ->orWhereHas('medProtest.protest.notes', function ($inner) use ($term) {
                    $inner->where('note', $term->type, $term->search)
                        ->orWhere('material', $term->type, $term->search);
                })
                ->orWhereHas('medProtest.notes', function ($inner) use ($term) {
                    $inner->where('note', $term->type, $term->search)
                        ->orWhere('material', $term->type, $term->search);
                });
            });
        });

        $query->when($this->dt_start, function ($q) {
            $q->whereDate('closed_at', '>=', $this->dt_start);
        });

        $query->when($this->dt_end, function ($q) {
            $q->whereDate('closed_at', '<=', $this->dt_end);
        });

        $query->when($this->month, function ($q) {
            try {
                $target = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
            } catch (\Throwable $th) {
                return;
            }

            $q->whereYear('closed_at', $target->year)
                ->whereMonth('closed_at', $target->month);
        });

        return $query->orderByDesc('closed_at')->paginate($this->perPage);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dt_start', 'dt_end', 'month']);
        $this->resetPage();
    }

    protected function authorizedUserIds(): Collection
    {
        if ($this->authorizedUserIds instanceof Collection) {
            return $this->authorizedUserIds;
        }

        /** @var User|null $viewer */
        $viewer = auth()->user();

        if (!$viewer) {
            return $this->authorizedUserIds = collect();
        }

        $ids = $viewer->descendantsQuery(
            includeSelf: true,
            includeDelegations: false,
            includeDelegatesTreesForPrincipal: false
        )
        ->pluck('users.id')
        ->push($viewer->id)
        ->unique()
        ->values();

        return $this->authorizedUserIds = $ids;
    }

    public function deadlineFor(ProtestJob $job): ?Carbon
    {
        $medProtest = $job->medProtest;
        $protest = $medProtest?->protest;

        if (!$medProtest || !$protest) {
            return null;
        }

        if (($protest->tipoNota ?? null) === 'NA') {
            return $protest->dtConclusaoDesej;
        }

        return $medProtest->dtFimMedidaDesej;
    }

    public function finishedWithinDeadline(ProtestJob $job): ?bool
    {
        $deadline = $this->deadlineFor($job);
        $finishedAt = $job->closed_at ?? $job->finished_at;

        if (!$deadline || !$finishedAt) {
            return null;
        }

        return $finishedAt->lessThanOrEqualTo($deadline);
    }

    public function render()
    {
        return view('livewire.protests.partner.history', [
            'list' => $this->list,
        ]);
    }
}