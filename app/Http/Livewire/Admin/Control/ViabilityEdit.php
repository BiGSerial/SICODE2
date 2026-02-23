<?php

namespace App\Http\Livewire\Admin\Control;

use App\Models\Company;
use App\Models\File;
use App\Models\Order;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ViabilityEdit extends Component
{
    public ?Viability $viability = null;
    public ?string $initAt = null;
    public ?string $sendedAt = null;
    public ?string $returnedAt = null;
    public ?string $tacitAt = null;
    public ?string $completedAt = null;
    public ?string $engineerAt = null;
    public ?string $hiredAt = null;
    public $companies = [];
    public $companyUsers = [];
    public $engineers = [];
    public $availableOrders = [];
    public $linkedOrders = [];
    public $pendingFileSave = false;
    public $hasFile = false;
    public $deleteFileId;

    protected $listeners = [
        'getInfoResponse',
        'savedFiles' => 'onFilesSaved',
        'continue' => 'onFilesSaved',
        'hasFile' => 'hasFile',
        'resetForm' => 'resetForm',
        'confirmDeleteFile' => 'confirmDeleteFile',
    ];

    protected function rules(): array
    {
        return [
            'viability.order_id' => ['nullable', 'integer'],
            'viability.company_id' => ['nullable', 'uuid'],
            'viability.user_id' => ['nullable', 'uuid'],
            'viability.engineer_id' => ['nullable', 'uuid'],
            'initAt' => ['nullable', 'date'],
            'sendedAt' => ['nullable', 'date'],
            'returnedAt' => ['nullable', 'date'],
            'tacitAt' => ['nullable', 'date'],
            'completedAt' => ['nullable', 'date'],
            'engineerAt' => ['nullable', 'date'],
            'hiredAt' => ['nullable', 'date'],
            'viability.tacit' => ['nullable', 'boolean'],
            'viability.completed' => ['nullable', 'boolean'],
            'viability.canceled' => ['nullable', 'boolean'],
            'viability.rejected' => ['nullable', 'boolean'],
            'viability.approved' => ['nullable', 'boolean'],
            'viability.engineer' => ['nullable', 'boolean'],
            'viability.hired' => ['nullable', 'boolean'],
            'viability.replica' => ['nullable', 'boolean'],
            'viability.treplica' => ['nullable', 'boolean'],
            'viability.inActivity' => ['nullable', 'boolean'],
            'viability.visible_partner' => ['nullable', 'boolean'],
            'viability.rehired' => ['nullable', 'boolean'],
            'viability.status' => ['nullable', 'integer'],
            'viability.value' => ['nullable', 'numeric'],
        ];
    }

    public function mount(): void
    {
        $this->companies = Company::orderBy('name')->get();
        $this->engineers = User::orderBy('name')->get();
    }

    public function getInfoResponse(Viability $viability): void
    {
        $this->resetForm(false);
        // Nao carregar a relacao Engineer aqui para evitar conflito com o campo booleano `engineer`.
        $this->viability = $viability->load(['Note', 'Company', 'User', 'Orders', 'Files']);

        $this->initAt = $this->formatDateTimeLocal($this->viability->init_at);
        $this->sendedAt = $this->formatDateTimeLocal($this->viability->sended_at);
        $this->returnedAt = $this->formatDateTimeLocal($this->viability->returned_at);
        $this->tacitAt = $this->formatDateTimeLocal($this->viability->tacit_at);
        $this->completedAt = $this->formatDateTimeLocal($this->viability->completed_at);
        $this->engineerAt = $this->formatDateTimeLocal($this->viability->engineer_at);
        $this->hiredAt = $this->formatDateTimeLocal($this->viability->hired_at);

        $this->refreshCompanyUsers($this->viability->company_id);
        $this->refreshOrders();

        $this->emitTo('files.manager.create-viab-files', 'cleanFiles');

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminViabilityModal',
        ]);
    }

    public function updatedViabilityCompanyId($value): void
    {
        $this->refreshCompanyUsers($value);
    }

    private function refreshCompanyUsers($companyId): void
    {
        if (!$companyId) {
            $this->companyUsers = [];
            return;
        }

        $this->companyUsers = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    private function refreshOrders(): void
    {
        if (!$this->viability?->note_id) {
            $this->availableOrders = [];
            $this->linkedOrders = [];
            return;
        }

        $orders = Order::where('note_id', $this->viability->note_id)->orderBy('ordem')->get();
        $linkedIds = $this->viability->Orders->pluck('id')->all();

        $this->linkedOrders = $orders->whereIn('id', $linkedIds)->values()->all();
        $this->availableOrders = $orders->whereNotIn('id', $linkedIds)->values()->all();
    }

    public function addOrder(int $orderId): void
    {
        if (!$this->viability) {
            return;
        }

        $this->viability->Orders()->syncWithoutDetaching([$orderId]);
        $this->viability->load('Orders');
        $this->refreshOrders();
    }

    public function removeOrder(int $orderId): void
    {
        if (!$this->viability) {
            return;
        }

        $this->viability->Orders()->detach($orderId);
        $this->viability->load('Orders');
        $this->refreshOrders();
    }

    public function save(): void
    {
        if (!$this->viability) {
            return;
        }

        try {
            $this->validate();

            $this->viability->init_at = $this->normalizeDateTime($this->initAt);
            $this->viability->sended_at = $this->normalizeDateTime($this->sendedAt);
            $this->viability->returned_at = $this->normalizeDateTime($this->returnedAt);
            $this->viability->tacit_at = $this->normalizeDateTime($this->tacitAt);
            $this->viability->completed_at = $this->normalizeDateTime($this->completedAt);
            $this->viability->engineer_at = $this->normalizeDateTime($this->engineerAt);
            $this->viability->hired_at = $this->normalizeDateTime($this->hiredAt);

            $this->viability->save();

            if (!$this->hasFile) {
                $this->onFilesSaved();
                return;
            }

            $this->pendingFileSave = true;
            $this->emitTo('files.manager.create-viab-files', 'saveFiles');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao salvar viabilidade',
                'text'     => $e->getMessage(),
            ]);
        }
    }

    public function hasFile(bool $has): void
    {
        $this->hasFile = $has;
    }

    public function onFilesSaved(): void
    {
        if (!$this->pendingFileSave && $this->hasFile) {
            return;
        }

        $this->pendingFileSave = false;

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Viabilidade atualizada com sucesso!',
            'timer'    => 2500,
        ]);

        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm(false);
        $this->emitUp('refresh_list');
    }

    public function requestDeleteFile(int $fileId): void
    {
        $this->deleteFileId = $fileId;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Arquivo',
            'msg'           => 'Confirma remover este arquivo?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Nao, Cancele',
            'action'        => 'confirmDeleteFile',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum arquivo foi removido.',
        ]);
    }

    public function confirmDeleteFile(): void
    {
        if (!$this->deleteFileId || !$this->viability) {
            return;
        }

        $file = File::find($this->deleteFileId);
        if (!$file) {
            return;
        }

        if (Storage::exists($file->path)) {
            Storage::delete($file->path);
        }

        $this->viability->Files()->detach($file->id);
        $file->delete();
        $this->viability->load('Files');
        $this->deleteFileId = null;
    }

    public function downloadFile(File $file)
    {
        if (Storage::exists($file->path)) {
            return Storage::download($file->path, $file->file_name);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO INEXISTENTE!',
            'timer'    => 2000,
        ]);
    }

    public function resetForm(bool $refresh = true): void
    {
        $this->resetErrorBag();
        $this->viability = null;
        $this->initAt = null;
        $this->sendedAt = null;
        $this->returnedAt = null;
        $this->tacitAt = null;
        $this->completedAt = null;
        $this->engineerAt = null;
        $this->hiredAt = null;
        $this->companyUsers = [];
        $this->availableOrders = [];
        $this->linkedOrders = [];
        $this->pendingFileSave = false;
        $this->hasFile = false;
        $this->deleteFileId = null;
        $this->emitTo('files.manager.create-viab-files', 'cleanFiles');

        if ($refresh) {
            $this->emitUp('refresh_list');
        }
    }

    private function formatDateTimeLocal($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (in_array((string) $value, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
            return null;
        }

        try {
            $date = $value instanceof \DateTimeInterface
                ? $value
                : \Carbon\Carbon::make($value);

            return $date ? $date->format('Y-m-d\\TH:i') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::make($value);
            return $date ? $date->format('Y-m-d H:i:s') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.admin.control.viability-edit', [
            'companies' => $this->companies,
            'companyUsers' => $this->companyUsers,
            'engineers' => $this->engineers,
        ]);
    }
}
