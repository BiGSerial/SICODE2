<?php

namespace App\Http\Livewire\Engineers;

use App\Custom\Notestatus;
use App\Helpers\TextFormatter;
use App\Jobs\Engineers\ExportWaitingFiveNotesJob;
use App\Models\FiveNote;
use App\Models\Service;
use App\Traits\AppliesQueryFilters;
use App\Traits\WildcardFormmater;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\Exportable;

class WaitingFiveNotes extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;
    use WildcardFormmater;
    use AppliesQueryFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 100;
    public $search;
    public $advanceSearch = '';
    public $multiD5 = [];
    public $multiNote = [];
    public $type = "";
    public $statusFilter = '';

    public $showDetails = false;


    public $selectall = false;
    public $selected = [];


    // Filters
    public $filtersState = [];
    protected ?string $fiscalizationServiceId = null;
    protected ?string $paymentServiceId = null;



    protected $queryString = [
        'type' => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'statusFilter' => ['except' => '', 'as' => 'status'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'filters.updated' => 'onFiltersUpdated',
        'filters.applied' => 'onFiltersUpdated',
         // MD5 of SICODE
    ];

    public function updatedSearch()
    {
        $this->resetPage();
        if (!$this->search) {
            $this->multiD5 = [];
            $this->multiNote = [];
            $this->advanceSearch = '';
        }
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }


    public function buscarMulti()
    {
        $this->search = "";
        $this->resetPage();
        $values = $this->formatTextToArray((string) $this->advanceSearch);
        $this->multiD5 = $values;
        $this->multiNote = $values;
    }

    public function onFiltersUpdated($payload = [])
    {

        $this->filtersState = $payload ?: [];
        $this->resetPage();



    }




    public function setSelectAll()
    {
        if (!$this->lists) {
            return;
        }

        $visibleItems = $this->lists->items();

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);



        if ($this->selectall) {

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

    /**
     * Marca/desmarca o checkbox "selecionar todos" de acordo com os itens visíveis
     */
    public function checkAllSelect($items)
    {

        $eligiblePage = [];

        foreach ($items as $note) {
            $eligiblePage[] = (int) $note->id;
        }

        // selectall fica true quando TODOS os elegíveis da página estão selecionados
        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectall = false;
                return false;
            }
        }

        $this->selectall = true;
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
            $this->selectall = false;
            return;
        }

        $selectedSet = array_fill_keys(array_map('intval', $this->selected), true);
        foreach ($eligiblePage as $id) {
            if (!isset($selectedSet[$id])) {
                $this->selectall = false;
                return;
            }
        }

        $this->selectall = true;
    }

    private function returnFilterArray($key)
    {
        if (is_array($this->filtersState[$key] ?? null)) {
            return $this->filtersState[$key] ?? [];
        } else {
            return $this->filtersState[$key] ?? null;
            ;
        }
    }

    private function applyDesiredBetweenFilter(Builder $base): void
    {
        $dateRange = $this->returnFilterArray('desired_between');

        if (!is_array($dateRange) || !isset($dateRange['start'], $dateRange['end'])) {
            return;
        }

        $periodColumn = $this->filterScalar('period_column', 'dispatch');

        if ($periodColumn === 'completed') {
            $base->whereBetween('completed_at', [$dateRange['start'], $dateRange['end']]);
            return;
        }

        if ($periodColumn === 'both') {
            $base->where(function ($query) use ($dateRange) {
                $query->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhereBetween('completed_at', [$dateRange['start'], $dateRange['end']]);
            });
            return;
        }

        $base->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
    }

    private function applyPassiveModeFilter(Builder $base): void
    {
        $passiveMode = $this->filterScalar('passive_mode', 'both');

        if ($passiveMode === 'passive') {
            $base->where('isPassive', true);
            return;
        }

        if ($passiveMode === 'meta') {
            $base->where(function ($query) {
                $query->whereNull('isPassive')
                    ->orWhere('isPassive', false);
            });
        }
    }

    private function filterScalar(string $key, string $default = ''): string
    {
        $value = $this->filtersState[$key] ?? $default;

        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    private function applyStatusFilter(Builder $base): void
    {
        switch ($this->statusFilter) {
            case 'aguardando_fornecedor':
                $base->where('visible_partner', true)
                    ->where('is_completed', false)
                    ->where('is_archived', false);
                break;
            case 'aguardando_fiscalizacao':
                $base->where(function ($query) {
                    $query->where('is_completed', true)
                        ->where('is_supervisioned', false)
                        ->where('is_archived', false);
                });
                break;
            case 'aguardando_pagamento':
                $base->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('is_supervisioned', true)
                            ->where('is_archived', false);
                    })->orWhere(function ($q) {
                        $q->where('is_archived', false)
                            ->where('visible_partner', false)
                            ->where(function ($d5) {
                                $d5->whereNull('note_d5')
                                    ->orWhere('note_d5', '');
                            });
                    });
                });
                break;
            case 'finalizado':
                $base->where('is_archived', true);
                break;
        }
    }

    /**
     * QUERY BASE (reutilizável)
     */
    private function baseQuery(): Builder
    {
        $base = FiveNote::query()
            ->where(function ($query) {
                $query->where('visible_partner', true)
                    ->orWhere(function ($inner) {
                        $inner->where(function ($noteD5) {
                            $noteD5->whereNull('note_d5')
                                ->orWhere('note_d5', '');
                        })->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('timeline_events as te')
                                ->whereColumn('te.five_note_id', 'five_notes.id')
                                ->where('te.event_type', 'd5_created_from_supervision');
                        });
                    });
            });

        $this->applyStatusFilter($base);

        if ($this->search) {

            $search = $this->formatWithWildcard($this->search);

            $base->where(function ($query) use ($search) {
                $query->whereHas('note', function ($q) use ($search) {
                    $q->where('note', $search->type, $search->search);
                })
                    ->orWhere('note_d5', $search->type, $search->search)
                    ->orWhere('reason', $search->type, $search->search)
                    ->orWhere('codify', $search->type, $search->search)
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', $search->type, $search->search);
                    });
            });
        }

        if ($this->returnFilterArray('company')) {
            $base->whereIn('company_id', $this->returnFilterArray('company'));
        }

        if ($this->returnFilterArray('type')) {
            $base->whereRelation('note', 'type_note', $this->returnFilterArray('type'));

        }

        $this->applyPassiveModeFilter($base);

        if ($this->returnFilterArray('city')) {
            $base->whereRelation('note', function ($q) {
                $q->whereIn('nexp', $this->returnFilterArray('city'));
            });
        }

        if ($this->returnFilterArray('rubrica')) {
            $base->whereRelation('note', function ($q) {
                $q->whereIn('rubrica', $this->returnFilterArray('rubrica'));
            });
        }

        $this->applyDesiredBetweenFilter($base);


        $hasNote = count($this->multiNote) > 0;
        $hasD5 = count($this->multiD5) > 0;

        if ($hasNote || $hasD5) {
            $base->where(function ($query) use ($hasNote, $hasD5) {
                if ($hasNote) {
                    $query->whereHas('note', function ($q) {
                        $q->whereIn('note', $this->multiNote);
                    });
                }

                if ($hasD5) {
                    if ($hasNote) {
                        $query->orWhereIn('note_d5', $this->multiD5);
                    } else {
                        $query->whereIn('note_d5', $this->multiD5);
                    }
                }
            });
        }

        return $base
            ->orderByRaw('CASE WHEN completed_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('completed_at', 'asc')
            ->orderBy('dispatch_at', 'asc');
    }

    public function getListsProperty()
    {
        $page = $this->baseQuery()->paginate($this->perPage);

        $page->load([
            'note:id,note,rubrica',
            'note.Productions:id,note_id,service_id,user_id,company_id,created_at,att_at,completed,completed_at,status',
            'note.Productions.User:id,name',
            'note.Productions.Company:id,name',
            'company:id,name',
            'productions:id,service_id,user_id,company_id,created_at,att_at,completed,completed_at,status',
            'productions.User:id,name',
            'productions.Company:id,name',
            'evidenceFiles',
        ]);

        foreach ($page->items() as $fiveNote) {
            $meta = $this->buildTrackingMeta($fiveNote);
            $fiveNote->setAttribute('tracking_meta', $meta);
        }

        return $page;
    }

    public function exportToExcel(): void
    {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        ExportWaitingFiveNotesJob::dispatch($this->exportPayload(), (string) $userId);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTACAO INICIADA',
            'text'     => 'Voce recebera uma notificacao quando o arquivo estiver pronto.',
            'timer'    => 5000,
        ]);
    }

    protected function exportPayload(): array
    {
        return [
            'search'        => $this->search,
            'multiD5'       => $this->multiD5,
            'multiNote'     => $this->multiNote,
            'statusFilter'  => $this->statusFilter,
            'filtersState'  => $this->filtersState,
        ];
    }

    protected function buildTrackingMeta(FiveNote $fiveNote): array
    {
        $activity = $this->resolveActivity($fiveNote);
        $assignee = $this->resolveAssignee($fiveNote, $activity['key']);

        $timeline = [
            [
                'label' => 'Despacho',
                'at' => $fiveNote->dispatch_at,
                'wait_days' => $this->waitDays($fiveNote->dispatch_at, $fiveNote->completed_at),
            ],
            [
                'label' => 'Retorno empreiteira',
                'at' => $fiveNote->completed_at,
                'wait_days' => $this->waitDays($fiveNote->completed_at, $fiveNote->supervisioned_at),
            ],
            [
                'label' => 'Fiscalizacao',
                'at' => $fiveNote->supervisioned_at,
                'wait_days' => $this->waitDays($fiveNote->supervisioned_at, $fiveNote->payed_at),
            ],
            [
                'label' => 'Pagamento',
                'at' => $fiveNote->payed_at,
                'wait_days' => $this->waitDays(
                    $fiveNote->payed_at,
                    $fiveNote->is_archived ? ($fiveNote->updated_at ?? now()) : null
                ),
            ],
            [
                'label' => 'Finalizado',
                'at' => $fiveNote->is_archived ? ($fiveNote->updated_at ?? now()) : null,
                'wait_days' => null,
            ],
        ];

        return [
            'activity' => $activity,
            'assignee' => $assignee,
            'timeline' => $timeline,
            'partner_return_at' => $fiveNote->completed_at,
        ];
    }

    protected function resolveActivity(FiveNote $fiveNote): array
    {
        if ($fiveNote->is_archived) {
            return ['key' => 'finalizado', 'label' => 'Finalizado', 'color' => 'text-bg-success'];
        }

        if ((is_null($fiveNote->note_d5) || trim((string) $fiveNote->note_d5) === '') && !$fiveNote->visible_partner) {
            return ['key' => 'aguardando_geracao_d5', 'label' => 'Aguardando Geracao de D5', 'color' => 'text-bg-secondary'];
        }

        if ($fiveNote->is_supervisioned) {
            return ['key' => 'aguardando_pagamento', 'label' => 'Aguardando Pagamento', 'color' => 'text-bg-primary'];
        }

        if ($fiveNote->is_completed) {
            return ['key' => 'aguardando_fiscalizacao', 'label' => 'Aguardando Fiscalizacao', 'color' => 'text-bg-warning'];
        }

        return ['key' => 'aguardando_fornecedor', 'label' => 'Aguardando Fornecedor', 'color' => 'text-bg-danger'];
    }

    protected function resolveAssignee(FiveNote $fiveNote, string $activityKey): array
    {
        if (!in_array($activityKey, ['aguardando_fiscalizacao', 'aguardando_pagamento', 'aguardando_geracao_d5'], true)) {
            $statusMeta = $this->statusMetaFromProduction(null);
            return [
                'name' => null,
                'company' => null,
                'has_assignee' => false,
                'assignment_status' => $statusMeta['label'],
                'assignment_badge' => $statusMeta['badge'],
            ];
        }

        $targetServiceId = $activityKey === 'aguardando_fiscalizacao'
            ? $this->fiscalizationServiceId()
            : $this->paymentServiceId();

        if (!$targetServiceId) {
            $statusMeta = $this->statusMetaFromProduction(null);
            return [
                'name' => null,
                'company' => null,
                'has_assignee' => false,
                'assignment_status' => $statusMeta['label'],
                'assignment_badge' => $statusMeta['badge'],
            ];
        }

        $productions = $fiveNote->note?->Productions ?? collect();
        $partnerReturnAt = $fiveNote->completed_at;
        $strictPartnerWindow = $activityKey === 'aguardando_fiscalizacao' && (bool) $partnerReturnAt;

        $forService = $productions->where('service_id', $targetServiceId);

        $openForService = $forService->where('completed', false);

        if ($partnerReturnAt) {
            $openForService = $openForService->filter(function ($production) use ($partnerReturnAt) {
                $mark = $production->att_at ?: $production->created_at;
                return $mark && $mark->greaterThanOrEqualTo($partnerReturnAt);
            });
        }

        $candidate = $openForService
            ->whereNotNull('user_id')
            ->sortByDesc(function ($production) {
                return $production->att_at ?: $production->created_at;
            })
            ->first();

        if (!$candidate) {
            $candidate = $openForService
                ->sortByDesc(function ($production) {
                    return $production->att_at ?: $production->created_at;
                })
                ->first();
        }

        if ($strictPartnerWindow && !$candidate) {
            $statusMeta = $this->statusMetaFromProduction(null);
            return [
                'name' => null,
                'company' => null,
                'has_assignee' => false,
                'assignment_status' => $statusMeta['label'],
                'assignment_badge' => $statusMeta['badge'],
            ];
        }

        if (!$candidate) {
            $candidate = $forService
                ->whereNotNull('user_id')
                ->sortByDesc(function ($production) {
                    return $production->att_at ?: $production->created_at;
                })
                ->first();
        }

        if (!$candidate) {
            $candidate = $forService
                ->where('completed', false)
                ->sortByDesc(function ($production) {
                    return $production->att_at ?: $production->created_at;
                })
                ->first();
        }

        if (!$candidate) {
            $statusMeta = $this->statusMetaFromProduction(null);
            return [
                'name' => null,
                'company' => null,
                'has_assignee' => false,
                'assignment_status' => $statusMeta['label'],
                'assignment_badge' => $statusMeta['badge'],
            ];
        }

        $statusMeta = $this->statusMetaFromProduction($candidate->status);

        return [
            'name' => $candidate->User?->name,
            'company' => $candidate->Company?->name,
            'has_assignee' => (bool) $candidate->user_id,
            'assignment_status' => $statusMeta['label'],
            'assignment_badge' => $statusMeta['badge'],
        ];
    }

    protected function statusMetaFromProduction($status): array
    {
        $fallback = [
            'label' => 'Nao Atribuido',
            'badge' => 'text-bg-secondary',
        ];

        try {
            $statusValue = is_null($status) ? 1 : (int) $status;
            $meta = Notestatus::status($statusValue);

            return [
                'label' => $meta->status ?? $fallback['label'],
                'badge' => $meta->colorbg ?? $fallback['badge'],
            ];
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    protected function waitDays($from, $to): ?int
    {
        if (!$from) {
            return null;
        }

        $start = $from instanceof Carbon ? $from : Carbon::parse($from);
        $end = $to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : now();

        return $start->diffInDays($end);
    }

    protected function fiscalizationServiceId(): ?string
    {
        if ($this->fiscalizationServiceId !== null) {
            return $this->fiscalizationServiceId;
        }

        $this->fiscalizationServiceId = Service::whereIn('service', ['Fiscalizacao', 'Fiscalização'])->value('uuid');

        return $this->fiscalizationServiceId;
    }

    protected function paymentServiceId(): ?string
    {
        if ($this->paymentServiceId !== null) {
            return $this->paymentServiceId;
        }

        $this->paymentServiceId = Service::where('service', 'Pagamento')->value('uuid');

        return $this->paymentServiceId;
    }


    public function render()
    {
        return view('livewire.engineers.waiting-five-notes', [
            'lists' => $this->lists,
        ]);
    }


}
