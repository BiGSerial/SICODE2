<?php

namespace App\Http\Livewire\Services\Payment\Uncancellation;

use App\Enum\CancellationRequestScope;
use App\Models\Note;
use App\Services\Payment\UncancellationRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class RequestCreate extends Component
{
    private const BULK_MAX_INPUT = 3000;

    public string $service;
    public string $createMode = 'single';
    public string $noteSearch = '';
    public ?Note $note = null;
    public array $orders = [];
    public string $scope = CancellationRequestScope::NOTE_FULL->value;
    public array $selectedOrders = [];
    public ?string $description = null;
    public ?string $bulkNotesInput = null;
    public array $bulkCandidates = [];
    public array $selectedBulkNoteIds = [];
    public bool $bulkProcessed = false;

    protected $listeners = [
        'confirm_uncancellation_request_submit' => 'confirmSubmit',
    ];

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function setCreateMode(string $mode): void
    {
        if (!in_array($mode, ['single', 'bulk'], true)) {
            return;
        }

        $this->createMode = $mode;
        $this->resetValidation();
        $this->reset(['noteSearch', 'note', 'orders', 'selectedOrders', 'bulkNotesInput', 'bulkCandidates', 'selectedBulkNoteIds', 'bulkProcessed']);
        $this->scope = CancellationRequestScope::NOTE_FULL->value;
    }

    public function findNote(): void
    {
        $this->resetValidation();
        $this->note = Note::query()
            ->with(['Orders', 'WorkFormAny'])
            ->where('note', trim($this->noteSearch))
            ->first();

        if (!$this->note) {
            $this->addError('noteSearch', 'Nota/OV não encontrada.');
            $this->orders = [];
            return;
        }

        $this->orders = $this->note->Orders
            ->map(fn ($order) => [
                'id' => (int) $order->id,
                'ordem' => (string) $order->ordem,
                'status' => (string) ($order->statusUser ?: $order->statusSist ?: '-'),
                'canceled' => (bool) $order->canceled,
            ])
            ->values()
            ->all();

        $this->selectedOrders = collect($this->orders)
            ->where('canceled', true)
            ->pluck('id')
            ->all();
    }

    public function processBulkNotes(): void
    {
        $values = collect(preg_split('/[\s,;\n\r]+/', (string) $this->bulkNotesInput))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            $this->addError('bulkNotesInput', 'Informe ao menos uma Nota/OV.');
            return;
        }

        if ($values->count() > self::BULK_MAX_INPUT) {
            $this->addError('bulkNotesInput', 'Limite de itens por pesquisa: ' . self::BULK_MAX_INPUT . '.');
            return;
        }

        $notes = Note::query()
            ->select(['id', 'note', 'client', 'canceled'])
            ->withCount(['Orders as canceled_orders_count' => fn ($q) => $q->where('canceled', true)])
            ->whereIn('note', $values->all())
            ->get();

        $this->bulkCandidates = $notes
            ->map(fn ($note) => [
                'id' => (int) $note->id,
                'note' => (string) $note->note,
                'client' => (string) ($note->client ?? '-'),
                'note_canceled' => (bool) $note->canceled,
                'canceled_orders' => (int) $note->canceled_orders_count,
                'eligible' => (bool) $note->canceled,
            ])
            ->values()
            ->all();

        $this->selectedBulkNoteIds = collect($this->bulkCandidates)
            ->where('eligible', true)
            ->pluck('id')
            ->all();
        $this->bulkProcessed = true;
    }

    public function submit(): void
    {
        if (trim((string) $this->description) === '') {
            $this->addError('description', 'Informe a justificativa.');
            return;
        }

        $target = $this->createMode === 'bulk'
            ? count($this->selectedBulkNoteIds) . ' Nota/OV(s)'
            : ($this->note?->note ?? 'Nota/OV selecionada');

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Solicitar descancelamento',
            'msg' => "Deseja solicitar o descancelamento de <strong>{$target}</strong>?",
            'icon' => 'question',
            'btnOktxt' => 'Sim, solicitar',
            'btnCanceltxt' => 'Não, cancelar',
            'action' => 'confirm_uncancellation_request_submit',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg' => 'A solicitação não foi enviada.',
        ]);
    }

    public function confirmSubmit(UncancellationRequestService $service): void
    {
        try {
            if ($this->createMode === 'bulk') {
                $created = 0;
                foreach ($this->selectedBulkNoteIds as $noteId) {
                    $note = Note::with('Orders')->find($noteId);
                    if (!$note || !$note->canceled) {
                        continue;
                    }
                    $service->createRequest($note, CancellationRequestScope::NOTE_FULL->value, [], Auth::user(), $this->description);
                    $created++;
                }

                if ($created === 0) {
                    throw new RuntimeException('Nenhuma Nota/OV cancelada selecionada.');
                }
            } else {
                if (!$this->note) {
                    throw new RuntimeException('Busque uma Nota/OV antes de solicitar.');
                }

                $service->createRequest($this->note, $this->scope, $this->selectedOrders, Auth::user(), $this->description);
            }

            $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Solicitação enviada.']);
            $this->reset(['noteSearch', 'note', 'orders', 'selectedOrders', 'description', 'bulkNotesInput', 'bulkCandidates', 'selectedBulkNoteIds', 'bulkProcessed']);
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.services.payment.uncancellation.request-create', [
            'scopeOptions' => CancellationRequestScope::cases(),
        ]);
    }
}
