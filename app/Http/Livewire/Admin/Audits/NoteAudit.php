<?php

namespace App\Http\Livewire\Admin\Audits;

use App\Models\Audit;
use App\Models\Company;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class NoteAudit extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $note;
    public $serviceId;
    public $action;
    public $modelClass;
    public $userId;
    public $dateFrom;
    public $dateTo;
    public $perPage = 30;

    public $services = [];
    public $users = [];
    public $modelOptions = [];
    public $serviceMap = [];
    public $userMap = [];
    protected array $companyMap = [];
    protected array $noteNumberMap = [];
    protected array $productionMap = [];

    public $selectedAudit;
    public $selectedDiff = [];
    public $selectedSummary = [];

    protected $queryString = [
        'note' => ['except' => '', 'as' => 'nota'],
        'serviceId' => ['except' => '', 'as' => 'atividade'],
        'action' => ['except' => '', 'as' => 'acao'],
        'modelClass' => ['except' => '', 'as' => 'modelo'],
        'userId' => ['except' => '', 'as' => 'usuario'],
        'dateFrom' => ['except' => '', 'as' => 'inicio'],
        'dateTo' => ['except' => '', 'as' => 'fim'],
        'page' => ['except' => 1, 'as' => 'pag'],
    ];

    protected array $ignoreFields = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function mount()
    {
        $this->services = Service::orderBy('service')
            ->get(['uuid', 'service']);
        $this->serviceMap = $this->services->pluck('service', 'uuid')->toArray();

        $this->users = User::orderBy('name')
            ->get(['id', 'name']);
        $this->userMap = $this->users->pluck('name', 'id')->toArray();

        $this->modelOptions = Audit::query()
            ->whereNotNull('model_class')
            ->select('model_class')
            ->distinct()
            ->orderBy('model_class')
            ->pluck('model_class')
            ->toArray();
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'note',
            'serviceId',
            'action',
            'modelClass',
            'userId',
            'dateFrom',
            'dateTo',
        ]);
        $this->resetPage();
    }

    public function openDetails(int $auditId)
    {
        $audit = Audit::with('User')->find($auditId);

        if (!$audit) {
            return;
        }

        $before = $this->decodeJson($audit->before);
        $after = $this->decodeJson($audit->after);

        $this->selectedAudit = $audit;
        $this->selectedDiff = $this->buildDiff($before, $after, $audit);
        $this->selectedSummary = [
            'note_id' => $after['note_id'] ?? $before['note_id'] ?? null,
            'service_id' => $after['service_id'] ?? $before['service_id'] ?? null,
        ];

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'audit_note_detail',
        ]);
    }

    public function diffCount($audit): int
    {
        $before = $this->decodeJson($audit->before);
        $after = $this->decodeJson($audit->after);

        return count($this->buildDiff($before, $after));
    }

    public function render()
    {
        $audits = $this->baseQuery()->paginate($this->perPage);

        $noteIds = $audits->map(function ($audit) {
            return $this->extractPayloadValue($audit, 'note_id');
        })->filter()->unique()->values();

        $noteMap = $noteIds->isEmpty()
            ? []
            : Note::whereIn('id', $noteIds)->pluck('note', 'id')->toArray();

        return view('livewire.admin.audits.note-audit', [
            'audits' => $audits,
            'noteMap' => $noteMap,
        ]);
    }

    protected function baseQuery()
    {
        $query = Audit::query()
            ->with('User')
            ->orderByDesc('created_at');

        if ($this->note) {
            $noteIds = $this->resolveNoteIds();

            if (empty($noteIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($noteIds) {
                    foreach ($noteIds as $noteId) {
                        $q->orWhere('after->note_id', $noteId)
                            ->orWhere('before->note_id', $noteId);
                    }
                });
            }
        }

        if ($this->serviceId) {
            $query->where(function ($q) {
                $q->where('after->service_id', $this->serviceId)
                    ->orWhere('before->service_id', $this->serviceId);
            });
        }

        $query->when($this->action, function ($q, $action) {
            return $q->where('action', $action);
        });

        $query->when($this->modelClass, function ($q, $modelClass) {
            return $q->where('model_class', 'like', '%' . $modelClass . '%');
        });

        $query->when($this->userId, function ($q, $userId) {
            return $q->where('user_id', $userId);
        });

        $query->when($this->dateFrom, function ($q, $dateFrom) {
            return $q->whereDate('created_at', '>=', $dateFrom);
        });

        $query->when($this->dateTo, function ($q, $dateTo) {
            return $q->whereDate('created_at', '<=', $dateTo);
        });

        return $query;
    }

    protected function resolveNoteIds(): array
    {
        $term = trim((string) $this->note);

        if ($term === '') {
            return [];
        }

        $noteIds = Note::query()
            ->where('note', $term)
            ->orWhere('id', $term)
            ->pluck('id')
            ->all();

        return array_values(array_unique($noteIds));
    }

    protected function decodeJson(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function extractPayloadValue(Audit $audit, string $key)
    {
        $after = $this->decodeJson($audit->after);

        if (array_key_exists($key, $after)) {
            return $after[$key];
        }

        $before = $this->decodeJson($audit->before);

        return $before[$key] ?? null;
    }

    protected function buildDiff(array $before, array $after, ?Audit $audit = null): array
    {
        $before = $this->stripIgnoredFields($before);
        $after = $this->stripIgnoredFields($after);

        $production = $this->resolveProduction($before, $after, $audit);

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changes = [];

        foreach ($keys as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeValue !== $afterValue) {
                $changes[] = [
                    'field' => $key,
                    'before' => $this->formatValueForField($key, $beforeValue, $audit, $production),
                    'after' => $this->formatValueForField($key, $afterValue, $audit, $production),
                ];
            }
        }

        return $changes;
    }

    protected function stripIgnoredFields(array $data): array
    {
        foreach ($this->ignoreFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    protected function formatValueForField(string $field, $value, ?Audit $audit = null, ?Production $production = null): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        $dateFormatted = $this->formatDateValue($field, $value);
        if ($dateFormatted !== null) {
            return $dateFormatted;
        }

        if (strtolower($field) === 'service_id') {
            $label = $this->getServiceName($value);
            if ($label) {
                return sprintf('%s (%s)', $label, $value);
            }
        }

        if (is_numeric($value)) {
            $label = $this->resolveLabelForField($field, $value, $audit, $production);
            if ($label) {
                return sprintf('%s (%s)', $label, $value);
            }
        }

        return (string) $value;
    }

    protected function resolveLabelForField(string $field, $value, ?Audit $audit = null, ?Production $production = null): ?string
    {
        $field = strtolower($field);

        $userFields = [
            'user_id',
            'dispatch_by',
            'dispatcher_by',
            'att_by',
            'engineer_id',
            'responsible_id',
            'created_by',
            'updated_by',
            'user',
        ];

        if (in_array($field, $userFields, true)) {
            return $this->getUserName($value);
        }

        if ($field === 'company_id') {
            return $this->getCompanyName($value);
        }

        if ($field === 'service_id') {
            return $this->getServiceName($value);
        }

        if ($field === 'note_id') {
            return $this->getNoteNumber($value);
        }

        if ($audit && $audit->model_class === 'App\\Models\\Production') {
            $relationLabel = $this->getProductionRelationLabel($field, $value, $production);
            if ($relationLabel) {
                return $relationLabel;
            }
        }

        return null;
    }

    protected function getProductionRelationLabel(string $field, $value, ?Production $production): ?string
    {
        if (!$production) {
            return null;
        }

        if ($field === 'user_id' && $production->User && (int) $production->User->id === (int) $value) {
            return $production->User->name;
        }

        if ($field === 'dispatch_by' && $production->Dispatcher && (int) $production->Dispatcher->id === (int) $value) {
            return $production->Dispatcher->name;
        }

        if ($field === 'att_by' && $production->Att && (int) $production->Att->id === (int) $value) {
            return $production->Att->name;
        }

        if ($field === 'company_id' && $production->Company && (int) $production->Company->id === (int) $value) {
            return $production->Company->name;
        }

        if ($field === 'service_id' && $production->Service && $production->Service->uuid === (string) $value) {
            return $production->Service->service;
        }

        if ($field === 'note_id' && $production->Note && (int) $production->Note->id === (int) $value) {
            return $production->Note->note;
        }

        return null;
    }

    protected function resolveProduction(array $before, array $after, ?Audit $audit): ?Production
    {
        if (!$audit || $audit->model_class !== 'App\\Models\\Production') {
            return null;
        }

        $productionId = $after['id'] ?? $before['id'] ?? null;
        if (!$productionId) {
            return null;
        }

        if (isset($this->productionMap[$productionId])) {
            return $this->productionMap[$productionId];
        }

        $production = Production::with(['User', 'Dispatcher', 'Att', 'Company', 'Service', 'Note'])
            ->find($productionId);

        if ($production) {
            $this->productionMap[$productionId] = $production;
        }

        return $production;
    }

    protected function formatDateValue(string $field, $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $field = strtolower($field);
        $isDateField = str_ends_with($field, '_at')
            || str_starts_with($field, 'dt_')
            || in_array($field, ['dhstats'], true);

        if (!$isDateField) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y H:i:s');
        } catch (\Throwable $th) {
            return null;
        }
    }

    protected function getUserName($userId): ?string
    {
        $userId = (int) $userId;

        if (isset($this->userMap[$userId])) {
            return $this->userMap[$userId];
        }

        $user = User::withTrashed()->find($userId);
        if ($user) {
            $this->userMap[$userId] = $user->name;
            return $user->name;
        }

        return null;
    }

    protected function getCompanyName($companyId): ?string
    {
        $companyId = (int) $companyId;

        if (isset($this->companyMap[$companyId])) {
            return $this->companyMap[$companyId];
        }

        $company = Company::find($companyId);
        if ($company) {
            $this->companyMap[$companyId] = $company->name;
            return $company->name;
        }

        return null;
    }

    protected function getServiceName($serviceId): ?string
    {
        if (isset($this->serviceMap[$serviceId])) {
            return $this->serviceMap[$serviceId];
        }

        $service = Service::where('uuid', $serviceId)->first();
        if ($service) {
            $this->serviceMap[$serviceId] = $service->service;
            return $service->service;
        }

        return null;
    }

    protected function getNoteNumber($noteId): ?string
    {
        $noteId = (int) $noteId;

        if (isset($this->noteNumberMap[$noteId])) {
            return $this->noteNumberMap[$noteId];
        }

        $note = Note::find($noteId);
        if ($note) {
            $this->noteNumberMap[$noteId] = $note->note;
            return $note->note;
        }

        return null;
    }
}
