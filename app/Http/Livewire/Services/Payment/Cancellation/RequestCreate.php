<?php

namespace App\Http\Livewire\Services\Payment\Cancellation;

use App\Models\CancellationCategory;
use App\Models\CancellationRequest;
use App\Models\Note;
use App\Services\Payment\CancellationRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class RequestCreate extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    public string $service;
    public string $noteSearch = '';
    public ?Note $note = null;
    public array $orders = [];
    public string $scope = CancellationRequest::SCOPE_NOTE_FULL;
    public array $selectedOrders = [];
    public ?int $categoryId = null;
    public ?string $description = null;

    public $files = [];
    public array $tempFiles = [];

    public array $config = [
        'disk' => 'public',
        'base_path' => 'evidences/CANCELLATION_REQUEST',
        'max_size_mb' => 10,
        'allowed_exts' => [
            'jpg','jpeg','png','gif','bmp','svg','tiff','webp',
            'pdf','doc','docx','odt','xls','xlsx','xlsm','ods',
            'dwg','dxf','dws','dwt','dgn','rvt','rfa','skp','txt'
        ],
    ];

    protected $listeners = ['resetCancellationForm' => 'resetForm'];

    public function mount(string $service): void
    {
        $this->service = $service;
    }

    public function updatedFiles(): void
    {
        $this->validateOnly('files.*');

        foreach ($this->files as $file) {
            $this->tempFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'extension' => strtolower($file->getClientOriginalExtension()),
                'size' => $file->getSize(),
                'file' => $file,
            ];
        }

        $this->files = [];
    }

    public function removeTempFile(int $index): void
    {
        if (isset($this->tempFiles[$index])) {
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
        }
    }

    public function findNote(): void
    {
        $this->resetNoteData();

        if (!trim($this->noteSearch)) {
            $this->addError('noteSearch', 'Informe o número da Nota.');
            return;
        }

        $note = Note::where('note', $this->noteSearch)->with('Orders')->first();

        if (!$note) {
            $this->addError('noteSearch', 'Nota não encontrada.');
            return;
        }

        $this->note = $note;
        $this->orders = $note->Orders->map(function ($order) {
            return [
                'id' => $order->id,
                'ordem' => $order->ordem,
                'status' => $order->statusUser ?? $order->statusSist,
                'canceled' => (bool) $order->canceled,
            ];
        })->toArray();

        if ($note->canceled || $note->Orders->where('canceled', true)->count() > 0) {
            $this->scope = CancellationRequest::SCOPE_ORDERS_PARTIAL;
        }
    }

    public function submit(CancellationRequestService $service): void
    {
        $this->authorize('create', CancellationRequest::class);

        if (!$this->note) {
            $this->addError('noteSearch', 'Carregue uma Nota válida antes de enviar.');
            return;
        }

        $this->validate();

        $category = CancellationCategory::where('active', true)->find($this->categoryId);
        if (!$category) {
            $this->addError('categoryId', 'Categoria inválida ou inativa.');
            return;
        }

        try {
            $attachments = array_map(fn ($item) => $item['file'], $this->tempFiles);

            $service->createRequest(
                $this->note,
                $this->scope,
                $category,
                $this->selectedOrders,
                $attachments,
                Auth::user(),
                $this->description
            );

            $this->dispatchBrowserEvent('swal', [
                'icon' => 'success',
                'title' => 'Solicitação enviada com sucesso!'
            ]);

            $this->resetForm();
        } catch (RuntimeException $e) {
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'error',
                'title' => $e->getMessage(),
            ]);
        }
    }

    protected function rules(): array
    {
        $maxKb = $this->config['max_size_mb'] * 1024;
        $mimes = implode(',', $this->config['allowed_exts']);

        $rules = [
            'noteSearch' => 'required|string',
            'scope' => 'required|in:' . CancellationRequest::SCOPE_NOTE_FULL . ',' . CancellationRequest::SCOPE_ORDERS_PARTIAL,
            'categoryId' => 'required|integer',
            'description' => 'nullable|string|max:2000',
            'selectedOrders' => 'array',
            'selectedOrders.*' => 'integer',
            'files.*' => "nullable|file|mimes:{$mimes}|max:{$maxKb}",
        ];

        if ($this->scope === CancellationRequest::SCOPE_ORDERS_PARTIAL) {
            $rules['selectedOrders'] = 'required|array|min:1';
        }

        return $rules;
    }

    private function resetForm(): void
    {
        $this->noteSearch = '';
        $this->note = null;
        $this->orders = [];
        $this->scope = CancellationRequest::SCOPE_NOTE_FULL;
        $this->selectedOrders = [];
        $this->categoryId = null;
        $this->description = null;
        $this->files = [];
        $this->tempFiles = [];
    }

    private function resetNoteData(): void
    {
        $this->note = null;
        $this->orders = [];
        $this->selectedOrders = [];
    }

    public function render()
    {
        $categories = CancellationCategory::where('active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $noteCanceled = $this->note?->canceled ?? false;
        $hasCanceledOrders = $this->note && $this->note->Orders->where('canceled', true)->count() > 0;

        return view('livewire.services.payment.cancellation.request-create', [
            'categories' => $categories,
            'noteCanceled' => $noteCanceled,
            'hasCanceledOrders' => $hasCanceledOrders,
        ]);
    }
}
