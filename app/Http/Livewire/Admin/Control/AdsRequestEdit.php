<?php

namespace App\Http\Livewire\Admin\Control;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\User;
use Livewire\Component;

class AdsRequestEdit extends Component
{
    public ?AdsRequest $requestModel = null;
    public ?string $requestedBy = null;
    public ?string $statusValue = null;
    public ?string $description = null;
    public ?string $url = null;
    public ?string $lastError = null;
    public bool $completed = false;
    public bool $partner = false;
    public int $attempts = 0;
    public int $version = 1;

    public ?string $startedAt = null;
    public ?string $completedAt = null;
    public ?string $deliveredAt = null;
    public ?string $canceledAt = null;
    public ?string $nextRetryAt = null;

    public $users = [];

    protected $listeners = [
        'getInfoResponse',
        'resetForm' => 'resetForm',
    ];

    protected function rules(): array
    {
        return [
            'requestedBy' => ['required', 'exists:users,id'],
            'statusValue' => ['required', 'in:' . implode(',', AdsRequestStatus::values())],
            'description' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'lastError' => ['nullable', 'string'],
            'completed' => ['boolean'],
            'partner' => ['boolean'],
            'attempts' => ['nullable', 'integer', 'min:0', 'max:999'],
            'version' => ['nullable', 'integer', 'min:1'],
            'startedAt' => ['nullable', 'date'],
            'completedAt' => ['nullable', 'date'],
            'deliveredAt' => ['nullable', 'date'],
            'canceledAt' => ['nullable', 'date'],
            'nextRetryAt' => ['nullable', 'date'],
        ];
    }

    public function mount(): void
    {
        $this->users = User::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function getInfoResponse(AdsRequest $adsRequest): void
    {
        $this->resetForm(false);

        $this->requestModel = $adsRequest->load(['note:id,note', 'company:id,name', 'requestedBy:id,name,email']);
        $this->requestedBy = (string) $this->requestModel->requested_by;
        $this->statusValue = $this->requestModel->status?->value ?? (string) $this->requestModel->status;
        $this->description = $this->requestModel->description;
        $this->url = $this->requestModel->url;
        $this->lastError = $this->requestModel->last_error;
        $this->completed = (bool) $this->requestModel->completed;
        $this->partner = (bool) $this->requestModel->partner;
        $this->attempts = (int) ($this->requestModel->attempts ?? 0);
        $this->version = (int) ($this->requestModel->version ?? 1);

        $this->startedAt = $this->formatDateTimeLocal($this->requestModel->started_at);
        $this->completedAt = $this->formatDateTimeLocal($this->requestModel->completed_at);
        $this->deliveredAt = $this->formatDateTimeLocal($this->requestModel->delivered_at);
        $this->canceledAt = $this->formatDateTimeLocal($this->requestModel->canceled_at);
        $this->nextRetryAt = $this->formatDateTimeLocal($this->requestModel->next_retry_at);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminAdsRequestModal',
        ]);
    }

    public function save(): void
    {
        if (!$this->requestModel) {
            return;
        }

        $this->validate();

        $this->requestModel->requested_by = $this->requestedBy;
        $this->requestModel->status = $this->statusValue;
        $this->requestModel->description = $this->description;
        $this->requestModel->url = $this->url;
        $this->requestModel->last_error = $this->lastError;
        $this->requestModel->completed = $this->completed;
        $this->requestModel->partner = $this->partner;
        $this->requestModel->attempts = $this->attempts;
        $this->requestModel->version = $this->version;
        $this->requestModel->started_at = $this->normalizeDateTime($this->startedAt);
        $this->requestModel->completed_at = $this->normalizeDateTime($this->completedAt);
        $this->requestModel->delivered_at = $this->normalizeDateTime($this->deliveredAt);
        $this->requestModel->canceled_at = $this->normalizeDateTime($this->canceledAt);
        $this->requestModel->next_retry_at = $this->normalizeDateTime($this->nextRetryAt);
        $this->requestModel->save();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'ADS solicitada atualizada com sucesso!',
            'timer' => 2200,
        ]);

        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm(false);
        $this->emitUp('refresh_list');
    }

    public function getStatusOptionsProperty(): array
    {
        return AdsRequestStatus::cases();
    }

    public function resetForm(bool $refresh = true): void
    {
        $this->resetErrorBag();
        $this->requestModel = null;
        $this->requestedBy = null;
        $this->statusValue = null;
        $this->description = null;
        $this->url = null;
        $this->lastError = null;
        $this->completed = false;
        $this->partner = false;
        $this->attempts = 0;
        $this->version = 1;
        $this->startedAt = null;
        $this->completedAt = null;
        $this->deliveredAt = null;
        $this->canceledAt = null;
        $this->nextRetryAt = null;

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
        return view('livewire.admin.control.ads-request-edit', [
            'statusOptions' => $this->statusOptions,
        ]);
    }
}
