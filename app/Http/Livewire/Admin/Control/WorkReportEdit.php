<?php

namespace App\Http\Livewire\Admin\Control;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\File;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Support\Facades\DB;
use App\Services\WorkReports\WorkReportFinalScopeResolver;
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
    public array $relatedWorkReports = [];
    public array $relatedWorkReportOptions = [];
    public array $availableFinalScopes = [];
    public array $splitScopeSelection = [];
    public string $activeTab = 'details';
    public array $workReportFiles = [];
    public array $selectedFileIds = [];
    public array $fileTargetByFile = [];
    public $fileTargetWorkReportId = null;

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
        $this->availableFinalScopes = [
            WorkReportFinalScopeResolver::SCOPE_NETWORK,
            WorkReportFinalScopeResolver::SCOPE_CONNECTION,
        ];
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
        $this->refreshRelatedWorkReports();
        $this->refreshWorkReportFiles();
        $this->splitScopeSelection = [];
        $this->selectedFileIds = [];
        $this->fileTargetByFile = [];
        $this->fileTargetWorkReportId = $this->workReport->id;
        $this->activeTab = 'details';

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminWorkReportModal',
        ]);
    }

    public function switchRelatedWorkReport(int $workReportId): void
    {
        $noteId = (int) ($this->workReport?->note_id ?: 0);
        $target = WorkReport::query()
            ->whereKey($workReportId)
            ->where('note_id', $noteId)
            ->first();

        if ($target) {
            $this->getInfoResponse($target);
        }
    }

    public function splitWorkReportByScopes(): void
    {
        if (!$this->workReport) {
            return;
        }

        $currentScopes = collect($this->workReport->finalScopeBadges())
            ->pluck('scope')
            ->filter()
            ->unique()
            ->values();

        if ($currentScopes->count() !== 2) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'info',
                'title' => 'Separação indisponível',
                'html' => 'A separação só pode ser feita quando o informe possuir os dois escopos: Rede e Ligação.',
                'timer' => 3000,
            ]);

            return;
        }

        $scopes = collect($this->splitScopeSelection)
            ->map(fn ($scope) => (string) $scope)
            ->intersect($this->availableFinalScopes)
            ->unique()
            ->values()
            ->all();

        if (count($scopes) < 1) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione o escopo que será separado',
                'timer' => 2200,
            ]);

            return;
        }

        $source = $this->workReport->load([
            'Note',
            'Orders',
            'FlowProductions',
        ]);
        $ordersByScope = $this->ordersByScope($source);
        $sourceOrderIds = $source->Orders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedOrderIds = collect($scopes)
            ->flatMap(fn ($scope) => $ordersByScope[$scope] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $remainingOrderIds = array_values(array_diff($sourceOrderIds, $selectedOrderIds));

        if (empty($selectedOrderIds) || empty($remainingOrderIds)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Não foi possível separar esse escopo',
                'html' => empty($selectedOrderIds)
                    ? 'Não existem ordens identificadas para o escopo selecionado.'
                    : 'O informe original precisa manter ao menos uma ordem.',
                'timer' => 3000,
            ]);

            return;
        }

        $now = now();
        $created = [];

        DB::transaction(function () use ($source, $scopes, $ordersByScope, $remainingOrderIds, $now, &$created) {
            $sourceAttributes = $source->getAttributes();
            $remainingScopes = collect($ordersByScope)
                ->filter(fn (array $ids, string $scope) => !empty(array_intersect($ids, $remainingOrderIds)))
                ->keys()
                ->values()
                ->all();

            $source->Orders()->sync($remainingOrderIds);
            $source->selected_final_scopes = $remainingScopes ?: null;
            $source->save();

            foreach ($scopes as $scope) {
                $orderIds = array_values(array_diff($ordersByScope[$scope] ?? [], $remainingOrderIds));

                if (empty($orderIds)) {
                    continue;
                }

                $alreadyExists = WorkReport::query()
                    ->where('note_id', $source->note_id)
                    ->where('id', '!=', $source->id)
                    ->whereJsonContains('selected_final_scopes', $scope)
                    ->first();

                if ($alreadyExists) {
                    $alreadyExists->Orders()->syncWithoutDetaching($orderIds);
                    $created[] = $alreadyExists->id;
                    continue;
                }

                $attributes = $sourceAttributes;
                unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);
                $attributes['selected_final_scopes'] = [$scope];
                $attributes['date'] = $now->toDateString();
                $attributes['informed_at'] = $now;

                $clone = new WorkReport();
                $clone->forceFill($attributes);
                $clone->save();
                $clone->Orders()->sync($orderIds);

                foreach ($source->FlowProductions as $flow) {
                    $flowAttributes = $flow->getAttributes();
                    unset(
                        $flowAttributes['id'],
                        $flowAttributes['created_at'],
                        $flowAttributes['updated_at'],
                        $flowAttributes['work_report_id'],
                        $flowAttributes['production_id'],
                        $flowAttributes['stage'],
                        $flowAttributes['final_scope'],
                    );

                    \App\Models\WorkReportFlowProduction::updateOrCreate(
                        [
                            'work_report_id' => $clone->id,
                            'production_id' => $flow->production_id,
                            'stage' => $flow->stage,
                            'final_scope' => $scope,
                        ],
                        [
                            ...$flowAttributes,
                            'is_current' => $flow->is_current,
                            'linked_at' => $flow->linked_at,
                            'linked_by' => $flow->linked_by,
                            'source' => $flow->source,
                            'metadata' => $flow->metadata,
                        ],
                    );
                }

                $created[] = $clone->id;
            }
        });

        $this->workReport->refresh()->load(['Note', 'Company', 'User', 'Orders', 'Adsform.Files']);
        $this->informedAt = $this->formatDateTimeLocal($this->workReport->informed_at);
        $this->splitScopeSelection = [];
        $this->refreshOrders();
        $this->refreshRelatedWorkReports();
        $this->refreshWorkReportFiles();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Escopo separado com sucesso',
            'html' => 'Novo informe: #' . implode(', #', $created) . '<br>Data da separação: ' . $now->format('d/m/Y H:i'),
            'timer' => 3500,
        ]);
    }

    private function ordersByScope(WorkReport $workReport): array
    {
        $payloads = app(WorkReportFinalScopeResolver::class)->resolve(
            $workReport->Note?->type_note,
            $workReport->Orders
        );

        return collect($payloads)
            ->mapWithKeys(fn (array $payload) => [
                $payload['scope'] => collect($payload['orders'])
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    private function refreshRelatedWorkReports(): void
    {
        if (!$this->workReport?->note_id) {
            $this->relatedWorkReports = [];
            $this->relatedWorkReportOptions = [];
            return;
        }

        $reports = WorkReport::query()
            ->with(['Company', 'User'])
            ->where('note_id', $this->workReport->note_id)
            ->orderByDesc('id')
            ->get();

        $this->relatedWorkReports = $reports->all();
        $this->relatedWorkReportOptions = $reports->map(fn (WorkReport $report): array => [
            'id' => (int) $report->id,
            'label' => collect($report->finalScopeBadges())->pluck('label')->implode(' / ') ?: 'Geral',
        ])->values()->all();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['details', 'files'], true) ? $tab : 'details';

        if ($this->activeTab === 'files') {
            $this->refreshWorkReportFiles();
        }
    }

    private function refreshWorkReportFiles(): void
    {
        $reportIds = collect($this->relatedWorkReports)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();

        if ($reportIds->isEmpty() && $this->workReport?->id) {
            $reportIds = collect([(int) $this->workReport->id]);
        }

        if ($reportIds->isEmpty()) {
            $this->workReportFiles = [];
            return;
        }

        $this->fileTargetByFile = [];
        $this->workReportFiles = File::query()
            ->whereHas('WorkReports', fn ($query) => $query->whereIn('work_reports.id', $reportIds->all()))
            ->with(['WorkReports:id,note_id'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (File $file): array {
                $relatedReportIds = $file->WorkReports
                    ->where('note_id', $this->workReport?->note_id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $this->fileTargetByFile[(int) $file->id] = $relatedReportIds[0] ?? $this->workReport?->id;

                return [
                    'id' => (int) $file->id,
                    'name' => (string) ($file->original_name ?: $file->file_name ?: 'Arquivo sem nome'),
                    'type' => strtoupper((string) ($file->ext ?: pathinfo((string) $file->file_name, PATHINFO_EXTENSION) ?: 'ARQ')),
                    'size' => (int) $file->size,
                    'report_ids' => $relatedReportIds,
                ];
            })
            ->values()
            ->all();
    }

    public function associateFileToWorkReport(int $fileId, int $targetWorkReportId = 0): void
    {
        $targetWorkReportId = $targetWorkReportId ?: (int) ($this->fileTargetByFile[$fileId] ?? 0);
        $this->moveFilesToWorkReport([$fileId], $targetWorkReportId);
    }

    public function associateSelectedFiles(): void
    {
        $this->moveFilesToWorkReport($this->selectedFileIds, (int) $this->fileTargetWorkReportId);
    }

    private function moveFilesToWorkReport(array $fileIds, int $targetWorkReportId): void
    {
        $reportIds = collect($this->relatedWorkReports)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        $target = WorkReport::query()
            ->whereKey($targetWorkReportId)
            ->whereIn('id', $reportIds->all())
            ->first();

        $fileIds = collect($fileIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if (!$target || $fileIds->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione os arquivos e o informe destino',
                'timer' => 2200,
            ]);
            return;
        }

        $files = File::query()
            ->whereIn('id', $fileIds->all())
            ->whereHas('WorkReports', fn ($query) => $query->whereIn('work_reports.id', $reportIds->all()))
            ->get();

        DB::transaction(function () use ($files, $reportIds, $target): void {
            foreach ($files as $file) {
                $file->WorkReports()->detach($reportIds->all());
                $target->Files()->syncWithoutDetaching([$file->id]);
            }
        });

        $count = $files->count();
        $this->selectedFileIds = [];
        $this->fileTargetWorkReportId = $target->id;
        $this->refreshWorkReportFiles();
        $this->activeTab = 'files';

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => $count . ' arquivo(s) atualizado(s)',
            'html' => 'Associação alterada para o informe #' . $target->id . '.',
            'timer' => 2500,
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
        $this->relatedWorkReports = [];
        $this->splitScopeSelection = [];

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
        $this->workReportFiles = [];
        $this->selectedFileIds = [];
        $this->fileTargetByFile = [];
        $this->fileTargetWorkReportId = null;
        $this->activeTab = 'details';
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
