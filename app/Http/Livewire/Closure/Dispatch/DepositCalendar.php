<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Models\Closure\{ClosureDepositCalendarEntry, ClosurePartnerSignal};
use App\Services\Closure\{ClosureDepositCalendarImporter, ClosureOrderWorkflowException, ClosurePartnerSignalService};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithFileUploads, WithPagination};

/**
 * Calendário de bloqueio de depósitos (inventário da gestão de ativos). O despacho carrega a planilha, o sistema
 * identifica o que vence e, na data, o despachante confirma: as Ordens dos centros de trabalho cadastrados para o
 * depósito (Administração > Empresa) passam a Depósito Bloqueado de uma vez. Exige closure.dispatch.
 */
class DepositCalendar extends Component
{
    use WithFileUploads;
    use WithPagination;

    public const PER_PAGE = 25;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $month = '';

    public string $status = 'PENDING';

    public string $serviceType = '';

    public string $due = '';

    public $calendarFile = null;

    public bool $importModal = false;

    public ?array $importResult = null;

    public ?int $confirmingId = null;

    public string $confirmReason = '';

    public ?int $skippingId = null;

    public string $skipReason = '';

    public ?int $releasingId = null;

    public string $releaseNote = '';

    /** @var array<int, string> ids de entradas selecionadas para ação em massa */
    public array $selected = [];

    public ?string $bulkAction = null;

    public string $bulkReason = '';

