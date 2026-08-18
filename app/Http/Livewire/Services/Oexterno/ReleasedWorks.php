<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Enum\{CancellationRequestScope, CancellationRequestStatus};
use App\Helpers\TextFormatter;
use App\Jobs\Services\ExportExternalOrganReleasedWorksJob;
use App\Models\{CancellationCategory, ExternalOrganRelease, Service};
use App\Services\Payment\CancellationRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};
use RuntimeException;

class ReleasedWorks extends Component
{
    use TextFormatter;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $search = '';

    public $advanceSearch = '';

    public $multisearch = [];

    public $tab = 'new';

    public $perPage = 100;

    public string $releaseStatusDateFrom = '';

    public string $releaseStatusDateTo = '';

    public string $costType = '';

    public array $selectedReleaseIds = [];

    private bool $closedReleasedStatuses = false;

    protected $listeners = [
        'rejectExternalOrganRequirement' => 'rejectExternalOrganRequirement',
    ];

    protected $queryString = [
        'tab'                   => ['except' => 'new'],
        'search'                => ['except' => '', 'as' => 'buscar'],
        'releaseStatusDateFrom' => ['except' => '', 'as' => 'liberacao_de'],
        'releaseStatusDateTo'   => ['except' => '', 'as' => 'liberacao_ate'],
        'costType'              => ['except' => '', 'as' => 'custo'],
        'page'                  => ['except' => 1, 'as' => 'p'],
    ];

    public function mount($service): void
    {
        $this->service = Service::query()
            ->select(['id', 'uuid', 'service'])
            ->where('uuid', $service)
            ->with('Status')
            ->first();
    }

    public function updatedTab(): void
    {
        $this->selectedReleaseIds = [];
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->multisearch = [];
        $this->resetPage();
    }

    public function updatedReleaseStatusDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedReleaseStatusDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedCostType(): void
    {
        $this->resetPage();
    }

