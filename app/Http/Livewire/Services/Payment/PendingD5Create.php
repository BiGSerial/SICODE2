<?php

namespace App\Http\Livewire\Services\Payment;

use App\Helpers\TextFormatter;
use App\Jobs\ExportPendingD5CreateJob;
use App\Models\FiveNote;
use App\Traits\AppliesQueryFilters;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PendingD5Create extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormmater;
    use AppliesQueryFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;
    public $search;
    public $advanceSearch;
    public $multiSearch = [];

    public $bulkD5Input = '';
    public $bulkD5Processed = false;
    public $bulkD5Ready = [];
    public $bulkD5Missing = [];
    public $bulkD5Divergent = [];
    public $bulkD5Ignored = [];
    public $bulkD5Invalid = [];

    public $filtersState = [];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'filters.updated' => 'onFiltersUpdated',
        'filters.applied' => 'onFiltersUpdated',
        'bulkD5Reset' => 'resetBulkD5',
    ];

    public function mount($service = null): void
    {
        $this->service = $service;
    }

    public function updatedSearch()
    {
        $this->resetPage();

        if (!$this->search) {
            $this->multiSearch = [];
            $this->advanceSearch = '';
        }
    }

    public function buscarMulti(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->multiSearch = $this->formatTextToArray($this->advanceSearch ?? '');
    }

    public function onFiltersUpdated($payload = []): void
    {
        $this->filtersState = $payload ?: [];
        $this->resetPage();
    }

    public function resetBulkD5(): void
    {
        $this->bulkD5Input = '';
        $this->bulkD5Processed = false;
        $this->resetBulkD5Results();
    }

    public function processBulkD5(): void
    {
        $this->resetBulkD5Results();

        $numbers = $this->extractBulkNumbers($this->bulkD5Input ?? '');

        if (count($numbers) < 2) {
            $this->bulkD5Invalid[] = 'Informe ao menos 2 numeros para formar pares (Nota e D5).';
            $this->bulkD5Processed = true;
            return;
        }

        if (count($numbers) % 2 !== 0) {
            $this->bulkD5Invalid[] = 'Quantidade impar de numeros. O ultimo numero foi ignorado.';
        }

        $pairs = [];
        for ($i = 0; $i + 1 < count($numbers); $i += 2) {
            $pairs[] = [
                'note' => (string) $numbers[$i],
                'd5' => (string) $numbers[$i + 1],
            ];
        }

        $uniqueNotes = array_values(array_unique(array_column($pairs, 'note')));
        $fiveNotes = FiveNote::query()
            ->whereHas('note', function ($q) use ($uniqueNotes) {
                $q->whereIn('note', $uniqueNotes);
            })
            ->with('note')
            ->get()
            ->keyBy(function ($five) {
                return (string) ($five->note?->note ?? '');
            });

        $seen = [];

        foreach ($pairs as $pair) {
            $noteNumber = $pair['note'];
            $d5Number = $pair['d5'];

            if (isset($seen[$noteNumber])) {
                $this->bulkD5Ignored[] = [
                    'note' => $noteNumber,
                    'd5' => $d5Number,
                    'reason' => 'Duplicada na lista',
                ];
                continue;
            }

            $seen[$noteNumber] = true;

            $fiveNote = $fiveNotes->get($noteNumber);

            if (!$fiveNote) {
                $this->bulkD5Missing[] = $noteNumber;
                continue;
            }

            $currentD5 = trim((string) $fiveNote->note_d5);

            if ($currentD5 === '') {
                $this->bulkD5Ready[] = [
                    'five_note_id' => $fiveNote->id,
                    'note' => $noteNumber,
                    'd5' => $d5Number,
                ];
                continue;
            }

            if ($currentD5 === $d5Number) {
                $this->bulkD5Ignored[] = [
                    'note' => $noteNumber,
                    'd5' => $d5Number,
                    'reason' => 'Ja possui o mesmo D5',
                ];
                continue;
            }

            $this->bulkD5Divergent[] = [
                'five_note_id' => $fiveNote->id,
                'note' => $noteNumber,
                'current_d5' => $currentD5,
                'new_d5' => $d5Number,
                'locked' => (bool) $fiveNote->is_completed,
            ];
        }

        $this->bulkD5Processed = true;
    }

    public function removeBulkD5Divergent(string $noteNumber): void
    {
        $this->bulkD5Divergent = array_values(array_filter(
            $this->bulkD5Divergent,
            fn ($item) => (string) ($item['note'] ?? '') !== (string) $noteNumber
        ));
    }

    public function confirmBulkD5(): void
    {
        $updates = [];

        foreach ($this->bulkD5Ready as $item) {
            $updates[] = [
                'id' => $item['five_note_id'],
                'note_d5' => $item['d5'],
            ];
        }

        foreach ($this->bulkD5Divergent as $item) {
            if (!empty($item['locked'])) {
                continue;
            }

            $updates[] = [
                'id' => $item['five_note_id'],
                'note_d5' => $item['new_d5'],
            ];
        }

        if (empty($updates)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'info',
                'title' => 'Nenhuma alteracao aplicada',
                'text' => 'Nao ha registros validos para atualizar.',
                'timer' => 4000,
            ]);
            return;
        }

        DB::transaction(function () use ($updates) {
            $updates = collect($updates)
                ->unique('id')
                ->values()
                ->all();

            $ids = array_column($updates, 'id');
            $cases = collect($updates)->map(function ($item) {
                $id = (int) $item['id'];
                $d5 = addslashes((string) $item['note_d5']);
                return "WHEN {$id} THEN '{$d5}'";
            })->implode(' ');

            if (!$cases) {
                return;
            }

            DB::table('five_notes')
                ->whereIn('id', $ids)
                ->update([
                    'note_d5' => DB::raw("CASE id {$cases} END"),
                    'updated_at' => now(),
                ]);
        });

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'D5 atualizadas com sucesso',
            'text' => 'As alteracoes foram aplicadas em massa.',
            'timer' => 5000,
        ]);

        $this->resetPage();
        $this->emit('refresh_list');
        $this->resetBulkD5();
        $this->dispatchBrowserEvent('bulk-d5-close');
    }

    private function resetBulkD5Results(): void
    {
        $this->bulkD5Ready = [];
        $this->bulkD5Missing = [];
        $this->bulkD5Divergent = [];
        $this->bulkD5Ignored = [];
        $this->bulkD5Invalid = [];
    }

    private function extractBulkNumbers(string $input): array
    {
        preg_match_all('/\d+/', $input, $matches);

        return $matches[0] ?? [];
    }

    private function returnFilterArray($key)
    {
        if (is_array($this->filtersState[$key] ?? null)) {
            return $this->filtersState[$key] ?? [];
        }

        return $this->filtersState[$key] ?? null;
    }

    private function baseQuery(): Builder
    {
        $base = FiveNote::query()
            ->where(function ($q) {
                $q->whereNull('note_d5')
                    ->orWhere('note_d5', '');
            })
            ->where(function ($q) {
                $q->whereNull('is_payed')
                    ->orWhere('is_payed', false);
            })
            ->whereNull('payed_at')
            ->where(function ($q) {
                $q->whereNull('is_archived')
                    ->orWhere('is_archived', false);
            })
            ->where(function ($q) {
                $q->whereNull('isPassive')
                    ->orWhere('isPassive', false);
            })
            ->where(function ($q) {
                $q->whereNull('returned')
                    ->orWhere('returned', false);
            });

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);

            $base->where(function ($query) use ($search) {
                $query->whereHas('note', function ($q) use ($search) {
                    $q->where('note', $search->type, $search->search);
                })
                    ->orWhereHas('note.Orders', function ($q) use ($search) {
                        $q->where('ordem', $search->type, $search->search);
                    })
                    ->orWhere('loc_install', $search->type, $search->search)
                    ->orWhere('pep', $search->type, $search->search)
                    ->orWhere('codify', $search->type, $search->search)
                    ->orWhere('reason', $search->type, $search->search);
            });
        }

        if (count($this->multiSearch) > 0) {
            $multi = $this->multiSearch;

            $base->where(function ($query) use ($multi) {
                $query->whereHas('note', function ($q) use ($multi) {
                    $q->whereIn('note', $multi);
                })
                    ->orWhereHas('note.Orders', function ($q) use ($multi) {
                        $q->whereIn('ordem', $multi);
                    })
                    ->orWhereIn('loc_install', $multi)
                    ->orWhereIn('pep', $multi)
                    ->orWhereIn('codify', $multi)
                    ->orWhereIn('reason', $multi);
            });
        }

        if ($this->returnFilterArray('company')) {
            $base->whereIn('company_id', $this->returnFilterArray('company'));
        }

        if ($this->returnFilterArray('type')) {
            $base->whereRelation('note', 'type_note', $this->returnFilterArray('type'));
        }

        if ($this->returnFilterArray('city')) {
            $base->whereRelation('note', function ($q) {
                $q->whereIn('nexp', $this->returnFilterArray('city'));
            });
        }

        if ($this->returnFilterArray('desired_between')) {
            $dateRange = $this->returnFilterArray('desired_between');
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $base->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
            }
        }

        return $base->orderBy('dispatch_at')
            ->orderBy('id');
    }

    public function getListsProperty()
    {
        $page = $this->baseQuery()->paginate($this->perPage);

        $page->load(['note.Orders', 'note.WorkForm.Orders', 'company']);

        return $page;
    }

    public function exportExcel(): void
    {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        ExportPendingD5CreateJob::dispatch($this->exportPayload(), $userId);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTACAO INICIADA',
            'text'     => 'Voce recebera uma notificacao quando estiver pronta.',
            'timer'    => 5000,
        ]);
    }

    protected function exportPayload(): array
    {
        return [
            'search'         => $this->search,
            'multipleSearch' => $this->multiSearch,
            'filters'        => $this->filtersState,
        ];
    }

    public function render()
    {
        return view('livewire.services.payment.pending-d5-create', [
            'lists' => $this->lists,
        ]);
    }
}
