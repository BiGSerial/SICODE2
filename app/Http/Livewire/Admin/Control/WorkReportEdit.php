<?php

namespace App\Http\Livewire\Admin\Control;

use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkReport;
use Livewire\Component;

class WorkReportEdit extends Component
{
    public ?WorkReport $workReport = null;
    public ?string $informedAt = null;
    public ?string $acceptanceAt = null;
    public ?string $acceptanceMetaJson = null;
    public $companies = [];
    public $users = [];
    public $availableOrders = [];
    public $linkedOrders = [];

    protected $listeners = [
        'getInfoResponse',
        'resetForm' => 'resetForm',
    ];

    protected function rules(): array
    {
        return [
            'workReport.note_id' => ['nullable', 'integer'],
            'workReport.company_id' => ['nullable', 'uuid'],
            'workReport.user_id' => ['nullable', 'uuid'],
            'workReport.date' => ['nullable', 'date'],
            'workReport.dd' => ['nullable', 'string', 'max:191'],
            'workReport.informer' => ['nullable', 'string', 'max:191'],
            'workReport.team' => ['nullable', 'string', 'max:191'],
            'workReport.responsible' => ['nullable', 'string', 'max:191'],
            'workReport.observation' => ['nullable', 'string'],
            'workReport.description' => ['nullable', 'string'],
            'workReport.acceptance_name' => ['nullable', 'string', 'max:191'],
            'informedAt' => ['nullable', 'date'],
            'acceptanceAt' => ['nullable', 'date'],
            'workReport.equipment' => ['boolean'],
            'workReport.connection' => ['boolean'],
            'workReport.changes' => ['boolean'],
            'workReport.damage' => ['boolean'],
            'workReport.approved' => ['boolean'],
            'workReport.rejected' => ['boolean'],
            'workReport.retry' => ['boolean'],
            'workReport.acceptance_accepted' => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->companies = Company::orderBy('name')->get();
        $this->users = User::orderBy('name')->get();
    }

    public function getInfoResponse(WorkReport $workReport): void
    {
        $this->resetForm(false);
        $this->workReport = $workReport->load(['Note', 'Company', 'User', 'Orders']);
        $this->informedAt = $this->formatDateTimeLocal($this->workReport->informed_at);
        $this->acceptanceAt = $this->formatDateTimeLocal($this->workReport->acceptance_at);
        $this->acceptanceMetaJson = $this->workReport->acceptance_meta
            ? json_encode($this->workReport->acceptance_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : null;

        $this->refreshOrders();

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminWorkReportModal',
        ]);
    }

    public function updatedWorkReportNoteId(): void
    {
        $this->refreshOrders();
    }

    private function refreshOrders(): void
    {
        if (!$this->workReport?->note_id) {
            $this->availableOrders = [];
            $this->linkedOrders = [];
            return;
        }

        $orders = Order::where('note_id', $this->workReport->note_id)->orderBy('ordem')->get();
        $linkedIds = $this->workReport->Orders->pluck('id')->all();

        $this->linkedOrders = $orders->whereIn('id', $linkedIds)->values()->all();
        $this->availableOrders = $orders->whereNotIn('id', $linkedIds)->values()->all();
    }

    public function addOrder(int $orderId): void
    {
        if (!$this->workReport) {
            return;
        }

        $this->workReport->Orders()->syncWithoutDetaching([$orderId]);
        $this->workReport->load('Orders');
        $this->refreshOrders();
    }

    public function removeOrder(int $orderId): void
    {
        if (!$this->workReport) {
            return;
        }

        $this->workReport->Orders()->detach($orderId);
        $this->workReport->load('Orders');
        $this->refreshOrders();
    }

    public function save(): void
    {
        if (!$this->workReport) {
            return;
        }

        $this->validate();

        $meta = null;
        if (filled($this->acceptanceMetaJson)) {
            $meta = json_decode((string) $this->acceptanceMetaJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'JSON invalido em acceptance_meta',
                    'text'     => json_last_error_msg(),
                ]);
                return;
            }
        }

        $this->workReport->informed_at = $this->normalizeDateTime($this->informedAt);
        $this->workReport->acceptance_at = $this->normalizeDateTime($this->acceptanceAt);
        $this->workReport->acceptance_meta = $meta;
        $this->workReport->save();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'WorkReport atualizado com sucesso!',
            'timer'    => 2500,
        ]);

        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm(false);
        $this->emitUp('refresh_list');
    }

    public function resetForm(bool $refresh = true): void
    {
        $this->resetErrorBag();
        $this->workReport = null;
        $this->informedAt = null;
        $this->acceptanceAt = null;
        $this->acceptanceMetaJson = null;
        $this->availableOrders = [];
        $this->linkedOrders = [];

        if ($refresh) {
            $this->emitUp('refresh_list');
        }
    }

    private function formatDateTimeLocal($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d\TH:i')
                : \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i');
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
        return view('livewire.admin.control.work-report-edit');
    }
}