    public function buscarMulti(): void
    {
        if ($this->advanceSearch) {
            $this->multisearch   = $this->formatTextToArray($this->advanceSearch);
            $this->search        = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function cleanAll(): void
    {
        $this->search                = '';
        $this->advanceSearch         = '';
        $this->multisearch           = [];
        $this->releaseStatusDateFrom = '';
        $this->releaseStatusDateTo   = '';
        $this->costType              = '';
        $this->selectedReleaseIds    = [];
        $this->resetPage();
    }

    public function sendSelectedToCancellation(): void
    {
        if ($this->tab !== 'cancel_90') {
            return;
        }

        $ids = collect($this->selectedReleaseIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione ao menos uma nota.',
                'timer'    => 2500,
            ]);

            return;
        }

        $category  = $this->externalOrganCancellationCategory();
        $service   = app(CancellationRequestService::class);
        $processed = 0;
        $errors    = 0;

        $releases = $this->baseQuery()
            ->whereIn('external_organ_releases.id', $ids->all())
            ->with(['note.Orders', 'note.WorkForm'])
            ->get();

        foreach ($releases as $release) {
            $note = $release->note;

            if (!$note) {
                $errors++;

                continue;
            }

            try {
                $service->createRequest(
                    $note,
                    CancellationRequestScope::NOTE_FULL->value,
                    $category,
                    [],
                    [],
                    Auth::user(),
                    'Cancelamento total solicitado pela fila Obras Liberadas OE: nota há mais de 90 dias no status 70.'
                );

                $processed++;
            } catch (RuntimeException $e) {
                $errors++;
            }
        }

        $this->selectedReleaseIds = [];
        $this->resetPage();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => $processed ? 'success' : 'warning',
            'title'    => "{$processed} solicitação(ões) enviada(s) para cancelamento total.",
            'html'     => $errors ? "{$errors} item(ns) não foram enviados por já possuírem solicitação aberta ou estarem indisponíveis." : null,
            'timer'    => 4200,
        ]);
    }

    public function confirmRejectExternalOrgan(int $releaseId): void
    {
        $release = $this->baseQuery()
            ->whereKey($releaseId)
            ->with('note:id,note,nstats,dt_status,type_note')
            ->first();

        if (!$release || $release->released_at) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Obra indisponível para recusa.',
                'timer'    => 2800,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('confirm-external-organ-rejection', [
            'releaseId' => $release->id,
            'note'      => $release->note?->note ?? '---',
        ]);
    }

    public function rejectExternalOrganRequirement(int $releaseId): void
    {
        $release = $this->baseQuery()
            ->whereKey($releaseId)
            ->with('note:id,nstats,dt_status,type_note')
            ->first();

        if (!$release || $release->released_at) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Obra indisponível para recusa.',
                'timer'    => 2800,
            ]);

            return;
        }

        $note = $release->note;
        $now  = now();

        DB::transaction(function () use ($release, $note, $now) {
            $release->update([
                'released_at'         => $now,
                'release_dt_status'   => $note?->dt_status,
                'release_detected_at' => $now,
                'release_nstats'      => $note?->nstats,
                'released_by'         => Auth::id(),
            ]);

            $note?->update([
                'doe' => false,
            ]);

            $this->removePendingExternalEntityLinks($note);
        });

        $this->selectedReleaseIds = array_values(array_diff(
            $this->selectedReleaseIds,
            [$releaseId, (string) $releaseId]
        ));
        $this->tab = 'released';
        $this->resetPage();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Pendência de OE recusada.',
            'html'     => 'A obra foi removida do bloqueio de Órgão Externo e poderá seguir para aprovação de projeto.',
            'timer'    => 3200,
        ]);
    }

    private function removePendingExternalEntityLinks($note): void
    {
        if (!$note) {
            return;
        }

        $note->Externals()
            ->whereNotNull('entity_id')
            ->where('completed', false)
            ->whereDoesntHave('Protocols')
            ->whereDoesntHave('Comments')
            ->whereDoesntHave('PoolPayments')
            ->whereDoesntHave('Reclaims')
            ->whereDoesntHave('Files')
            ->delete();
    }

    public function exportToExcel(): void
    {
        $this->closeReleasedStatuses();

        $ids = $this->baseQuery()
            ->whereNull('released_at')
            ->whereHas('note', function ($q) {
                $q->where('type_note', 2)
                    ->whereIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!count($ids)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma obra disponível para exportar.',
                'timer'    => 2800,
            ]);

            return;
        }

        ExportExternalOrganReleasedWorksJob::dispatch($ids, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => 'Você receberá uma notificação quando o arquivo estiver pronto.',
            'timer'    => 3000,
        ]);
    }

    public function getReleasesProperty()
    {
        $query = $this->baseQuery();

        if ($this->tab === 'released') {
            return $query
                ->orderByDesc('released_at')
                ->orderByDesc('id')
                ->paginate($this->perPage);
        }

        return $query
            ->orderByRaw('exported_at IS NOT NULL')
            ->orderBy('created_at')
            ->paginate($this->perPage);
    }

    private function baseQuery()
    {
        $query = ExternalOrganRelease::query()
            ->with([
                'note:id,note,client,lexp,rubrica,nstats,dt_status,dt_created,numPedido,type_note,centerjob',
                'note.Externals:id,note_id,entity_id,entidade,completed',
                'note.Externals.Entity:id,name,nick',
                'production:id,note_id,user_id,company_id,service_id,completed_at,status',
                'production.User:id,name',
                'production.Company:id,name',
                'production.Service:uuid,service',
                'production.LatestProjectReviewCycle' => function ($q) {
                    $q->select([
                        'project_review_cycles.id',
                        'project_review_cycles.production_id',
                        'project_review_cycles.round_number',
                    ])
                        ->with('Orders:id,cycle_id,sort_order,order_number,total_cost,company_cost,client_cost');
                },
                'exportedBy:id,name',
                'releasedBy:id,name',
            ]);

        if ($this->tab === 'new') {
            $query->eligibleForExternalOrganList()
                ->whereNull('released_at')
                ->whereNull('exported_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2)
                        ->whereIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
                });
        } elseif ($this->tab === 'exported') {
            $query->eligibleForExternalOrganList()
                ->whereNull('released_at')
                ->whereNotNull('exported_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2)
                        ->whereIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
                });
        } elseif ($this->tab === 'released') {
            $query->whereNotNull('released_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2);
                });
        } elseif ($this->tab === 'cancel_90') {
            $query->eligibleForExternalOrganList()
                ->whereNull('released_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2)
                        ->where('nstats', 70)
                        ->whereDate('dt_status', '<=', now()->subDays(90)->toDateString())
                        ->whereDoesntHave('CancellationRequests', function ($requestQuery) {
                            $requestQuery
                                ->where('scope', CancellationRequestScope::NOTE_FULL->value)
                                ->whereIn('status', [
                                    CancellationRequestStatus::DRAFT->value,
                                    CancellationRequestStatus::SUBMITTED->value,
                                    CancellationRequestStatus::ASSIGNED->value,
                                    CancellationRequestStatus::PAUSED->value,
                                ]);
                        });
                });
        } elseif ($this->tab === 'pending') {
            $query->eligibleForExternalOrganList()
                ->whereNull('released_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2)
                        ->whereNotIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
                });
        } else {
            $query->eligibleForExternalOrganList()
                ->whereNull('released_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2)
                        ->whereIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
                });
        }

        if (filled(trim((string) $this->search))) {
            $term = '%' . trim((string) $this->search) . '%';
            $query->whereHas('note', function ($q) use ($term) {
                $q->where('note', 'like', $term)
                    ->orWhere('numPedido', 'like', $term)
                    ->orWhere('client', 'like', $term)
                    ->orWhere('lexp', 'like', $term);
            });
        }

        if (!empty($this->multisearch)) {
            $query->whereHas('note', function ($q) {
                $q->whereIn('note', $this->multisearch)
                    ->orWhereIn('numPedido', $this->multisearch);
            });
        }

        if ($this->tab !== 'cancel_90' && filled($this->releaseStatusDateFrom)) {
            $query->whereDate('release_dt_status', '>=', $this->releaseStatusDateFrom);
        }

        if ($this->tab !== 'cancel_90' && filled($this->releaseStatusDateTo)) {
            $query->whereDate('release_dt_status', '<=', $this->releaseStatusDateTo);
        }

        if (in_array($this->costType, ['client', 'company'], true)) {
            $this->applyCostTypeFilter($query);
        }

        return $query;
    }

    private function applyCostTypeFilter(Builder $query): void
    {
        $query->whereHas('production.ProjectReviewCycles', function ($cycleQuery) {
            $cycleQuery->whereRaw(
                'project_review_cycles.round_number = (
                    select max(prc_latest.round_number)
                    from project_review_cycles prc_latest
                    where prc_latest.production_id = project_review_cycles.production_id
                )'
            );

            if ($this->costType === 'client') {
                $cycleQuery->whereHas('Orders', function ($orderQuery) {
                    $orderQuery->where('client_cost', '>', 0);
                });

                return;
            }

            $cycleQuery
                ->whereHas('Orders', function ($orderQuery) {
                    $orderQuery->where('company_cost', '>', 0);
                })
                ->whereDoesntHave('Orders', function ($orderQuery) {
                    $orderQuery->where('client_cost', '>', 0);
                });
        });
    }

    private function closeReleasedStatuses(): void
    {
        if ($this->closedReleasedStatuses) {
            return;
        }

        $this->closedReleasedStatuses = true;

        ExternalOrganRelease::query()
            ->pending()
            ->whereHas('note', function ($q) {
                $q->whereIn('nstats', ExternalOrganRelease::RELEASE_STATUSES);
            })
            ->with('note:id,nstats,dt_status')
            ->limit(1000)
            ->get()
            ->each(function (ExternalOrganRelease $release) {
                $note = $release->note;

                if (!$note) {
                    return;
                }

                $release->update([
                    'released_at'         => $note->dt_status,
                    'release_dt_status'   => $note->dt_status,
                    'release_detected_at' => now(),
                    'release_nstats'      => $note->nstats,
                    'released_by'         => null,
                ]);
            });
    }

    private function externalOrganCancellationCategory(): CancellationCategory
    {
        return CancellationCategory::query()->firstOrCreate(
            ['name' => 'Órgão Externo - Status 70 acima de 90 dias'],
            [
                'description'        => 'Cancelamento total solicitado pela lista de Obras Liberadas OE.',
                'active'             => true,
                'require_evidence'   => false,
                'min_evidence_files' => 0,
                'display_order'      => 90,
            ]
        );
    }

    public function projectReviewCostSummary(ExternalOrganRelease $release): array
    {
        $cycle = $release->production?->LatestProjectReviewCycle;

        if (!$cycle) {
            return [
                'has_cycle'       => false,
                'has_client_cost' => false,
                'round_number'    => null,
                'total_cost'      => 0.0,
                'company_cost'    => 0.0,
                'client_cost'     => 0.0,
                'orders'          => '',
            ];
        }

        $orders     = collect($cycle->Orders ?? []);
        $clientCost = (float) $orders->sum(fn ($order) => (float) ($order->client_cost ?? 0));

        return [
            'has_cycle'       => true,
            'has_client_cost' => $clientCost > 0,
            'round_number'    => (int) $cycle->round_number,
            'total_cost'      => (float) $orders->sum(fn ($order) => (float) ($order->total_cost ?? 0)),
            'company_cost'    => (float) $orders->sum(fn ($order) => (float) ($order->company_cost ?? 0)),
            'client_cost'     => $clientCost,
            'orders'          => $orders
                ->pluck('order_number')
                ->map(fn ($orderNumber) => trim((string) $orderNumber))
                ->filter()
                ->implode(', '),
        ];
    }

    public function formatMoneyBr(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public function externalEntitySummary(ExternalOrganRelease $release): string
    {
        return collect($release->note?->Externals ?? [])
            ->map(function ($external) {
                $entity = $external->Entity;

                return trim((string) (
                    $entity
                        ? (($entity->nick ? $entity->nick . ' - ' : '') . $entity->name)
                        : $external->entidade
                ));
            })
            ->filter()
            ->unique()
            ->implode('; ');
    }

    public function render()
    {
        return view('livewire.services.oexterno.released-works', [
            'releases' => $this->releases,
        ]);
    }
}
