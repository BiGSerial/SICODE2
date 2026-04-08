<?php

namespace App\Http\Livewire\Admin\Control;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Support\Facades\DB;
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
    public $deleteAdsFormId = null;
    public ?array $firstValidAdsRequest = null;

    public bool $adsFormEnabled = false;
    public ?int $adsFormId = null;
    public int $adsFilesCount = 0;
    public ?string $adsName = null;
    public ?string $adsObs = null;
    public ?string $adsContract = null;
    public ?string $adsCenter = null;
    public ?string $adsDeposit = null;
    public ?string $adsAmount = null;
    public bool $adsPartial = false;
    public bool $adsTacit = false;
    public ?string $adsTacitDueAt = null;
    public ?string $adsTacitDeliveredAt = null;

    protected $listeners = [
        'getInfoResponse',
        'resetForm' => 'resetForm',
        'confirmDeleteAdsForm' => 'deleteAdsForm',
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
            'adsName' => ['nullable', 'string', 'max:191'],
            'adsObs' => ['nullable', 'string'],
            'adsContract' => ['nullable', 'string', 'max:191'],
            'adsCenter' => ['nullable', 'string', 'max:191'],
            'adsDeposit' => ['nullable', 'string', 'max:191'],
            'adsAmount' => ['nullable', 'string', 'max:50'],
            'adsPartial' => ['boolean'],
            'adsTacit' => ['boolean'],
            'adsTacitDueAt' => ['nullable', 'date'],
            'adsTacitDeliveredAt' => ['nullable', 'date'],
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
        $this->workReport = $workReport->load(['Note', 'Company', 'User', 'Orders', 'Adsform.Files']);
        $this->informedAt = $this->formatDateTimeLocal($this->workReport->informed_at);
        $this->acceptanceAt = $this->formatDateTimeLocal($this->workReport->acceptance_at);
        $this->acceptanceMetaJson = $this->workReport->acceptance_meta
            ? json_encode($this->workReport->acceptance_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : null;
        $this->syncAdsFormState();

        $this->refreshOrders();
        $this->refreshFirstValidAdsRequest();

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminWorkReportModal',
        ]);
    }

    public function updatedWorkReportNoteId(): void
    {
        $this->refreshOrders();
        $this->refreshFirstValidAdsRequest();
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

    private function refreshFirstValidAdsRequest(): void
    {
        $this->firstValidAdsRequest = null;

        if (!$this->workReport?->note_id) {
            return;
        }

        $request = AdsRequest::query()
            ->with(['requestedBy:id,name'])
            ->where('note_id', $this->workReport->note_id)
            ->where('status', AdsRequestStatus::DONE->value)
            ->whereNotNull('url')
            ->whereRaw("NULLIF(LTRIM(RTRIM(url)), '') IS NOT NULL")
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if (!$request) {
            return;
        }

        $deliveredAt = $request->delivered_at ?? $request->completed_at;
        $createdAt = $request->created_at;
        $deadlineAt = $createdAt?->copy()->addDay()->endOfDay();
        $withinDeadline = $deliveredAt && $deadlineAt ? $deliveredAt->lte($deadlineAt) : null;

        $this->firstValidAdsRequest = [
            'id' => $request->id,
            'status' => $request->status instanceof AdsRequestStatus ? $request->status->value : (string) $request->status,
            'url' => (string) $request->url,
            'delivered_at' => $deliveredAt?->format('d/m/Y H:i:s'),
            'elapsed' => $this->formatElapsedTime($createdAt, $deliveredAt),
            'within_deadline' => $withinDeadline,
            'requested_by_name' => $request->requestedBy?->name,
        ];
    }

    private function formatElapsedTime($from, $to): ?string
    {
        if (!$from || !$to) {
            return null;
        }

        $seconds = $from->diffInSeconds($to, false);
        if ($seconds < 0) {
            return '0m';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
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
        DB::transaction(function () {
            $this->workReport->save();
            $this->saveAdsForm();
        });

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
        $this->firstValidAdsRequest = null;
        $this->deleteAdsFormId = null;
        $this->resetAdsFormState();

        if ($refresh) {
            $this->emitUp('refresh_list');
        }
    }

    public function enableAdsForm(): void
    {
        $this->adsFormEnabled = true;
        if (!$this->adsName && $this->workReport?->responsible) {
            $this->adsName = $this->workReport->responsible;
        }
    }

    public function requestDeleteAdsForm(): void
    {
        if (!$this->workReport?->Adsform) {
            return;
        }

        $this->deleteAdsFormId = $this->workReport->Adsform->id;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Excluir ADSForm',
            'msg'           => 'Tem certeza que deseja excluir este ADSForm? Esta acao nao podera ser desfeita.',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Excluir!',
            'btnCanceltxt'  => 'Nao, Cancele',
            'action'        => 'confirmDeleteAdsForm',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'ADSForm nao foi excluido.',
        ]);
    }

    public function deleteAdsForm(): void
    {
        if (!$this->deleteAdsFormId || !$this->workReport) {
            return;
        }

        $adsForm = $this->workReport->Adsform()->whereKey($this->deleteAdsFormId)->first();
        if (!$adsForm) {
            $this->deleteAdsFormId = null;
            return;
        }

        DB::transaction(function () use ($adsForm) {
            $adsForm->Files()->detach();
            $adsForm->delete();
        });

        $this->workReport->load(['Adsform.Files']);
        $this->deleteAdsFormId = null;
        $this->syncAdsFormState();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ADSForm excluido com sucesso!',
            'timer'    => 2200,
        ]);

        $this->emitUp('refresh_list');
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

    private function resetAdsFormState(): void
    {
        $this->adsFormEnabled = false;
        $this->adsFormId = null;
        $this->adsFilesCount = 0;
        $this->adsName = null;
        $this->adsObs = null;
        $this->adsContract = null;
        $this->adsCenter = null;
        $this->adsDeposit = null;
        $this->adsAmount = null;
        $this->adsPartial = false;
        $this->adsTacit = false;
        $this->adsTacitDueAt = null;
        $this->adsTacitDeliveredAt = null;
    }

    private function syncAdsFormState(): void
    {
        $ads = $this->workReport?->Adsform;
        if (!$ads) {
            $this->resetAdsFormState();
            return;
        }

        $this->adsFormEnabled = true;
        $this->adsFormId = $ads->id;
        $this->adsFilesCount = $ads->Files->count();
        $this->adsName = $ads->name;
        $this->adsObs = $ads->obs;
        $this->adsContract = $ads->contract;
        $this->adsCenter = $ads->center;
        $this->adsDeposit = $ads->deposit;
        $this->adsAmount = $ads->amount !== null ? (string) $ads->amount : null;
        $this->adsPartial = (bool) $ads->partial;
        $this->adsTacit = (bool) $ads->tacit;
        $this->adsTacitDueAt = $this->formatDateTimeLocal($ads->tacit_due_at);
        $this->adsTacitDeliveredAt = $this->formatDateTimeLocal($ads->tacit_delivered_at);
    }

    private function saveAdsForm(): void
    {
        if (!$this->workReport || !$this->adsFormEnabled) {
            return;
        }

        $tacitDueAt = $this->normalizeDateTime($this->adsTacitDueAt);
        $tacitDeliveredAt = $this->normalizeDateTime($this->adsTacitDeliveredAt);

        if (!$this->adsTacit) {
            $tacitDueAt = null;
            $tacitDeliveredAt = null;
        }

        $payload = [
            'note_id' => $this->workReport->note_id,
            'user_id' => $this->workReport->user_id,
            'name' => $this->normalizeNullableString($this->adsName),
            'obs' => $this->normalizeNullableString($this->adsObs),
            'contract' => $this->normalizeNullableString($this->adsContract),
            'center' => $this->normalizeNullableString($this->adsCenter),
            'deposit' => $this->normalizeNullableString($this->adsDeposit),
            'amount' => $this->normalizeAmount($this->adsAmount),
            'partial' => $this->adsPartial,
            'tacit' => $this->adsTacit,
            'tacit_due_at' => $tacitDueAt,
            'tacit_delivered_at' => $tacitDeliveredAt,
        ];

        $ads = $this->workReport->Adsform()->first();
        if ($ads) {
            $ads->update($payload);
        } else {
            $this->workReport->Adsform()->create($payload);
        }

        $this->workReport->load(['Adsform.Files']);
        $this->syncAdsFormState();
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizeAmount(?string $value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            if (strpos($raw, ',') > strpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    public function render()
    {
        return view('livewire.admin.control.work-report-edit');
    }
}