    public ?array $bulkResult = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'month', 'status', 'serviceType', 'due'], true)) {
            $this->selected = [];
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'month', 'serviceType', 'due');
        $this->status = '';
        $this->resetPage();
    }

    public function setDue(string $due): void
    {
        $this->due    = $this->due === $due ? '' : $due;
        $this->status = $due === 'done' ? '' : 'PENDING';
        $this->resetPage();
    }

    // ------------------------------------------------------------- Importação

    public function openImport(): void
    {
        $this->reset('calendarFile', 'importResult');
        $this->resetValidation();
        $this->importModal = true;
    }

    public function closeImport(): void
    {
        $this->importModal = false;
        $this->reset('calendarFile');
    }

    public function import(): void
    {
        $this->validate(['calendarFile' => 'required|file|mimes:xlsx,xls|max:10240'], [
            'calendarFile.required' => 'Escolha a planilha do calendário.',
            'calendarFile.mimes'    => 'Envie um arquivo .xlsx ou .xls.',
            'calendarFile.max'      => 'A planilha passa de 10 MB.',
        ]);

        $this->importResult = app(ClosureDepositCalendarImporter::class)->import($this->calendarFile->getRealPath(), auth()->user());
        $this->reset('calendarFile');
        $this->resetPage();

        if (!$this->importResult['errors'] || $this->importResult['created'] + $this->importResult['updated'] > 0) {
            $this->toast('Calendário importado', "{$this->importResult['created']} nova(s), {$this->importResult['updated']} atualizada(s).");
        }
    }

    // ------------------------------------------------------------- Confirmar bloqueio

    public function startConfirm(int $entryId): void
    {
        $entry = $this->entries()->pending()->findOrFail($entryId);

        $this->confirmingId  = $entry->id;
        $this->confirmReason = "Inventário {$entry->year}: contagem inicial em {$entry->scheduled_date->format('d/m/Y')}. Depósito {$entry->deposit} bloqueado para contagem.";
    }

    public function cancelConfirm(): void
    {
        $this->reset('confirmingId', 'confirmReason');
        $this->resetValidation();
    }

    public function confirmBlock(): void
    {
        $entry = $this->entries()->pending()->findOrFail($this->confirmingId);

        if (trim($this->confirmReason) === '') {
            $this->addError('confirmReason', 'Informe o motivo do bloqueio.');

            return;
        }

        try {
            $signal = app(ClosurePartnerSignalService::class)->blockDeposit(
                $entry->deposit,
                auth()->user(),
                $this->confirmReason,
                $entry->center,
                null,
                ClosurePartnerSignal::ORIGIN_CALENDAR,
                $entry,
            );
        } catch (ClosureOrderWorkflowException $exception) {
            $this->addError('confirmReason', $exception->getMessage());

            return;
        }

        $this->cancelConfirm();
        $this->toast('Depósito bloqueado', "Depósito {$signal->deposit_code}: {$signal->affected_count} Ordem(ns) bloqueadas.");
    }

    // ------------------------------------------------------------- Dispensar / reabrir / liberar

    public function startSkip(int $entryId): void
    {
        $this->skippingId = $this->entries()->pending()->findOrFail($entryId)->id;
        $this->skipReason = '';
    }

    public function cancelSkip(): void
    {
        $this->reset('skippingId', 'skipReason');
    }

    public function confirmSkip(): void
    {
        $entry = $this->entries()->pending()->findOrFail($this->skippingId);

        $entry->update(['status' => ClosureDepositCalendarEntry::STATUS_SKIPPED, 'skip_reason' => trim($this->skipReason) ?: null, 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        $this->cancelSkip();
        $this->toast('Data dispensada', "Depósito {$entry->deposit} não será bloqueado nesta data.");
    }

    public function reopen(int $entryId): void
    {
        $entry = $this->entries()->where('status', ClosureDepositCalendarEntry::STATUS_SKIPPED)->findOrFail($entryId);

        $entry->update(['status' => ClosureDepositCalendarEntry::STATUS_PENDING, 'skip_reason' => null]);
    }

    public function startRelease(int $entryId): void
    {
        $this->releasingId = $this->entries()->where('status', ClosureDepositCalendarEntry::STATUS_BLOCKED)->findOrFail($entryId)->id;
        $this->releaseNote = '';
    }

    public function cancelRelease(): void
    {
        $this->reset('releasingId', 'releaseNote');
    }

    public function confirmRelease(): void
    {
        $entry = $this->entries()->where('status', ClosureDepositCalendarEntry::STATUS_BLOCKED)->with('Signal')->findOrFail($this->releasingId);

        try {
            $signal = app(ClosurePartnerSignalService::class)->releaseDeposit($entry->Signal, auth()->user(), $this->releaseNote);
        } catch (ClosureOrderWorkflowException $exception) {
            $this->cancelRelease();
            $this->toast('Não foi possível liberar', $exception->getMessage(), 'warning');

            return;
        }

        $this->cancelRelease();
        $this->toast('Depósito liberado', "{$signal->released_count} Ordem(ns) voltaram ao status anterior.");
    }

    // ------------------------------------------------------------- Ação em massa

    private const BULK_STATUS = [
        'block'   => ClosureDepositCalendarEntry::STATUS_PENDING,
        'skip'    => ClosureDepositCalendarEntry::STATUS_PENDING,
        'release' => ClosureDepositCalendarEntry::STATUS_BLOCKED,
        'reopen'  => ClosureDepositCalendarEntry::STATUS_SKIPPED,
    ];

    public function selectPage(array $ids): void
    {
        $ids = array_map('strval', $ids);

        $this->selected = array_diff($ids, $this->selected) === []
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function selectFiltered(): void
    {
        $this->selected = $this->filteredQuery()->limit(500)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function startBulk(string $action): void
    {
        abort_unless(isset(self::BULK_STATUS[$action]), 422);

        $this->bulkAction = $action;
        $this->bulkResult = null;
        $this->bulkReason = match ($action) {
            'block'   => 'Inventário ' . now()->year . ': depósito bloqueado para contagem.',
            'release' => 'Inventário concluído.',
            default   => '',
        };
        $this->resetValidation();
    }

    public function cancelBulk(): void
    {
        $this->reset('bulkAction', 'bulkReason');
        $this->resetValidation();
    }

    public function confirmBulk(): void
    {
        $action = $this->bulkAction;

        abort_unless($action && isset(self::BULK_STATUS[$action]), 422);

        if (in_array($action, ['block', 'release'], true) && trim($this->bulkReason) === '') {
            $this->addError('bulkReason', 'Informe o motivo.');

            return;
        }

        $entries = $this->entries()->with('Signal')->whereIn('id', $this->selected)->where('status', self::BULK_STATUS[$action])->orderBy('scheduled_date')->get();
        $service = app(ClosurePartnerSignalService::class);
        $done    = 0;
        $orders  = 0;
        $refused = [];

        foreach ($entries as $entry) {
            try {
                if ($action === 'block') {
                    $signal = $service->blockDeposit($entry->deposit, auth()->user(), trim($this->bulkReason), $entry->center, null, ClosurePartnerSignal::ORIGIN_CALENDAR, $entry);
                    $orders += (int) $signal->affected_count;
                } elseif ($action === 'release') {
                    $signal = $service->releaseDeposit($entry->Signal, auth()->user(), trim($this->bulkReason));
                    $orders += (int) $signal->released_count;
                } elseif ($action === 'skip') {
                    $entry->update(['status' => ClosureDepositCalendarEntry::STATUS_SKIPPED, 'skip_reason' => trim($this->bulkReason) ?: null, 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
                } else {
                    $entry->update(['status' => ClosureDepositCalendarEntry::STATUS_PENDING, 'skip_reason' => null]);
                }

                $done++;
            } catch (ClosureOrderWorkflowException $exception) {
                $refused[] = "Depósito {$entry->deposit} ({$entry->scheduled_date->format('d/m/Y')}): {$exception->getMessage()}";
            }
        }

        $ignored = count($this->selected) - $entries->count();

        $this->bulkResult = ['action' => $action, 'done' => $done, 'orders' => $orders, 'refused' => $refused, 'ignored' => max(0, $ignored)];
        $this->selected   = [];
        $this->reset('bulkAction', 'bulkReason');
        $this->toast('Ação em massa concluída', "{$done} depósito(s) processado(s)" . ($refused ? ', ' . count($refused) . ' recusado(s).' : '.'), $refused ? 'warning' : 'success');
    }

    public function dismissBulkResult(): void
    {
        $this->bulkResult = null;
    }

    // ------------------------------------------------------------- Dados

    public function render()
    {
        $today = Carbon::today();
        $query = $this->filteredQuery();

        $entries = $query->orderBy('scheduled_date')->orderBy('deposit')->paginate(self::PER_PAGE);

        $service = app(ClosurePartnerSignalService::class);
        $links   = [];

        foreach ($entries as $entry) {
            $rows = $service->workCenterRows($entry->deposit, $entry->center);

            $links[$entry->id] = [
                'rows'     => $rows,
                'centers'  => $rows->pluck('centerjob')->unique()->values()->all(),
                'affected' => $entry->status === ClosureDepositCalendarEntry::STATUS_PENDING && $rows->isNotEmpty()
                    ? $service->affectedStates($rows->pluck('centerjob')->unique()->values()->all())->count()
                    : null,
            ];
        }

        $pending = $this->entries()->pending();

        return view('livewire.closure.dispatch.deposit-calendar', [
            'entries' => $entries,
            'links'   => $links,
            'today'   => $today,
            'kpis'    => [
                'today'   => (clone $pending)->whereDate('scheduled_date', $today)->count(),
                'week'    => (clone $pending)->whereDate('scheduled_date', '>', $today)->whereDate('scheduled_date', '<=', $today->copy()->addDays(7))->count(),
                'overdue' => (clone $pending)->whereDate('scheduled_date', '<', $today)->count(),
                'active'  => ClosurePartnerSignal::query()->active()->where('origin', ClosurePartnerSignal::ORIGIN_CALENDAR)->count(),
            ],
            'months'         => $this->entries()->select('month')->distinct()->orderBy('month')->pluck('month'),
            'serviceTypes'   => $this->entries()->whereNotNull('service_type')->select('service_type')->distinct()->orderBy('service_type')->pluck('service_type'),
            'confirming'     => $this->confirmingId ? $this->entries()->find($this->confirmingId) : null,
            'confirmInfo'    => $this->confirmingId ? $this->confirmInfo() : null,
            'skipping'       => $this->skippingId ? $this->entries()->find($this->skippingId) : null,
            'releasing'      => $this->releasingId ? $this->entries()->with('Signal')->find($this->releasingId) : null,
            'hasEntries'     => $this->entries()->exists(),
            'selectedCounts' => $this->selected === [] ? [] : $this->entries()->whereIn('id', $this->selected)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
            'bulkCount'      => $this->bulkAction ? $this->entries()->whereIn('id', $this->selected)->where('status', self::BULK_STATUS[$this->bulkAction])->count() : 0,
        ]);
    }

    private function confirmInfo(): ?array
    {
        $entry = $this->entries()->find($this->confirmingId);

        if (!$entry) {
            return null;
        }

        $service = app(ClosurePartnerSignalService::class);
        $rows    = $service->workCenterRows($entry->deposit, $entry->center);

        return [
            'rows'     => $rows,
            'affected' => $rows->isEmpty() ? 0 : $service->affectedStates($rows->pluck('centerjob')->unique()->values()->all())->count(),
            'active'   => $service->activeFor($entry->deposit),
        ];
    }

    private function entries(): Builder
    {
        return ClosureDepositCalendarEntry::query();
    }

    private function filteredQuery(): Builder
    {
        $today = Carbon::today();

        return $this->entries()
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->month !== '', fn ($q) => $q->where('month', (int) $this->month))
            ->when($this->serviceType !== '', fn ($q) => $q->where('service_type', $this->serviceType))
            ->when($this->due === 'today', fn ($q) => $q->whereDate('scheduled_date', $today))
            ->when($this->due === 'week', fn ($q) => $q->whereDate('scheduled_date', '>=', $today)->whereDate('scheduled_date', '<=', $today->copy()->addDays(7)))
            ->when($this->due === 'overdue', fn ($q) => $q->whereDate('scheduled_date', '<', $today))
            ->when(trim($this->search) !== '', function ($q) {
                $like = '%' . addcslashes(trim($this->search), '%_\\') . '%';
                $q->where(fn ($w) => $w->where('deposit', 'like', $like)->orWhere('denomination', 'like', $like)->orWhere('city', 'like', $like)->orWhere('center', 'like', $like));
            });
    }

    private function toast(string $title, string $text, string $icon = 'success'): void
    {
        $this->dispatchBrowserEvent('notification:toast', ['title' => $title, 'text' => $text, 'icon' => $icon]);
    }
}
