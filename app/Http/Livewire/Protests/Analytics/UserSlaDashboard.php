<?php

namespace App\Http\Livewire\Protests\Analytics;

use App\Enum\ProtestJobStatus;
use App\Enum\ProtestType;
use App\Jobs\Protests\ExportDispatcherMeasuresJob;
use App\Jobs\Protests\ExportProtestJobsJob;
use App\Models\MedProtest;
use App\Models\Protest;
use App\Models\ProtestJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class UserSlaDashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $dt_in;
    public $dt_out;
    public $advanceFilter = 'all'; // all | advance | normal
    public $userId = null;
    public array $protestTypes = [];
    public string $complaintSearch = '';
    public array $complaintNoteTypes = [];
    public array $complaintClassifications = [];
    public array $complaintCities = [];
    public string $medaDispatchFilter = 'all'; // all | with_job | without_job
    public string $openDispatchBtzeroFilter = 'all'; // all | without_btzero | only_btzero
    public string $generalMeasuresOpenFilter = 'all'; // all | open | not_open
    public string $generalMeasuresBtzeroFilter = 'all'; // all | with_btzero | without_btzero
    public string $complaintsBtzeroFilter = 'without_btzero'; // all | without_btzero | only_btzero
    public ?string $medaHistogramBucket = null; // YYYY-MM
    public ?string $medaHistogramStackFilter = null; // overdue | due_soon | within
    public string $medaHistogramSource = 'desired'; // desired | sla
    public ?int $medaHistogramYear = null;
    public string $medaHistogramBtzeroFilter = 'all'; // all | without_btzero | only_btzero
    public ?string $medaDoneOpenCreatorId = null;
    public ?string $medaOpenNoteTypeFilter = null; // NA | OU | PR
    public ?string $medaDueWindowFilter = null; // overdue | due_soon | today | tomorrow | in_3_days

    public $usersOptions = [];
    public array $protestTypeOptions = [];
    public array $complaintNoteTypeOptions = [];
    public array $complaintClassificationOptions = [];
    public array $complaintCityOptions = [];

    protected $queryString = [
        'dt_in'         => ['except' => ''],
        'dt_out'        => ['except' => ''],
        'advanceFilter' => ['except' => 'all', 'as' => 'adv'],
        'userId'        => ['except' => null, 'as' => 'user'],
        'complaintSearch' => ['except' => '', 'as' => 'rq'],
        'complaintNoteTypes' => ['except' => [], 'as' => 'rnt'],
        'complaintClassifications' => ['except' => [], 'as' => 'rcl'],
        'complaintCities' => ['except' => [], 'as' => 'rct'],
        'medaDispatchFilter' => ['except' => 'all', 'as' => 'mdf'],
        'openDispatchBtzeroFilter' => ['except' => 'all', 'as' => 'odbf'],
        'generalMeasuresOpenFilter' => ['except' => 'all', 'as' => 'gmof'],
        'generalMeasuresBtzeroFilter' => ['except' => 'all', 'as' => 'gmbf'],
        'complaintsBtzeroFilter' => ['except' => 'without_btzero', 'as' => 'cbf'],
        'medaHistogramBucket' => ['except' => null, 'as' => 'mhb'],
        'medaHistogramStackFilter' => ['except' => null, 'as' => 'mhsf'],
        'medaHistogramSource' => ['except' => 'desired', 'as' => 'mhsrc'],
        'medaHistogramYear' => ['except' => null, 'as' => 'mhyear'],
        'medaHistogramBtzeroFilter' => ['except' => 'all', 'as' => 'mhbz'],
        'medaDoneOpenCreatorId' => ['except' => null, 'as' => 'mdc'],
        'medaOpenNoteTypeFilter' => ['except' => null, 'as' => 'mont'],
        'medaDueWindowFilter' => ['except' => null, 'as' => 'mdwf'],
    ];

    public function mount()
    {
        if (blank($this->dt_in)) {
            $this->dt_in = now()->startOfMonth()->toDateString();
        }

        if (blank($this->dt_out)) {
            $this->dt_out = now()->toDateString();
        }

        // Usuários que aparecem em created_by ou owner_id em qualquer Job
        $this->usersOptions = User::whereIn('id', function ($q) {
            $q->select('created_by')->from('protest_jobs')->whereNotNull('created_by');
        })
            ->orWhereIn('id', function ($q) {
                $q->select('owner_id')->from('protest_jobs')->whereNotNull('owner_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->protestTypeOptions = collect(ProtestType::cases())->map(function (ProtestType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
            ];
        })->values()->all();

        $this->protestTypes = collect((array) $this->protestTypes)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values()
            ->all();
        $this->medaHistogramYear = $this->medaHistogramYear ?: (int) now()->year;
        $this->complaintNoteTypes = collect((array) $this->complaintNoteTypes)->filter()->values()->all();
        $this->complaintClassifications = collect((array) $this->complaintClassifications)->filter()->values()->all();
        $this->complaintCities = collect((array) $this->complaintCities)->filter()->values()->all();
        $this->loadComplaintFilterOptions();
    }

    public function updated($propertyName)
    {
        $paginationSensitiveProps = [
            'dt_in',
            'dt_out',
            'advanceFilter',
            'userId',
            'protestTypes',
            'complaintSearch',
            'complaintNoteTypes',
            'complaintClassifications',
            'complaintCities',
            'medaDispatchFilter',
            'openDispatchBtzeroFilter',
            'generalMeasuresOpenFilter',
            'generalMeasuresBtzeroFilter',
            'complaintsBtzeroFilter',
            'medaHistogramBucket',
            'medaHistogramStackFilter',
            'medaHistogramSource',
            'medaHistogramYear',
            'medaHistogramBtzeroFilter',
            'medaDoneOpenCreatorId',
            'medaOpenNoteTypeFilter',
            'medaDueWindowFilter',
        ];

        $isProtestTypesNested = str_starts_with($propertyName, 'protestTypes.');
        $isComplaintNoteTypesNested = str_starts_with($propertyName, 'complaintNoteTypes.');
        $isComplaintClassificationsNested = str_starts_with($propertyName, 'complaintClassifications.');
        $isComplaintCitiesNested = str_starts_with($propertyName, 'complaintCities.');

        if (
            $isProtestTypesNested
            || $isComplaintNoteTypesNested
            || $isComplaintClassificationsNested
            || $isComplaintCitiesNested
            || in_array($propertyName, $paginationSensitiveProps, true)
        ) {
            $this->resetDueMeasuresPagination();
        }
    }

    protected function resetDueMeasuresPagination(): void
    {
        $this->resetPage('due_today_page');
        $this->resetPage('overdue_page');
        $this->resetPage('dispatcher_measures_page');
        $this->resetPage('meda_open_dispatch_page');
        $this->resetPage('general_protests_page');
    }

    public function setMedaHistogramBucket(?string $bucket = null): void
    {
        if (!$bucket || !preg_match('/^\d{4}\-\d{2}$/', $bucket)) {
            return;
        }

        $this->medaHistogramBucket = $bucket;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function setMedaHistogramStackSelection(?string $bucket = null, ?string $segment = null): void
    {
        $normalizedSegment = $this->normalizeMedaHistogramSegment($segment);
        if (!$normalizedSegment) {
            return;
        }

        $previousBucket = $this->medaHistogramBucket;
        $validBucket = $bucket && preg_match('/^\d{4}\-\d{2}$/', $bucket);
        if ($validBucket) {
            $this->medaHistogramBucket = $bucket;
        }

        if (
            $this->medaHistogramStackFilter === $normalizedSegment
            && (!$validBucket || $previousBucket === $bucket)
        ) {
            $this->medaHistogramStackFilter = null;
            $this->resetPage('meda_open_dispatch_page');
            return;
        }

        $this->medaHistogramStackFilter = $normalizedSegment;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function setMedaDoneOpenCreator(?string $creatorId = null): void
    {
        $creatorId = trim((string) $creatorId);
        if ($creatorId === '') {
            $this->medaDoneOpenCreatorId = null;
            $this->resetPage('meda_open_dispatch_page');
            return;
        }

        $this->medaDoneOpenCreatorId = $this->medaDoneOpenCreatorId === $creatorId ? null : $creatorId;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function setMedaOpenNoteTypeFilter(?string $noteType = null): void
    {
        $allowed = ['NA', 'OU', 'PR'];
        $normalized = mb_strtoupper(trim((string) $noteType));

        if ($normalized === '' || !in_array($normalized, $allowed, true)) {
            $this->medaOpenNoteTypeFilter = null;
            $this->resetPage('meda_open_dispatch_page');
            return;
        }

        $this->medaOpenNoteTypeFilter = $this->medaOpenNoteTypeFilter === $normalized ? null : $normalized;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function setMedaDueWindowFilter(?string $filter = null): void
    {
        $allowed = ['overdue', 'due_soon', 'today', 'tomorrow', 'in_3_days'];
        if (!$filter || !in_array($filter, $allowed, true)) {
            $this->medaDueWindowFilter = null;
            $this->resetPage('meda_open_dispatch_page');
            return;
        }

        $this->medaDueWindowFilter = $this->medaDueWindowFilter === $filter ? null : $filter;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function clearMedaQuickFilters(): void
    {
        $this->medaOpenNoteTypeFilter = null;
        $this->medaDueWindowFilter = null;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function toggleMedaHistogramBucket(?string $bucket = null): void
    {
        if (!$bucket || !preg_match('/^\d{4}\-\d{2}$/', $bucket)) {
            return;
        }

        $this->medaHistogramBucket = $this->medaHistogramBucket === $bucket ? null : $bucket;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function updatedMedaHistogramSource($value): void
    {
        if (!in_array($value, ['desired', 'sla'], true)) {
            $this->medaHistogramSource = 'desired';
        }

        $this->medaHistogramBucket = null;
        $this->medaHistogramStackFilter = null;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function updatedMedaHistogramYear(): void
    {
        $this->medaHistogramBucket = null;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function updatedMedaHistogramBtzeroFilter($value): void
    {
        if (!in_array($value, ['all', 'without_btzero', 'only_btzero'], true)) {
            $this->medaHistogramBtzeroFilter = 'all';
        }
        // Sincroniza automaticamente com a lista consolidada.
        // O usuário ainda pode ajustar manualmente depois pelo filtro da própria lista.
        $this->openDispatchBtzeroFilter = $this->medaHistogramBtzeroFilter;
        $this->medaHistogramBucket = null;
        $this->medaHistogramStackFilter = null;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function updatedMedaDispatchFilter($value): void
    {
        if (!in_array($value, ['all', 'with_job', 'without_job'], true)) {
            $this->medaDispatchFilter = 'all';
        }
        $this->medaHistogramBucket = null;
        $this->medaHistogramStackFilter = null;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function clearMedaHistogramFilter(): void
    {
        $this->medaHistogramBucket = null;
        $this->medaHistogramStackFilter = null;
        $this->resetPage('meda_open_dispatch_page');
    }

    public function updatedGeneralMeasuresOpenFilter($value): void
    {
        if (!in_array($value, ['all', 'open', 'not_open'], true)) {
            $this->generalMeasuresOpenFilter = 'all';
        }
        $this->resetPage('general_protests_page');
    }

    public function updatedGeneralMeasuresBtzeroFilter($value): void
    {
        if (!in_array($value, ['all', 'with_btzero', 'without_btzero'], true)) {
            $this->generalMeasuresBtzeroFilter = 'all';
        }
        $this->resetPage('general_protests_page');
    }

    public function updatedComplaintsBtzeroFilter($value): void
    {
        if (!in_array($value, ['all', 'without_btzero', 'only_btzero'], true)) {
            $this->complaintsBtzeroFilter = 'without_btzero';
        }
    }

    /**
     * Query base para filtrar os ProtestJobs do período / filtros.
     */
    protected function baseJobsQuery()
    {
        return ProtestJob::query()
            ->when($this->advanceFilter === 'advance', fn ($q) => $q->where('is_advance', true))
            ->when($this->advanceFilter === 'normal', fn ($q) => $q->where(function ($sub) {
                $sub->where('is_advance', false)->orWhereNull('is_advance');
            }))
            ->when($this->userId, function ($q) {
                $id = $this->userId;
                $q->where(function ($sub) use ($id) {
                    $sub->where('created_by', $id)
                        ->orWhere('owner_id', $id)
                        ->orWhere('closed_by', $id);
                });
            })
            ->when($types = $this->getSelectedProtestTypes(), function ($q) use ($types) {
                $q->whereHas('medProtest', function ($sub) use ($types) {
                    $sub->whereIn('protest_type', $types);
                });
            })
            ->whereHas('protest', function ($q) {
                $this->applyComplaintFiltersToProtestBuilder($q);
            });
    }

    protected function jobsBaseQuery(Carbon $start, Carbon $end, string $dateColumn = 'sent_at')
    {
        if (!in_array($dateColumn, ['sent_at', 'finished_at'], true)) {
            $dateColumn = 'sent_at';
        }

        return $this->baseJobsQuery()
            ->whereBetween($dateColumn, [$start, $end]);
    }

    protected function getSelectedProtestTypes(): array
    {
        return collect($this->protestTypes)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    protected function applyMedProtestTypeFilter($query)
    {
        $types = $this->getSelectedProtestTypes();
        return $query->when(!empty($types), fn ($q) => $q->whereIn('protest_type', $types));
    }

    protected function applyProtestTypeFilter($query)
    {
        $types = $this->getSelectedProtestTypes();
        return $query->when(!empty($types), function ($q) use ($types) {
            $q->whereHas('medProtests', function ($sub) use ($types) {
                $sub->whereIn('protest_type', $types);
            });
        });
    }

    protected function loadComplaintFilterOptions(): void
    {
        $this->complaintNoteTypeOptions = Protest::query()
            ->select('tipoNota')
            ->whereNotNull('tipoNota')
            ->distinct()
            ->orderBy('tipoNota')
            ->pluck('tipoNota')
            ->filter()
            ->values()
            ->toArray();

        $this->complaintClassificationOptions = Protest::query()
            ->select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->filter()
            ->values()
            ->toArray();

        $this->complaintCityOptions = Protest::query()
            ->select('cidade')
            ->whereNotNull('cidade')
            ->distinct()
            ->orderBy('cidade')
            ->pluck('cidade')
            ->filter()
            ->values()
            ->toArray();
    }

    protected function applyComplaintFiltersToProtestBuilder($query): void
    {
        $search = trim((string) $this->complaintSearch);
        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('nota', 'like', '%' . $search . '%')
                    ->orWhere('codecodf', 'like', '%' . $search . '%')
                    ->orWhere('txtGrpCodificacao', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('cidade', 'like', '%' . $search . '%')
                    ->orWhere('tipoNota', 'like', '%' . $search . '%');
            });
        }

        if (!empty($this->complaintNoteTypes)) {
            $query->whereIn('tipoNota', $this->complaintNoteTypes);
        }

        if (!empty($this->complaintClassifications)) {
            $query->whereIn('type', $this->complaintClassifications);
        }

        if (!empty($this->complaintCities)) {
            $query->whereIn('cidade', $this->complaintCities);
        }
    }

    protected function applyComplaintFiltersToProtestQuery($query)
    {
        $this->applyComplaintFiltersToProtestBuilder($query);
        return $query;
    }

    protected function applyComplaintFiltersToMedProtestQuery($query)
    {
        return $query->whereHas('protest', function ($protestQuery) {
            $this->applyComplaintFiltersToProtestBuilder($protestQuery);
        });
    }

    protected function applyComplaintsCipUniverseFilter($query)
    {
        $query->where(function ($scope) {
            // Universo principal: CIP por texto e por enum na medida
            $scope->where(function ($cip) {
                $cip->where('type', 'like', 'CIP%')
                    ->whereHas('medProtests', function ($sub) {
                        $sub->where('protest_type', ProtestType::CIP->value);
                    });
            })
            // Inclusão explícita: sem informação em ambos (protest.type e med_protests.protest_type)
            ->orWhere(function ($unknown) {
                $unknown
                    ->where(function ($type) {
                        $type->whereNull('type')
                            ->orWhereRaw('TRIM(type) = ""');
                    })
                    ->whereDoesntHave('medProtests', function ($sub) {
                        $sub->whereNotNull('protest_type');
                    });
            });
        });

        // Expurgo fixo de Construção
        $query->whereDoesntHave('medProtests', function ($sub) {
            $sub->where('protest_type', ProtestType::CONSTRUCTION->value);
        });

        if ($this->complaintsBtzeroFilter === 'without_btzero') {
            $query->whereDoesntHave('medProtests', function ($sub) {
                $sub->identifiedAsBtzero();
            });
        } elseif ($this->complaintsBtzeroFilter === 'only_btzero') {
            $query->whereHas('medProtests', function ($sub) {
                $sub->identifiedAsBtzero();
            });
        }

        return $query;
    }

    protected function resolveProtestTypeLabel($value): string
    {
        if ($value instanceof ProtestType) {
            return $value->label();
        }

        if (is_numeric($value)) {
            $enum = ProtestType::tryFrom((int) $value);
            if ($enum) {
                return $enum->label();
            }
        }

        return 'Sem classificacao';
    }

    protected function formatFirstAndLastName(?string $fullName, string $fallback = 'Sem responsável'): string
    {
        $name = trim((string) $fullName);
        if ($name === '') {
            return $fallback;
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => trim((string) $part) !== ''));
        $count = count($parts);

        if ($count === 0) {
            return $fallback;
        }

        if ($count === 1) {
            return (string) $parts[0];
        }

        return (string) ($parts[0] . ' ' . $parts[$count - 1]);
    }

    protected function getDateRange(): array
    {
        $start = $this->dt_in ? Carbon::parse($this->dt_in)->startOfDay() : now()->startOfMonth();
        $end   = $this->dt_out ? Carbon::parse($this->dt_out)->endOfDay() : now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function secondsToHuman(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 min';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    /**
     * Métricas gerais do período (cards de resumo).
     */
    protected function buildSummary(Carbon $start, Carbon $end): array
    {
        $base = $this->jobsBaseQuery($start, $end);

        $totalJobs = (clone $base)->count();
        $finishedJobs = (clone $base)->whereNotNull('finished_at')->count();
        $slaBreached = (clone $base)->whereNotNull('sla_breached_at')->count();
        $onTimeJobs  = max(0, $finishedJobs - $slaBreached);

        $slaRate = $finishedJobs > 0
            ? round(($onTimeJobs / $finishedJobs) * 100, 1)
            : 0;

        // Média global de reação (Despachantes)
        $dispatcherGlobal = (clone $base)
            ->join('med_protests', 'med_protests.id', '=', 'protest_jobs.med_protest_id')
            ->whereNotNull('protest_jobs.created_by')
            ->whereNotNull('med_protests.dtCriacaoMedida')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, med_protests.dtCriacaoMedida, protest_jobs.sent_at)) as avg_reaction_seconds')
            ->first();

        $avgReactionSeconds = (int)($dispatcherGlobal->avg_reaction_seconds ?? 0);

        // Tempo até aceite pelo responsável (sent_at -> accepted_at)
        $userReaction = (clone $base)
            ->whereNotNull('protest_jobs.accepted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.accepted_at)) as avg_accept_seconds')
            ->first();

        $avgUserReactionSeconds = (int)($userReaction->avg_accept_seconds ?? 0);

        // Média global de execução (Responsáveis) sent_at -> finished_at
        $ownerGlobal = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->whereNotNull('protest_jobs.sent_at')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.finished_at)) as avg_exec_seconds')
            ->first();

        $avgExecSeconds = (int)($ownerGlobal->avg_exec_seconds ?? 0);

        // Encerramento pelo próprio responsável
        $closureAgg = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('
                SUM(CASE WHEN protest_jobs.closed_by = protest_jobs.owner_id THEN 1 ELSE 0 END) as self_closed,
                COUNT(*) as total_closed
            ')
            ->first();

        $selfClosed  = (int)($closureAgg->self_closed ?? 0);
        $totalClosed = (int)($closureAgg->total_closed ?? 0);

        $selfClosureRate = $totalClosed > 0
            ? round(($selfClosed / $totalClosed) * 100, 1)
            : 0;

        return [
            'period_label'             => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
            'total_jobs'               => $totalJobs,
            'finished_jobs'            => $finishedJobs,
            'sla_rate'                 => $slaRate,
            'avg_reaction_sec'         => $avgReactionSeconds,
            'avg_reaction_human'       => $this->secondsToHuman($avgReactionSeconds),
            'avg_user_reaction_sec'    => $avgUserReactionSeconds,
            'avg_user_reaction_human'  => $this->secondsToHuman($avgUserReactionSeconds),
            'avg_exec_sec'             => $avgExecSeconds,
            'avg_exec_human'           => $this->secondsToHuman($avgExecSeconds),
            'self_closure_rate'        => $selfClosureRate,
            'self_closed'              => $selfClosed,
            'total_closed'             => $totalClosed,
        ];
    }

    /**
     * Estatísticas por despachante (created_by).
     */
    protected function buildDispatcherStats(Carbon $start, Carbon $end)
    {
        $base = $this->jobsBaseQuery($start, $end);

        $rows = (clone $base)
            ->join('med_protests', 'med_protests.id', '=', 'protest_jobs.med_protest_id')
            ->whereNotNull('protest_jobs.created_by')
            ->selectRaw('
                protest_jobs.created_by as user_id,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN protest_jobs.is_advance = 1 THEN 1 ELSE 0 END) as total_advance,
                AVG(TIMESTAMPDIFF(SECOND, med_protests.created_at, protest_jobs.sent_at)) as avg_reaction_seconds
            ')
            ->groupBy('protest_jobs.created_by')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $totalJobs    = (int)$row->total_jobs;
            $totalAdvance = (int)($row->total_advance ?? 0);
            $avgSec       = (int)($row->avg_reaction_seconds ?? 0);

            return [
                'user_id'            => $row->user_id,
                'user_name'          => optional($users->get($row->user_id))->name ?? 'N/A',
                'total_jobs'         => $totalJobs,
                'total_advance'      => $totalAdvance,
                'advance_ratio'      => $totalJobs > 0 ? round(($totalAdvance / $totalJobs) * 100, 1) : 0,
                'avg_reaction_sec'   => $avgSec,
                'avg_reaction_human' => $this->secondsToHuman($avgSec),
            ];
        })->sortByDesc('total_jobs')->values();
    }

    /**
     * Estatísticas por responsável (owner_id).
     */
    protected function buildOwnerStats(Carbon $start, Carbon $end)
    {
        $base = $this->jobsBaseQuery($start, $end);

        $rows = (clone $base)
            ->whereNotNull('protest_jobs.owner_id')
            ->selectRaw('
                protest_jobs.owner_id as user_id,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN protest_jobs.is_advance = 1 THEN 1 ELSE 0 END) as total_advance,
                SUM(CASE WHEN protest_jobs.finished_at IS NOT NULL THEN 1 ELSE 0 END) as finished_jobs,
                SUM(
                    CASE
                        WHEN protest_jobs.finished_at IS NULL
                             OR protest_jobs.status != ?
                        THEN 1 ELSE 0
                    END
                ) as open_jobs,
                SUM(
                    CASE
                        WHEN protest_jobs.sla_breached_at IS NULL
                             AND protest_jobs.sla_due_at IS NOT NULL
                             AND protest_jobs.finished_at IS NOT NULL
                             AND protest_jobs.finished_at <= protest_jobs.sla_due_at
                        THEN 1 ELSE 0
                    END
                ) as sla_on_time,
                SUM(
                    CASE
                        WHEN protest_jobs.closed_by = protest_jobs.owner_id
                             AND protest_jobs.finished_at IS NOT NULL
                        THEN 1 ELSE 0
                    END
                ) as self_closed,
                AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.finished_at)) as avg_total_seconds,
                AVG(TIMESTAMPDIFF(SECOND, protest_jobs.sent_at, protest_jobs.finished_at)) as avg_exec_seconds
            ', [ProtestJobStatus::DONE->value])
            ->groupBy('protest_jobs.owner_id')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $totalJobs     = (int)$row->total_jobs;
            $totalAdvance  = (int)($row->total_advance ?? 0);
            $finishedJobs  = (int)($row->finished_jobs ?? 0);
            $openJobs      = (int)($row->open_jobs ?? 0);
            $slaOnTime     = (int)($row->sla_on_time ?? 0);
            $selfClosed    = (int)($row->self_closed ?? 0);
            $avgTotalSec   = (int)($row->avg_total_seconds ?? 0);
            $avgExecSec    = (int)($row->avg_exec_seconds ?? 0);

            return [
                'user_id'             => $row->user_id,
                'user_name'           => optional($users->get($row->user_id))->name ?? 'N/A',
                'total_jobs'          => $totalJobs,
                'total_advance'       => $totalAdvance,
                'advance_ratio'       => $totalJobs > 0 ? round(($totalAdvance / $totalJobs) * 100, 1) : 0,
                'finished_jobs'       => $finishedJobs,
                'open_jobs'           => $openJobs,
                'sla_on_time'         => $slaOnTime,
                'sla_rate'            => $finishedJobs > 0 ? round(($slaOnTime / $finishedJobs) * 100, 1) : 0,
                'self_closed'         => $selfClosed,
                'self_closure_rate'   => $finishedJobs > 0 ? round(($selfClosed / $finishedJobs) * 100, 1) : 0,
                'avg_total_seconds'   => $avgTotalSec,
                'avg_total_human'     => $this->secondsToHuman($avgTotalSec),
                'avg_exec_seconds'    => $avgExecSec,
                'avg_exec_human'      => $this->secondsToHuman($avgExecSec),
            ];
        })->sortByDesc('total_jobs')->values();
    }

    protected function buildProductivityPanel(Carbon $start, Carbon $end): array
    {
        $daysRange = max($start->diffInDays($end) + 1, 1);

        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $currentDispatchBase = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'sent_at')
            ->whereNotNull('protest_jobs.sent_at');
        $totalDispatched = (clone $currentDispatchBase)->count();

        $finishedBase = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'finished_at')
            ->whereNotNull('protest_jobs.finished_at');

        $finishedMeta = (clone $finishedBase)
            ->whereBetween('protest_jobs.sent_at', [$rangeStart, $rangeEnd])
            ->count();

        $finishedPassive = (clone $finishedBase)
            ->where('protest_jobs.sent_at', '<', $rangeStart)
            ->count();

        $passiveOpen = $this->baseJobsQuery()
            ->whereNotNull('protest_jobs.sent_at')
            ->where('protest_jobs.sent_at', '<', $rangeStart)
            ->where(function ($q) {
                $q->whereNull('protest_jobs.finished_at')
                    ->orWhere('protest_jobs.status', '!=', ProtestJobStatus::DONE->value);
            })
            ->count();

        $finishedTotal = $finishedMeta + $finishedPassive;

        return [
            'total_dispatched'     => $totalDispatched,
            'finished_meta'        => $finishedMeta,
            'finished_passive'     => $finishedPassive,
            'finished_total'       => $finishedTotal,
            'passivo_aberto'       => $passiveOpen,
            'avg_daily_dispatch'   => round($totalDispatched / $daysRange, 1),
            'avg_daily_finish'     => round($finishedTotal / $daysRange, 1),
        ];
    }

    protected function buildBacklogPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $periodBase = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd]);

        $totalPeriod = (clone $periodBase)->count();
        $withJobPeriod = (clone $periodBase)->whereHas('ProtestJobs')->count();
        $withoutJobPeriod = max(0, $totalPeriod - $withJobPeriod);

        $openBase = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->where('statusSist', 'MEDA');

        $currentOpen = (clone $openBase)->count();
        $currentOpenWithoutJob = (clone $openBase)
            ->whereDoesntHave('ProtestJobs')
            ->count();

        $startMonth = $rangeStart->copy()->startOfMonth();
        $previousMonthStart = $startMonth->copy()->subMonth();
        $previousMonthEnd = $startMonth->copy()->subDay();

        $passiveOpen = (clone $openBase)
            ->whereBetween('dtCriacaoMedida', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $olderThanFive = (clone $openBase)
            ->whereDoesntHave('ProtestJobs')
            ->whereDate('dtCriacaoMedida', '<=', now()->subDays(5)->startOfDay())
            ->count();

        $expiredOpen = (clone $openBase)
            ->whereNotNull('dtFimMedidaDesej')
            ->whereDate('dtFimMedidaDesej', '<', now()->startOfDay())
            ->count();

        return [
            'period_total'       => $totalPeriod,
            'period_with_job'    => $withJobPeriod,
            'period_without_job' => $withoutJobPeriod,
            'current_open'       => $currentOpen,
            'current_open_without_job' => $currentOpenWithoutJob,
            'passive_open'       => $passiveOpen,
            'passive_month_label'=> $previousMonthStart->format('m/Y'),
            'older_than_5'       => $olderThanFive,
            'expired_open'       => $expiredOpen,
        ];
    }

    protected function buildSlaPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();
        $now        = now();

        $periodMedBase = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd]);

        $medCreated     = (clone $periodMedBase)->count();
        $medStatusOpen  = (clone $periodMedBase)->where('statusSist', 'MEDA')->count();
        $medStatusClose = (clone $periodMedBase)->where('statusSist', 'MEDE')->count();

        $concludedBase = (clone $periodMedBase)->whereNotNull('dtFimMedida');
        $concludedTotal = (clone $concludedBase)->count();
        $concludedOnTime = (clone $concludedBase)
            ->whereNotNull('dtFimMedidaDesej')
            ->whereColumn('dtFimMedida', '<=', 'dtFimMedidaDesej')
            ->count();
        $concludedLate = max(0, $concludedTotal - $concludedOnTime);

        $jobsPeriod = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'sent_at')
            ->whereNotNull('protest_jobs.sla_due_at');

        $jobSlaTotal = (clone $jobsPeriod)->count();
        $jobSlaLate = (clone $jobsPeriod)
            ->where(function ($q) use ($now) {
                $q->whereNotNull('protest_jobs.sla_breached_at')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('protest_jobs.finished_at')
                            ->whereColumn('protest_jobs.finished_at', '>', 'protest_jobs.sla_due_at');
                    })
                    ->orWhere(function ($sub) use ($now) {
                        $sub->whereNull('protest_jobs.finished_at')
                            ->where('protest_jobs.sla_due_at', '<', $now);
                    });
            })
            ->count();
        $jobSlaOnTime = max(0, $jobSlaTotal - $jobSlaLate);

        $measureSlaTotal = $concludedTotal;
        $measureSlaLate  = $concludedLate;
        $measureSlaOnTime = max(0, $measureSlaTotal - $measureSlaLate);

        $volumetryRaw = (clone $periodMedBase)
            ->selectRaw('DATE(dtCriacaoMedida) as date,
                SUM(CASE WHEN statusSist = "MEDA" THEN 1 ELSE 0 END) as opened_status,
                SUM(CASE WHEN statusSist = "MEDE" THEN 1 ELSE 0 END) as closed_status
            ')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $volLabels    = [];
        $volOpenSeries  = [];
        $volClosedSeries = [];

        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->toDateString();
            $volLabels[] = $cursor->format('d/m');
            $volOpenSeries[]   = (int)($volumetryRaw[$key]->opened_status ?? 0);
            $volClosedSeries[] = (int)($volumetryRaw[$key]->closed_status ?? 0);
            $cursor->addDay();
        }

        $volumetryChart = [
            'type' => 'bar',
            'data' => [
                'labels'   => $volLabels,
                'datasets' => [
                    [
                        'label'           => 'MEDA (abertas)',
                        'data'            => $volOpenSeries,
                        'backgroundColor' => 'rgba(59,130,246,0.5)',
                        'borderColor'     => '#2563eb',
                        'borderWidth'     => 1,
                        'stack'           => 'status',
                    ],
                    [
                        'label'           => 'MEDE (encerradas)',
                        'data'            => $volClosedSeries,
                        'backgroundColor' => 'rgba(16,185,129,0.5)',
                        'borderColor'     => '#10b981',
                        'borderWidth'     => 1,
                        'stack'           => 'status',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Volumetria MEDA x MEDE (criação diária)',
                    ],
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => [
                        'stacked'     => true,
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];

        $slaChart = [
            'type' => 'bar',
            'data' => [
                'labels'   => ['SLA Solicitado', 'SLA Medida'],
                'datasets' => [
                    [
                        'label'           => 'Cumprido',
                        'backgroundColor' => 'rgba(16,185,129,0.7)',
                        'borderColor'     => '#047857',
                        'borderWidth'     => 1,
                        'data'            => [$jobSlaOnTime, $measureSlaOnTime],
                        'stack'           => 'sla',
                    ],
                    [
                        'label'           => 'Vencido',
                        'backgroundColor' => 'rgba(239,68,68,0.7)',
                        'borderColor'     => '#b91c1c',
                        'borderWidth'     => 1,
                        'data'            => [$jobSlaLate, $measureSlaLate],
                        'stack'           => 'sla',
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Cumprimento de SLA (Atividades de Reclamação x medidas)',
                    ],
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => [
                        'stacked'     => true,
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];

        return [
            'med_created'        => $medCreated,
            'med_open_status'    => $medStatusOpen,
            'med_closed_status'  => $medStatusClose,
            'concluded_total'    => $concludedTotal,
            'concluded_on_time'  => $concludedOnTime,
            'concluded_rate'     => $concludedTotal > 0 ? round(($concludedOnTime / $concludedTotal) * 100, 1) : 0,
            'job_sla' => [
                'total'   => $jobSlaTotal,
                'on_time' => $jobSlaOnTime,
                'late'    => $jobSlaLate,
                'rate'    => $jobSlaTotal > 0 ? round(($jobSlaOnTime / $jobSlaTotal) * 100, 1) : 0,
            ],
            'measure_sla' => [
                'total'   => $measureSlaTotal,
                'on_time' => $measureSlaOnTime,
                'late'    => $measureSlaLate,
                'rate'    => $measureSlaTotal > 0 ? round(($measureSlaOnTime / $measureSlaTotal) * 100, 1) : 0,
            ],
            'volumetry_chart' => $volumetryChart,
            'sla_chart'       => $slaChart,
        ];
    }

    protected function measureSlaOnTimeCaseSql(): string
    {
        return '
            CASE
                WHEN med_protests.dtFimMedida IS NOT NULL
                     AND protests.tipoNota = "NA"
                     AND protests.dtConclusaoDesej IS NOT NULL
                     AND med_protests.dtFimMedida <= protests.dtConclusaoDesej
                THEN 1
                WHEN med_protests.dtFimMedida IS NOT NULL
                     AND (protests.tipoNota <> "NA" OR protests.tipoNota IS NULL)
                     AND med_protests.dtFimMedidaDesej IS NOT NULL
                     AND med_protests.dtFimMedida <= med_protests.dtFimMedidaDesej
                THEN 1
                ELSE 0
            END
        ';
    }

    protected function periodMeasuresBaseQuery(Carbon $start, Carbon $end)
    {
        $query = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->where(function ($q) use ($start, $end) {
                $q->whereHas('protest', function ($sub) use ($start, $end) {
                    $sub->where('tipoNota', 'NA')
                        ->whereBetween('dtConclusaoDesej', [$start, $end]);
                })
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereBetween('dtFimMedidaDesej', [$start, $end])
                        ->whereHas('protest', function ($tipo) {
                            $tipo->where('tipoNota', '!=', 'NA')
                                ->orWhereNull('tipoNota');
                        });
                });
            })
            ->whereDoesntHave('protest.medProtests', function ($q) {
                $q->where('statusSist', 'MEDA');
            });

        return $this->applyMedProtestTypeFilter($query);
    }

    protected function firstDispatchJobSubquery()
    {
        return ProtestJob::selectRaw('med_protest_id, MIN(id) as job_id')
            ->whereNotNull('created_by')
            ->groupBy('med_protest_id');
    }

    protected function isMeasureOnTime(MedProtest $measure): bool
    {
        $finishedAt = $measure->dtFimMedida;
        if (! $finishedAt) {
            return false;
        }

        $tipoNota = $measure->protest?->tipoNota;
        if ($tipoNota === 'NA') {
            $due = $measure->protest?->dtConclusaoDesej;
            return $due ? $finishedAt->lte($due) : false;
        }

        $due = $measure->dtFimMedidaDesej;
        return $due ? $finishedAt->lte($due) : false;
    }

    protected function buildDispatcherMeasuresPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $base = $this->periodMeasuresBaseQuery($rangeStart, $rangeEnd);

        $totalMeasures = (clone $base)->count();

        $concludedBase = (clone $base)->whereNotNull('med_protests.dtFimMedida');

        $onTimeMeasures = (int) (clone $concludedBase)
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->selectRaw('SUM(' . $this->measureSlaOnTimeCaseSql() . ') as on_time')
            ->value('on_time');

        $concludedTotal = (clone $concludedBase)->count();
        $lateMeasures = max(0, $concludedTotal - $onTimeMeasures);
        $onTimeRate = $concludedTotal > 0
            ? round(($onTimeMeasures / $concludedTotal) * 100, 1)
            : 0;

        $firstJobs = $this->firstDispatchJobSubquery();

        $dispatchedBase = (clone $base)
            ->joinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
            ->join('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id');

        $dispatchedTotal = (clone $dispatchedBase)->count();

        $dispatchedOnTime = (int) (clone $dispatchedBase)
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->selectRaw('SUM(' . $this->measureSlaOnTimeCaseSql() . ') as on_time')
            ->value('on_time');

        $dispatchedConcluded = (clone $dispatchedBase)->whereNotNull('med_protests.dtFimMedida')->count();
        $dispatchedLate = max(0, $dispatchedConcluded - $dispatchedOnTime);
        $dispatchedRate = $dispatchedConcluded > 0
            ? round(($dispatchedOnTime / $dispatchedConcluded) * 100, 1)
            : 0;

        $dispatcherRows = (clone $dispatchedBase)
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->selectRaw('
                first_job.created_by as user_id,
                COUNT(*) as total_measures,
                SUM(' . $this->measureSlaOnTimeCaseSql() . ') as on_time
            ')
            ->groupBy('first_job.created_by')
            ->get();

        $users = User::whereIn('id', $dispatcherRows->pluck('user_id')->filter())->get()->keyBy('id');

        $dispatchers = $dispatcherRows->map(function ($row) use ($users) {
            $total = (int) $row->total_measures;
            $onTime = (int) $row->on_time;
            $late = max(0, $total - $onTime);

            return [
                'user_id'    => $row->user_id,
                'user_name'  => optional($users->get($row->user_id))->name ?? 'N/A',
                'total'      => $total,
                'on_time'    => $onTime,
                'late'       => $late,
                'sla_rate'   => $total > 0 ? round(($onTime / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values();

        $selectedUser = null;
        if ($this->userId) {
            $selectedUserName = User::find($this->userId)?->name ?? 'N/A';

            $listQuery = (clone $base)
                ->joinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
                ->join('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id')
                ->where('first_job.created_by', $this->userId)
                ->with(['protest:id,nota,tipoNota,dtConclusaoDesej'])
                ->select([
                    'med_protests.*',
                    'first_job.id as job_id',
                    'first_job.sent_at as job_sent_at',
                ])
                ->orderByDesc('med_protests.dtFimMedidaDesej');

            $measures = $listQuery->paginate(10, ['*'], 'dispatcher_measures_page');

            $measures->setCollection($measures->getCollection()->map(function (MedProtest $measure) {
                $isOnTime = $this->isMeasureOnTime($measure);
                $dueDate = $measure->protest?->tipoNota === 'NA'
                    ? $measure->protest?->dtConclusaoDesej
                    : $measure->dtFimMedidaDesej;

                return [
                    'protest_number' => $measure->protest?->nota ?? 'N/A',
                    'med_id'         => $measure->med_id ?? 'N/A',
                    'due_date'       => $dueDate?->format('d/m/Y') ?? 'N/A',
                    'finished_at'    => $measure->dtFimMedida?->format('d/m/Y') ?? 'N/A',
                    'job_id'         => $measure->job_id ?? null,
                    'job_sent_at'    => $measure->job_sent_at
                        ? Carbon::parse($measure->job_sent_at)->format('d/m/Y H:i')
                        : 'N/A',
                    'status_label'   => $isOnTime ? 'Dentro do prazo' : 'Fora do prazo',
                    'status_badge'   => $isOnTime ? 'bg-success' : 'bg-danger',
                ];
            }));

            $selectedUser = [
                'name'     => $selectedUserName,
                'measures' => $measures,
            ];
        }

        return [
            'period_label'     => $rangeStart->format('d/m/Y') . ' - ' . $rangeEnd->format('d/m/Y'),
            'total_measures'   => $totalMeasures,
            'on_time'          => $onTimeMeasures,
            'late'             => $lateMeasures,
            'on_time_rate'     => $onTimeRate,
            'dispatched_total' => $dispatchedTotal,
            'dispatched_on'    => $dispatchedOnTime,
            'dispatched_late'  => $dispatchedLate,
            'dispatched_rate'  => $dispatchedRate,
            'dispatchers'      => $dispatchers,
            'selected_user'    => $selectedUser,
        ];
    }

    protected function buildBottlenecksPanel(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();
        $now        = now();

        $periodBase = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd]);

        $categoryRows = (clone $periodBase)
            ->selectRaw('
                protest_type,
                COUNT(*) as total_medidas,
                SUM(CASE WHEN statusSist = "MEDA" THEN 1 ELSE 0 END) as abertas,
                SUM(CASE WHEN statusSist = "MEDA" AND dtFimMedidaDesej IS NOT NULL AND dtFimMedidaDesej < ? THEN 1 ELSE 0 END) as vencidas
            ', [$now])
            ->groupBy('protest_type')
            ->get();

        $prevMonthStart = $rangeStart->copy()->startOfMonth()->subMonth();
        $prevMonthEnd   = $rangeStart->copy()->startOfMonth()->subDay();

        $passiveRows = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->whereBetween('dtCriacaoMedida', [$prevMonthStart, $prevMonthEnd])
            ->where('statusSist', 'MEDA')
            ->selectRaw('protest_type, COUNT(*) as total_passivo')
            ->groupBy('protest_type')
            ->pluck('total_passivo', 'protest_type');

        $totalMedidasPeriodo = max(1, $categoryRows->sum('total_medidas'));

        $categories = $categoryRows->map(function ($row) use ($totalMedidasPeriodo, $passiveRows) {
            $total = (int)$row->total_medidas;
            $open  = (int)$row->abertas;
            $late  = (int)$row->vencidas;
            $typeKey = $row->protest_type instanceof ProtestType ? $row->protest_type->value : $row->protest_type;
            $passive = (int)($passiveRows[$typeKey] ?? 0);

            return [
                'type_value' => $row->protest_type,
                'label'      => $this->resolveProtestTypeLabel($row->protest_type),
                'total'      => $total,
                'abertas'    => $open,
                'passivo'    => $passive,
                'vencidas'   => $late,
                'percent'    => round(($total / $totalMedidasPeriodo) * 100, 1),
            ];
        })->sortByDesc('total')->values();

        $categoriesTotals = [
            'total'   => $categories->sum('total'),
            'abertas' => $categories->sum('abertas'),
            'passivo' => $categories->sum('passivo'),
            'vencidas'=> $categories->sum('vencidas'),
        ];

        $tipoNota = Protest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->whereNotNull('dtAberturaNota')
            ->whereBetween('dtAberturaNota', [$rangeStart, $rangeEnd])
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->selectRaw('protests.tipoNota as tipoNota, COUNT(*) as total')
            ->groupBy('protests.tipoNota')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'tipo'  => $row->tipoNota ?? 'Sem classificacao',
                    'total' => (int)($row->total ?? 0),
                ];
            })
            ->toArray();

        $tipoNotaLate = MedProtest::query()
            ->join('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->where('med_protests.statusSist', 'MEDA')
            ->whereNotNull('med_protests.dtFimMedidaDesej')
            ->whereBetween('med_protests.dtFimMedidaDesej', [$rangeStart, $rangeEnd])
            ->where('med_protests.dtFimMedidaDesej', '<', $now)
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->selectRaw('protests.tipoNota as tipoNota, COUNT(*) as total')
            ->groupBy('protests.tipoNota')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'tipo'  => $row->tipoNota ?? 'Sem classificacao',
                    'total' => (int)($row->total ?? 0),
                ];
            })
            ->toArray();

        return [
            'categories'        => $categories->toArray(),
            'categories_totals' => $categoriesTotals,
            'tipo_nota'         => $tipoNota,
            'tipo_nota_late'    => $tipoNotaLate,
        ];
    }

    protected function buildDailyOpeningsChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $protestData = Protest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->whereNotNull('dtAberturaNota')
            ->whereBetween('dtAberturaNota', [$rangeStart, $rangeEnd])
            ->whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->selectRaw('DATE(dtAberturaNota) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $medData = MedProtest::query()
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->whereNotNull('dtCriacaoMedida')
            ->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd])
            ->whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->selectRaw('DATE(dtCriacaoMedida) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels         = [];
        $seriesProtests = [];
        $seriesMed      = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();    // Y-m-d
            $labels[] = $cursor->format('d/m'); // label visual

            $seriesProtests[] = (int)($protestData[$key] ?? 0);
            $seriesMed[]      = (int)($medData[$key] ?? 0);

            $cursor->addDay();
        }

        $points = max(count($labels), 1);
        $avgProtest = $points > 0 ? round(array_sum($seriesProtests) / $points, 2) : 0;
        $avgMed     = $points > 0 ? round(array_sum($seriesMed) / $points, 2) : 0;
        $avgProtestSeries = array_fill(0, count($labels), $avgProtest);
        $avgMedSeries     = array_fill(0, count($labels), $avgMed);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'Abertura Reclamações',
                        'data'            => $seriesProtests,
                        'backgroundColor' => 'rgba(102,126,234,0.4)',
                        'borderColor'     => '#667eea',
                        'borderWidth'     => 1,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média Reclamação',
                        'data'        => $avgProtestSeries,
                        'borderColor' => '#1f3a8a',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Criação Medidas',
                        'data'            => $seriesMed,
                        'borderColor'     => '#f5576c',
                        'backgroundColor' => 'rgba(245,87,108,0.2)',
                        'tension'         => 0.1,
                        'fill'            => false,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média Medidas',
                        'data'        => $avgMedSeries,
                        'borderColor' => '#b71c1c',
                        'borderWidth' => 2,
                        'borderDash'  => [4, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Aberturas diárias (Reclamações x Medidas)',
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title'       => [
                            'display' => true,
                            'text'    => 'Qtd de registros',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildMedaSnapshot(Carbon $start, Carbon $end): array
    {
        $openMeasures = MedProtest::where('statusSist', 'MEDA')
            ->whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->count();

        $openProtests = Protest::whereHas('medProtests', function ($q) {
            $q->where('statusSist', 'MEDA')
                ->whereHas('ProtestJobs');
        })
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->count();

        $totalProtests  = Protest::whereHas('ProtestJobs')
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->count();
        $closedProtests = Protest::whereHas('ProtestJobs', function ($q) {
            $q->whereNotNull('finished_at');
        })
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->count();

        $baselineStart = $start->copy()->startOfDay();
        $baselineEnd = $end->copy()->endOfDay();
        $daysInRange = max($baselineStart->diffInDays($baselineEnd) + 1, 1);

        $baselineJobs = $this->jobsBaseQuery($baselineStart, $baselineEnd, 'finished_at')
            ->whereNotNull('finished_at')
            ->whereNotNull('med_protest_id')
            ->whereHas('medProtest', function ($q) {
                $q->where('statusSist', '!=', 'MEDA');
            });

        $dispatchedJobs = (clone $baselineJobs)->whereNotNull('created_by')->count();
        $dispatcherUsers = (int)(clone $baselineJobs)
            ->whereNotNull('created_by')
            ->selectRaw('COUNT(DISTINCT protest_jobs.created_by) as total')
            ->value('total');

        $finishedJobs = (clone $baselineJobs)->count();
        $executorUsers = (int)(clone $baselineJobs)
            ->whereNotNull('owner_id')
            ->selectRaw('COUNT(DISTINCT protest_jobs.owner_id) as total')
            ->value('total');

        $avgDispatchDaily    = round($dispatchedJobs / $daysInRange, 1);
        $avgFinishDaily      = round($finishedJobs / $daysInRange, 1);
        $avgDispatchPerUser  = $dispatcherUsers > 0 ? round($dispatchedJobs / ($dispatcherUsers * $daysInRange), 1) : 0;
        $avgFinishPerUser    = $executorUsers > 0 ? round($finishedJobs / ($executorUsers * $daysInRange), 1) : 0;

        $daysToClear = $avgFinishDaily > 0 ? (int)ceil($openMeasures / $avgFinishDaily) : null;

        $statusLabel = 'Sem dados';
        $statusBadge = 'bg-secondary';
        $statusMessage = 'Período ainda sem conclusões para estimar produtividade.';

        if (!is_null($daysToClear)) {
            if ($daysToClear <= 7) {
                $statusLabel = 'Baixo';
                $statusBadge = 'bg-success';
                $statusMessage = 'Capacidade atual elimina a pilha em menos de uma semana.';
            } elseif ($daysToClear <= 15) {
                $statusLabel = 'Moderado';
                $statusBadge = 'bg-warning text-dark';
                $statusMessage = 'A pilha será zerada em aproximadamente ' . $daysToClear . ' dias.';
            } else {
                $statusLabel = 'Alto';
                $statusBadge = 'bg-danger';
                $statusMessage = 'Backlog exige mais de ' . $daysToClear . ' dias com a produtividade atual.';
            }
        }

        return [
            'open_measures'         => $openMeasures,
            'open_protests'         => $openProtests,
            'closed_protests'       => $closedProtests,
            'total_protests'        => $totalProtests,
            'dispatcher_users'      => $dispatcherUsers,
            'executor_users'        => $executorUsers,
            'avg_dispatch_daily'    => $avgDispatchDaily,
            'avg_dispatch_per_user' => $avgDispatchPerUser,
            'avg_finish_daily'      => $avgFinishDaily,
            'avg_finish_per_user'   => $avgFinishPerUser,
            'days_to_clear'         => $daysToClear,
            'status_label'          => $statusLabel,
            'status_badge_class'    => $statusBadge,
            'status_message'        => $statusMessage,
            'days_considered'       => $daysInRange,
            'sample_start'          => $baselineStart->format('d/m/Y'),
            'sample_end'            => $baselineEnd->format('d/m/Y'),
        ];
    }

    /**
     * Gráfico para MEDA: med_protests com e sem Atividade de Reclamação relacionada.
     */
    protected function buildMedaJobsChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $jobsSub = ProtestJob::selectRaw('med_protest_id, COUNT(*) as job_count')
            ->groupBy('med_protest_id');

        $raw = MedProtest::query()
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->tap(fn ($q) => $this->applyComplaintFiltersToMedProtestQuery($q))
            ->leftJoinSub($jobsSub, 'jobs', 'jobs.med_protest_id', '=', 'med_protests.id')
            ->whereBetween('med_protests.dtCriacaoMedida', [$rangeStart, $rangeEnd])
            ->tap(fn ($q) => $this->applyMedProtestTypeFilter($q))
            ->selectRaw("
                DATE(med_protests.dtCriacaoMedida) as date,
                SUM(CASE WHEN COALESCE(jobs.job_count, 0) > 0 THEN 1 ELSE 0 END) as with_job,
                SUM(CASE WHEN COALESCE(jobs.job_count, 0) = 0 THEN 1 ELSE 0 END) as without_job
            ")
                ->groupBy('date')
                ->get()
                ->keyBy('date');

        $labels            = [];
        $seriesWithJob     = [];
        $seriesNoJob       = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');

            $withJobValue = $raw[$key]->with_job ?? 0;
            $withoutJobValue = $raw[$key]->without_job ?? 0;

            $seriesWithJob[] = (int)$withJobValue;
            $seriesNoJob[]   = (int)$withoutJobValue;

            $cursor->addDay();
        }

        $points = max(count($labels), 1);
        $avgWithJob = $points > 0 ? round(array_sum($seriesWithJob) / $points, 2) : 0;
        $avgWithoutJob = $points > 0 ? round(array_sum($seriesNoJob) / $points, 2) : 0;
        $avgWithJobSeries = array_fill(0, count($labels), $avgWithJob);
        $avgWithoutJobSeries = array_fill(0, count($labels), $avgWithoutJob);

        return [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'type'            => 'bar',
                        'label'           => 'MEDA criadas com Atividade de Reclamação',
                        'data'            => $seriesWithJob,
                        'backgroundColor' => 'rgba(16,185,129,0.4)',
                        'borderColor'     => '#10b981',
                        'borderWidth'     => 1,
                        'stack'           => 'meda',
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média MEDA com Atividade de Reclamação',
                        'data'        => $avgWithJobSeries,
                        'borderColor' => '#0f766e',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'            => 'bar',
                        'label'           => 'MEDA criadas sem Atividade de Reclamação',
                        'data'            => $seriesNoJob,
                        'backgroundColor' => 'rgba(239,68,68,0.35)',
                        'borderColor'     => '#ef4444',
                        'borderWidth'     => 1,
                        'stack'           => 'meda',
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média MEDA sem Atividade de Reclamação',
                        'data'        => $avgWithoutJobSeries,
                        'borderColor' => '#be123c',
                        'borderWidth' => 2,
                        'borderDash'  => [4, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'MEDA criadas (com x sem Atividade de Reclamação)',
                    ],
                ],
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => [
                        'stacked'      => true,
                        'beginAtZero'  => true,
                        'title'        => [
                            'display' => true,
                            'text'    => 'Qtd de medidas',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function normalizeMedaHistogramSegment(?string $segment): ?string
    {
        $normalized = Str::of((string) $segment)->lower()->trim()->replace('-', '_')->value();
        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'overdue', 'vencido', 'vencidos' => 'overdue',
            'due_soon', 'vencendo' => 'due_soon',
            'within', 'a_vencer', 'a vencer' => 'within',
            default => null,
        };
    }

    protected function applyMedaSegmentCondition($query, string $column, ?string $segment): void
    {
        $normalizedSegment = $this->normalizeMedaHistogramSegment($segment);
        if (!$normalizedSegment) {
            return;
        }

        $todayStart = now()->startOfDay();
        $dueSoonEnd = $todayStart->copy()->addDays(3)->endOfDay();

        if ($normalizedSegment === 'overdue') {
            $query->where($column, '<', $todayStart);
            return;
        }

        if ($normalizedSegment === 'due_soon') {
            $query->whereBetween($column, [$todayStart, $dueSoonEnd]);
            return;
        }

        $query->where($column, '>', $dueSoonEnd);
    }

    protected function applyMedaDesiredDateWindowFilter(Builder $query, string $filter): void
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $thirdDay = $today->copy()->addDays(3);
        $dueSoonEnd = $today->copy()->addDays(3)->endOfDay();

        $query->where(function (Builder $scope) use ($filter, $today, $tomorrow, $thirdDay, $dueSoonEnd) {
            $scope->whereHas('protest', function ($p) use ($filter, $today, $tomorrow, $thirdDay, $dueSoonEnd) {
                $p->where('tipoNota', 'NA')
                    ->whereNotNull('dtConclusaoDesej');

                if ($filter === 'overdue') {
                    $p->where('dtConclusaoDesej', '<', $today);
                } elseif ($filter === 'due_soon') {
                    $p->whereBetween('dtConclusaoDesej', [$today, $dueSoonEnd]);
                } elseif ($filter === 'today') {
                    $p->whereDate('dtConclusaoDesej', $today);
                } elseif ($filter === 'tomorrow') {
                    $p->whereDate('dtConclusaoDesej', $tomorrow);
                } elseif ($filter === 'in_3_days') {
                    $p->whereDate('dtConclusaoDesej', $thirdDay);
                }
            })->orWhere(function (Builder $sub) use ($filter, $today, $tomorrow, $thirdDay, $dueSoonEnd) {
                $sub->whereNotNull('dtFimMedidaDesej')
                    ->whereHas('protest', function ($tipo) {
                        $tipo->where(function ($t) {
                            $t->where('tipoNota', '!=', 'NA')
                                ->orWhereNull('tipoNota');
                        });
                    });

                if ($filter === 'overdue') {
                    $sub->where('dtFimMedidaDesej', '<', $today);
                } elseif ($filter === 'due_soon') {
                    $sub->whereBetween('dtFimMedidaDesej', [$today, $dueSoonEnd]);
                } elseif ($filter === 'today') {
                    $sub->whereDate('dtFimMedidaDesej', $today);
                } elseif ($filter === 'tomorrow') {
                    $sub->whereDate('dtFimMedidaDesej', $tomorrow);
                } elseif ($filter === 'in_3_days') {
                    $sub->whereDate('dtFimMedidaDesej', $thirdDay);
                }
            });
        });
    }

    protected function applyMedaHistogramFilterOnOpenMeasures(Builder $query): void
    {
        $selectedSegment = $this->normalizeMedaHistogramSegment($this->medaHistogramStackFilter);
        $hasBucket = $this->medaHistogramBucket && preg_match('/^\d{4}\-\d{2}$/', $this->medaHistogramBucket);
        $bucketYear = null;
        $bucketMonth = null;

        if ($hasBucket) {
            [$bucketYear, $bucketMonth] = explode('-', $this->medaHistogramBucket);
            $bucketYear = (int) $bucketYear;
            $bucketMonth = (int) $bucketMonth;
        }

        if (!$hasBucket && !$selectedSegment) {
            return;
        }

        if ($this->medaHistogramSource === 'sla') {
            $query->whereHas('ProtestJobs', function ($jobQuery) use ($bucketYear, $bucketMonth, $hasBucket, $selectedSegment) {
                $jobQuery->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')
                        ->orWhere('status', '!=', ProtestJobStatus::CANCELED->value);
                })->whereNull('finished_at')
                    ->whereNotNull('sla_due_at');

                if ($hasBucket) {
                    $jobQuery->whereYear('sla_due_at', $bucketYear)
                        ->whereMonth('sla_due_at', $bucketMonth);
                }

                $this->applyMedaSegmentCondition($jobQuery, 'sla_due_at', $selectedSegment);
            });
            return;
        }

        $query->where(function (Builder $scope) use ($bucketYear, $bucketMonth, $hasBucket, $selectedSegment) {
            $scope->whereHas('protest', function ($p) use ($bucketYear, $bucketMonth, $hasBucket, $selectedSegment) {
                $p->where('tipoNota', 'NA')
                    ->whereNotNull('dtConclusaoDesej');

                if ($hasBucket) {
                    $p->whereYear('dtConclusaoDesej', $bucketYear)
                        ->whereMonth('dtConclusaoDesej', $bucketMonth);
                }

                $this->applyMedaSegmentCondition($p, 'dtConclusaoDesej', $selectedSegment);
            })->orWhere(function ($sub) use ($bucketYear, $bucketMonth, $hasBucket, $selectedSegment) {
                $sub->whereNotNull('dtFimMedidaDesej')
                    ->whereHas('protest', function ($tipo) {
                        $tipo->where(function ($t) {
                            $t->where('tipoNota', '!=', 'NA')
                                ->orWhereNull('tipoNota');
                        });
                    });

                if ($hasBucket) {
                    $sub->whereYear('dtFimMedidaDesej', $bucketYear)
                        ->whereMonth('dtFimMedidaDesej', $bucketMonth);
                }

                $this->applyMedaSegmentCondition($sub, 'dtFimMedidaDesej', $selectedSegment);
            });
        });
    }

    protected function buildMedaOpenBaseQuery(bool $applyDispatchFilter = true, bool $applyDueWindowFilter = true): Builder
    {
        $query = MedProtest::query()
            ->where('statusSist', 'MEDA')
            ->where(function (Builder $scope) {
                $scope->whereHas('protest', function ($p) {
                    $p->where('tipoNota', 'NA')
                        ->whereNotNull('dtConclusaoDesej');
                })->orWhere(function (Builder $sub) {
                    $sub->whereNotNull('dtFimMedidaDesej')
                        ->whereHas('protest', function ($tipo) {
                            $tipo->where(function ($t) {
                                $t->where('tipoNota', '!=', 'NA')
                                    ->orWhereNull('tipoNota');
                            });
                        });
                });
            });

        $query = $this->applyMedProtestTypeFilter($query);
        $query = $this->applyComplaintFiltersToMedProtestQuery($query);

        if ($this->medaHistogramBtzeroFilter === 'without_btzero') {
            $query->notIdentifiedAsBtzero();
        } elseif ($this->medaHistogramBtzeroFilter === 'only_btzero') {
            $query->identifiedAsBtzero();
        }

        if ($applyDispatchFilter) {
            if ($this->medaDispatchFilter === 'with_job') {
                $query->whereHas('ProtestJobs');
            } elseif ($this->medaDispatchFilter === 'without_job') {
                $query->whereDoesntHave('ProtestJobs');
            }
        }

        if ($applyDueWindowFilter && !empty($this->medaDueWindowFilter)) {
            $this->applyMedaDesiredDateWindowFilter($query, $this->medaDueWindowFilter);
        }

        if (!empty($this->medaOpenNoteTypeFilter)) {
            $noteType = mb_strtoupper((string) $this->medaOpenNoteTypeFilter);
            $query->whereHas('protest', function ($protestQuery) use ($noteType) {
                $protestQuery->whereRaw('UPPER(COALESCE(tipoNota, "")) = ?', [$noteType]);
            });
        }

        return $query;
    }

    protected function buildMedaOpenDesiredHistogram(Carbon $start, Carbon $end): array
    {
        $query = $this->buildMedaOpenBaseQuery()
            ->with(['protest:id,tipoNota,dtConclusaoDesej,txtGrpCodificacao'])
            ->with([
                'ProtestJobs' => function ($jobQuery) {
                    $jobQuery->with('owner:id,name')
                        ->orderByDesc('sent_at')
                        ->orderByDesc('id');
                },
            ])
            ->withCount([
                'ProtestJobs as all_jobs_count',
            ]);

        $measures = $query->get();

        $withJobByMonth = [];
        $withoutJobByMonth = [];
        $btzeroWithJobByMonth = [];
        $btzeroWithoutJobByMonth = [];
        $totals = [];
        $overdueByMonth = [];
        $dueSoonByMonth = [];
        $withinByMonth = [];
        $totalWithJob = 0;
        $totalWithoutJob = 0;
        $totalBtzero = 0;
        $totalBtzeroWithJob = 0;
        $totalBtzeroWithoutJob = 0;

        foreach ($measures as $measure) {
            $desiredDate = (mb_strtoupper((string) ($measure->protest?->tipoNota ?? '')) === 'NA')
                ? $measure->protest?->dtConclusaoDesej
                : $measure->dtFimMedidaDesej;

            $hasJob = ((int) ($measure->all_jobs_count ?? 0)) > 0;
            $isBtzero = $this->isBtzeroMeasure($measure);

            $bucketDate = null;
            if ($this->medaHistogramSource === 'sla') {
                $pendingJob = $measure->ProtestJobs->first(function ($job) {
                    $statusRaw = $job->status ?? '';
                    $status = $statusRaw instanceof ProtestJobStatus
                        ? $statusRaw->value
                        : mb_strtolower((string) $statusRaw);
                    $isCanceled = $status === ProtestJobStatus::CANCELED->value;
                    $isConfirmed = (bool) ($job->confirmed ?? false);
                    return !$isCanceled
                        && !$isConfirmed
                        && is_null($job->finished_at)
                        && !is_null($job->sla_due_at);
                });
                // Mantém o universo idêntico ao da lista: se não houver SLA de job,
                // cai para data desejada da medida.
                $bucketDate = $pendingJob?->sla_due_at ?? $desiredDate;
            } else {
                $bucketDate = $desiredDate;
            }

            if (!$bucketDate) {
                continue;
            }

            $normalized = Carbon::parse($bucketDate)->copy()->startOfDay();
            $key = $normalized->format('Y-m');

            if ($isBtzero) {
                if ($hasJob) {
                    $btzeroWithJobByMonth[$key] = ($btzeroWithJobByMonth[$key] ?? 0) + 1;
                    $totalBtzeroWithJob++;
                } else {
                    $btzeroWithoutJobByMonth[$key] = ($btzeroWithoutJobByMonth[$key] ?? 0) + 1;
                    $totalBtzeroWithoutJob++;
                }
            } else {
                if ($hasJob) {
                    $withJobByMonth[$key] = ($withJobByMonth[$key] ?? 0) + 1;
                    $totalWithJob++;
                } else {
                    $withoutJobByMonth[$key] = ($withoutJobByMonth[$key] ?? 0) + 1;
                    $totalWithoutJob++;
                }
            }

            $totals[$key] = ($totals[$key] ?? 0) + 1;
            $diff = now()->startOfDay()->diffInDays($normalized, false);
            if ($diff < 0) {
                $overdueByMonth[$key] = ($overdueByMonth[$key] ?? 0) + 1;
            } elseif ($diff <= 3) {
                $dueSoonByMonth[$key] = ($dueSoonByMonth[$key] ?? 0) + 1;
            } else {
                $withinByMonth[$key] = ($withinByMonth[$key] ?? 0) + 1;
            }
        }
        $totalBtzero = $totalBtzeroWithJob + $totalBtzeroWithoutJob;

        $monthKeys = array_keys($totals);
        sort($monthKeys);
        $selectedBucket = in_array((string) $this->medaHistogramBucket, $monthKeys, true)
            ? (string) $this->medaHistogramBucket
            : null;
        $overdueCounts = [];
        $dueSoonCounts = [];
        $withinCounts = [];
        $monthTotals = [];
        $monthLabels = [];
        foreach ($monthKeys as $bucketKey) {
            $overdueCounts[] = (int) ($overdueByMonth[$bucketKey] ?? 0);
            $dueSoonCounts[] = (int) ($dueSoonByMonth[$bucketKey] ?? 0);
            $withinCounts[] = (int) ($withinByMonth[$bucketKey] ?? 0);
            $monthTotals[$bucketKey] = (int) ($totals[$bucketKey] ?? 0);
            $monthLabels[] = Carbon::createFromFormat('Y-m', $bucketKey)->format('m/Y');
        }
        $monthLabelsMap = [];
        foreach ($monthKeys as $index => $bucketKey) {
            $monthLabelsMap[$bucketKey] = $monthLabels[$index] ?? $bucketKey;
        }

        $displayMonthKeys = $monthKeys;
        $displayMonthLabels = $monthLabels;
        $displayOverdueCounts = $overdueCounts;
        $displayDueSoonCounts = $dueSoonCounts;
        $displayWithinCounts = $withinCounts;

        if ($selectedBucket) {
            $idx = array_search($selectedBucket, $monthKeys, true);
            if ($idx !== false) {
                $displayMonthKeys = [$selectedBucket];
                $displayMonthLabels = [$monthLabels[$idx] ?? $selectedBucket];
                $displayOverdueCounts = [(int) ($overdueCounts[$idx] ?? 0)];
                $displayDueSoonCounts = [(int) ($dueSoonCounts[$idx] ?? 0)];
                $displayWithinCounts = [(int) ($withinCounts[$idx] ?? 0)];
            }
        }

        $overdueTotal = (int) array_sum($displayOverdueCounts);
        $dueSoonTotal = (int) array_sum($displayDueSoonCounts);
        $withinTotal = (int) array_sum($displayWithinCounts);
        $stackTotals = array_map(
            fn ($overdue, $dueSoon, $within) => (int) $overdue + (int) $dueSoon + (int) $within,
            $displayOverdueCounts,
            $displayDueSoonCounts,
            $displayWithinCounts
        );

        return [
            'total_with_job' => $totalWithJob,
            'total_without_job' => $totalWithoutJob,
            'total_btzero' => $totalBtzero,
            'total_btzero_with_job' => $totalBtzeroWithJob,
            'total_btzero_without_job' => $totalBtzeroWithoutJob,
            'total' => $totalWithJob + $totalWithoutJob + $totalBtzero,
            'years' => [],
            'selectedYear' => null,
            'selectedMonth' => null,
            'selectedBucket' => $selectedBucket,
            'selectedSegment' => $this->normalizeMedaHistogramSegment($this->medaHistogramStackFilter),
            'monthKeys' => $monthKeys,
            'monthTotals' => $monthTotals,
            'monthLabels' => $monthLabelsMap,
            'source' => $this->medaHistogramSource,
            'chart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $displayMonthLabels,
                    'datasets' => [
                        [
                            'type' => 'bar',
                            'label' => "Vencidos ({$overdueTotal})",
                            'data' => $displayOverdueCounts,
                            'backgroundColor' => 'rgba(33,46,62,0.85)',
                            'borderColor' => '#212E3E',
                            'borderWidth' => 1,
                            'stack' => 'prazo',
                            'datalabels' => [
                                'display' => false,
                            ],
                        ],
                        [
                            'type' => 'bar',
                            'label' => "Vencendo ({$dueSoonTotal})",
                            'data' => $displayDueSoonCounts,
                            'backgroundColor' => 'rgba(40,255,82,0.85)',
                            'borderColor' => '#28FF52',
                            'borderWidth' => 1,
                            'stack' => 'prazo',
                            'datalabels' => [
                                'display' => false,
                            ],
                        ],
                        [
                            'type' => 'bar',
                            'label' => "A vencer ({$withinTotal})",
                            'data' => $displayWithinCounts,
                            'backgroundColor' => 'rgba(124,149,153,0.85)',
                            'borderColor' => '#7C9599',
                            'borderWidth' => 1,
                            'stack' => 'prazo',
                            'datalabels' => [
                                'display' => true,
                                'labels' => [
                                    'total' => [
                                        'display' => true,
                                        'anchor' => 'end',
                                        'align' => 'top',
                                        'offset' => 8,
                                        'color' => '#1f2937',
                                        'font' => ['weight' => 'bold', 'size' => 11],
                                        'formatter' => '__TOTAL_FROM_SERIES__',
                                        'totalSeries' => $stackTotals,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'layout' => [
                        'padding' => [
                            'top' => 10,
                        ],
                    ],
                    'plugins' => [
                        'legend' => [
                            'position' => 'top',
                            'labels' => [
                                'padding' => 14,
                            ],
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'Histograma de previsões mensais (em aberto)',
                        ],
                        'datalabels' => [
                            'display' => true,
                        ],
                    ],
                    'scales' => [
                        'x' => ['stacked' => true],
                        'y' => [
                            'stacked' => true,
                            'beginAtZero' => true,
                            'grace' => '20%',
                            'title' => [
                                'display' => true,
                                'text' => 'Qtd de medidas',
                            ],
                        ],
                    ],
                    'onClickFilter' => [
                        'enabled' => true,
                        'method' => 'setMedaHistogramStackSelection',
                        'mode' => 'index',
                        'intersect' => false,
                        'allowLabelFallback' => true,
                        'withDataset' => true,
                        'keys' => $displayMonthKeys,
                        'datasetKeys' => ['overdue', 'due_soon', 'within'],
                    ],
                ],
            ],
        ];
    }

    protected function buildMedaOpenDispatchList(): array
    {
        $query = $this->buildMedaOpenBaseQuery()
            ->with([
                'protest:id,nota,tipoNota,codecodf,dtAberturaNota,dtConclusaoDesej,type,txtGrpCodificacao',
                'ProtestJobs' => function ($jobQuery) {
                    $jobQuery->with(['owner:id,name', 'creator:id,name'])
                        ->orderByDesc('sent_at')
                        ->orderByDesc('id');
                },
            ]);

        if ($this->medaDoneOpenCreatorId) {
            $creatorId = (string) $this->medaDoneOpenCreatorId;
            $query->whereHas('ProtestJobs', function ($jobQuery) use ($creatorId) {
                $jobQuery->where('created_by', $creatorId)
                    ->whereNotNull('finished_at')
                    ->where('status', ProtestJobStatus::DONE->value);
            })->whereHas('ProtestJobs')
                ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                    $jobQuery->where(function ($pending) {
                        $pending->whereNull('finished_at')
                            ->orWhere('status', '!=', ProtestJobStatus::DONE->value);
                    });
                });

            // Garante alinhamento com o painel de agrupamento:
            // o criador filtrado precisa ser o do último job concluído da medida.
            $query->whereExists(function ($subQuery) use ($creatorId) {
                $subQuery->selectRaw('1')
                    ->from('protest_jobs as pj')
                    ->whereColumn('pj.med_protest_id', 'med_protests.id')
                    ->where('pj.created_by', $creatorId)
                    ->whereNotNull('pj.finished_at')
                    ->where('pj.status', ProtestJobStatus::DONE->value)
                    ->whereRaw(
                        'pj.id = (
                            SELECT pj2.id
                            FROM protest_jobs pj2
                            WHERE pj2.med_protest_id = med_protests.id
                            ORDER BY pj2.finished_at DESC, pj2.id DESC
                            LIMIT 1
                        )'
                    );
            });
        }

        $this->applyMedaHistogramFilterOnOpenMeasures($query);

        $totalWithoutDispatch = (clone $query)
            ->whereDoesntHave('ProtestJobs')
            ->count();

        $totalDispatchedOpen = (clone $query)
            ->whereHas('ProtestJobs')
            ->count();

        $noteTypeCounts = ['NA' => 0, 'OU' => 0, 'PR' => 0];
        $typeRows = (clone $query)->with(['protest:id,tipoNota'])->get();
        foreach ($typeRows as $measure) {
            $type = mb_strtoupper((string) ($measure->protest?->tipoNota ?? ''));
            if (isset($noteTypeCounts[$type])) {
                $noteTypeCounts[$type]++;
            }
        }

        $list = $query
            ->orderByRaw("
                CASE
                    WHEN statusSist = 'MEDE' THEN 0
                    WHEN statusSist = 'MEDA' THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('dtFimMedida')
            ->orderBy('dtFimMedidaDesej')
            ->orderBy('id')
            ->paginate(20, ['*'], 'meda_open_dispatch_page');

        $now = now()->startOfDay();

        $list->setCollection($list->getCollection()->map(function (MedProtest $measure) use ($now) {
            $pendingJob = $measure->ProtestJobs->first(function ($job) {
                $statusRaw = $job->status ?? '';
                $status = $statusRaw instanceof ProtestJobStatus
                    ? $statusRaw->value
                    : mb_strtolower((string) $statusRaw);
                return $status === '' || $status !== ProtestJobStatus::CANCELED->value;
            }) ?? $measure->ProtestJobs->first();
            $displayJob = $pendingJob;
            if (!empty($this->medaDoneOpenCreatorId)) {
                $displayJob = $measure->ProtestJobs->first(function ($job) {
                    return !is_null($job->finished_at)
                        && (
                            $job->status === ProtestJobStatus::DONE
                            || (string) $job->status === ProtestJobStatus::DONE->value
                        );
                }) ?? $pendingJob;
            }
            $desiredAt = $this->resolveMeasureDesiredDate($measure);
            $statusSist = mb_strtoupper((string) ($measure->statusSist ?? ''));
            $isMede = $statusSist === 'MEDE';

            $desiredInfo = [
                'date' => $desiredAt?->format('d/m/Y') ?? '---',
                'class' => 'bg-secondary',
                'detail' => 'Sem data desejada',
            ];

            if ($desiredAt) {
                if ($isMede && $measure->dtFimMedida) {
                    $finishedAt = $measure->dtFimMedida->copy()->startOfDay();
                    $onTime = $finishedAt->lte($desiredAt->copy()->startOfDay());
                    $desiredInfo = [
                        'date' => $desiredAt->format('d/m/Y'),
                        'class' => $onTime ? 'bg-success' : 'bg-danger',
                        'detail' => 'Encerrado em ' . $measure->dtFimMedida->format('d/m/Y'),
                    ];
                } else {
                    $deltaDesired = $now->diffInDays($desiredAt->copy()->startOfDay(), false);
                    if ($deltaDesired < 0) {
                        $desiredInfo['class'] = 'bg-danger';
                        $desiredInfo['detail'] = 'Vencido ha ' . abs($deltaDesired) . ' d';
                    } elseif ($deltaDesired <= 3) {
                        if ($deltaDesired === 0) {
                            $desiredInfo['class'] = 'bg-warning text-danger';
                            $desiredInfo['detail'] = 'Vence hoje';
                        } else {
                            $desiredInfo['class'] = 'bg-warning text-dark';
                            $desiredInfo['detail'] = 'Vence em ' . $deltaDesired . ' d';
                        }
                    } else {
                        $desiredInfo['class'] = 'bg-success';
                        $desiredInfo['detail'] = 'Faltam ' . $deltaDesired . ' d';
                    }
                }
            }

            $slaInfo = [
                'due_date' => $pendingJob?->sla_due_at?->format('d/m/Y H:i') ?? '---',
                'delivery_date' => $pendingJob?->finished_at?->format('d/m/Y H:i') ?? 'Nao entregue',
                'class' => 'bg-secondary',
                'detail' => 'Sem SLA',
            ];

            if ($pendingJob?->sla_due_at) {
                $slaDueAt = $pendingJob->sla_due_at->copy();
                $slaFinishedAt = $pendingJob->finished_at?->copy();

                if ($slaFinishedAt) {
                    $onTime = $slaFinishedAt->lte($slaDueAt);
                    if ($onTime) {
                        $slaInfo['class'] = 'bg-success';
                        $slaInfo['detail'] = 'Entregue no prazo';
                    } else {
                        $deltaFinish = $slaDueAt->diffInDays($slaFinishedAt, false);
                        $slaInfo['class'] = 'bg-danger';
                        $slaInfo['detail'] = 'Entregue com atraso de ' . max(1, $deltaFinish) . ' d';
                    }
                } else {
                    $nowDateTime = now();
                    $deltaSla = $nowDateTime->diffInDays($slaDueAt, false);
                    if ($deltaSla < 0) {
                        $slaInfo['class'] = 'bg-danger';
                        $slaInfo['detail'] = 'Vencido ha ' . max(1, abs($deltaSla)) . ' d';
                    } elseif ($deltaSla <= 3) {
                        if ($nowDateTime->isSameDay($slaDueAt)) {
                            $slaInfo['class'] = 'bg-warning text-danger';
                            $slaInfo['detail'] = 'Vence hoje';
                        } else {
                            $slaInfo['class'] = 'bg-warning text-dark';
                            $slaInfo['detail'] = 'Vence em ' . $deltaSla . ' d';
                        }
                    } else {
                        $slaInfo['class'] = 'bg-success';
                        $slaInfo['detail'] = 'Faltam ' . $deltaSla . ' d';
                    }
                }
            }

            $statusSlaLabel = $displayJob?->status_label ?? 'Não Despachado';
            $statusSlaClass = $displayJob?->status_badge_class ?? 'badge bg-secondary';

            return [
                'id' => $measure->id,
                'med_id' => $measure->med_id,
                'nota' => $measure->protest->nota ?? '---',
                'tipo_nota' => $measure->protest->tipoNota ?? '---',
                'codf' => $measure->protest->codecodf ?? '---',
                'tipo_reclamacao' => $measure->protest->txtGrpCodificacao ?? '---',
                'classificacao_reclamacao' => $measure->protest->type ?? '---',
                'is_btzero' => $this->isBtzeroMeasure($measure),
                'abertura_reclamacao' => $measure->protest?->dtAberturaNota?->format('d/m/Y') ?? '---',
                'abertura_medida' => $measure->dtCriacaoMedida?->format('d/m/Y') ?? '---',
                'desired_info' => $desiredInfo,
                'despachado_em' => $displayJob?->sent_at?->format('d/m/Y H:i') ?? '---',
                'sla_info' => $slaInfo,
                'sap_status' => $isMede ? 'ENC' : 'ABER',
                'sap_class' => $isMede ? 'bg-success' : 'bg-warning text-dark',
                'despachante' => $this->formatFirstAndLastName($displayJob?->creator?->name, 'Sem despachante'),
                'responsavel' => $this->formatFirstAndLastName($displayJob?->owner?->name, 'Sem responsável'),
                'has_dispatch' => $measure->ProtestJobs->isNotEmpty(),
                'status_sla_label' => $statusSlaLabel,
                'status_sla_class' => $statusSlaClass,
            ];
        }));

        return [
            'items' => $list,
            'total' => $totalWithoutDispatch + $totalDispatchedOpen,
            'total_without_dispatch' => $totalWithoutDispatch,
            'total_dispatched_open' => $totalDispatchedOpen,
            'note_type_counts' => $noteTypeCounts,
        ];
    }

    protected function buildMedaDueWindowSummaryCounts(): array
    {
        $base = $this->buildMedaOpenBaseQuery(applyDispatchFilter: true, applyDueWindowFilter: false);
        $this->applyMedaHistogramFilterOnOpenMeasures($base);

        $counts = [
            'overdue' => (clone $base),
            'due_soon' => (clone $base),
            'today' => (clone $base),
            'tomorrow' => (clone $base),
            'in_3_days' => (clone $base),
        ];

        $this->applyMedaDesiredDateWindowFilter($counts['overdue'], 'overdue');
        $this->applyMedaDesiredDateWindowFilter($counts['due_soon'], 'due_soon');
        $this->applyMedaDesiredDateWindowFilter($counts['today'], 'today');
        $this->applyMedaDesiredDateWindowFilter($counts['tomorrow'], 'tomorrow');
        $this->applyMedaDesiredDateWindowFilter($counts['in_3_days'], 'in_3_days');

        return [
            'overdue' => $counts['overdue']->count(),
            'due_soon' => $counts['due_soon']->count(),
            'today' => $counts['today']->count(),
            'tomorrow' => $counts['tomorrow']->count(),
            'in_3_days' => $counts['in_3_days']->count(),
            'selected' => $this->medaDueWindowFilter,
        ];
    }

    protected function buildMedaNoteTypeSummaryCounts(): array
    {
        $base = $this->buildMedaOpenBaseQuery(applyDispatchFilter: true, applyDueWindowFilter: false);
        $this->applyMedaHistogramFilterOnOpenMeasures($base);

        $counts = [
            'NA' => (clone $base),
            'OU' => (clone $base),
            'PR' => (clone $base),
        ];

        foreach ($counts as $key => $query) {
            $query->whereHas('protest', function ($protestQuery) use ($key) {
                $protestQuery->whereRaw('UPPER(COALESCE(tipoNota, "")) = ?', [$key]);
            });
        }

        return [
            'NA' => $counts['NA']->count(),
            'OU' => $counts['OU']->count(),
            'PR' => $counts['PR']->count(),
            'selected' => $this->medaOpenNoteTypeFilter,
        ];
    }

    protected function buildMedaDoneOpenByOwnerPanel(): array
    {
        $query = $this->buildMedaOpenBaseQuery(applyDispatchFilter: false)
            ->with([
                'protest:id,nota,tipoNota,codecodf,dtAberturaNota,dtConclusaoDesej,txtGrpCodificacao,cidade',
                'ProtestJobs' => function ($jobQuery) {
                    $jobQuery->with('creator:id,name')
                        ->orderByDesc('finished_at')
                        ->orderByDesc('id');
                },
            ])
            ->whereHas('ProtestJobs')
            ->whereDoesntHave('ProtestJobs', function ($jobQuery) {
                $jobQuery->where(function ($pending) {
                    $pending->whereNull('finished_at')
                        ->orWhere('status', '!=', ProtestJobStatus::DONE->value);
                });
            });

        $this->applyMedaHistogramFilterOnOpenMeasures($query);

        $measures = $query->get();
        $groups = [];
        $now = now();

        foreach ($measures as $measure) {
            $doneJob = $measure->ProtestJobs->first(function ($job) {
                return !is_null($job->finished_at)
                    && ($job->status === ProtestJobStatus::DONE || (string) $job->status === ProtestJobStatus::DONE->value);
            });

            $creatorId = trim((string) ($doneJob?->created_by ?? ''));
            $creatorName = $this->formatFirstAndLastName($doneJob?->creator?->name, 'Sem despachante');
            $groupKey = $creatorId !== '' ? 'u_' . $creatorId : 'u_0';
            $type = mb_strtoupper((string) ($measure->protest?->tipoNota ?? ''));
            $isOuPr = in_array($type, ['OU', 'PR'], true);
            $isOver24h = $doneJob?->finished_at
                ? $doneJob->finished_at->copy()->lte($now->copy()->subHours(24))
                : false;

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'creator_id' => $creatorId !== '' ? $creatorId : null,
                    'creator_name' => $creatorName,
                    'total' => 0,
                    'na' => 0,
                    'ou' => 0,
                    'pr' => 0,
                    'ou_pr_over24h' => 0,
                ];
            }

            $groups[$groupKey]['total']++;
            if ($type === 'NA') {
                $groups[$groupKey]['na']++;
            } elseif ($type === 'OU') {
                $groups[$groupKey]['ou']++;
            } elseif ($type === 'PR') {
                $groups[$groupKey]['pr']++;
            }

            if ($isOuPr && $isOver24h) {
                $groups[$groupKey]['ou_pr_over24h']++;
            }
        }

        $groups = collect($groups)->sortByDesc(function ($row) {
            return ($row['ou_pr_over24h'] * 100000) + $row['total'];
        })->values()->all();

        return [
            'groups' => $groups,
            'selected_creator_id' => $this->medaDoneOpenCreatorId,
            'total' => count($measures),
        ];
    }

    protected function buildGeneralProtestsList(Carbon $start, Carbon $end): array
    {
        $hasSearch = trim((string) $this->complaintSearch) !== '';

        $query = MedProtest::query()
            ->with([
                'protest:id,nota,tipoNota,codecodf,dtAberturaNota,dtConclusaoDesej,type,txtGrpCodificacao,cidade',
                'ProtestJobs' => function ($jobQuery) {
                    $jobQuery->with('owner:id,name')
                        ->orderByDesc('sent_at')
                        ->orderByDesc('id');
                },
            ]);

        $query = $this->applyMedProtestTypeFilter($query);
        $query = $this->applyComplaintFiltersToMedProtestQuery($query);

        if ($this->generalMeasuresOpenFilter === 'open') {
            $query->where('statusSist', 'MEDA');
        } elseif ($this->generalMeasuresOpenFilter === 'not_open') {
            $query->where(function ($q) {
                $q->where('statusSist', '!=', 'MEDA')
                    ->orWhereNull('statusSist');
            });
        }

        if ($this->generalMeasuresBtzeroFilter === 'with_btzero') {
            $query->identifiedAsBtzero();
        } elseif ($this->generalMeasuresBtzeroFilter === 'without_btzero') {
            $query->notIdentifiedAsBtzero();
        }

        if (!$hasSearch) {
            $rangeStart = $start->copy()->startOfDay();
            $rangeEnd = $end->copy()->endOfDay();

            $query->where(function ($scope) use ($rangeStart, $rangeEnd) {
                $scope->whereBetween('dtCriacaoMedida', [$rangeStart, $rangeEnd])
                    ->orWhereHas('protest', function ($protest) use ($rangeStart, $rangeEnd) {
                        $protest->whereBetween('dtAberturaNota', [$rangeStart, $rangeEnd]);
                    })
                    ->orWhereHas('ProtestJobs', function ($jobQuery) use ($rangeStart, $rangeEnd) {
                        $jobQuery->where('confirmed', '!=', true)->where(function ($job) use ($rangeStart, $rangeEnd) {
                            $job->whereBetween('sent_at', [$rangeStart, $rangeEnd])
                                ->orWhereBetween('finished_at', [$rangeStart, $rangeEnd]);
                        });
                    })
                    ->orWhereHas('protest.ProtestJobs', function ($job) use ($rangeStart, $rangeEnd) {
                        $job->where(function ($dateScope) use ($rangeStart, $rangeEnd) {
                            $dateScope->whereBetween('sent_at', [$rangeStart, $rangeEnd])
                                ->orWhereBetween('finished_at', [$rangeStart, $rangeEnd]);
                        });
                    });
            });
        }

        $list = $query
            ->orderByRaw("
                CASE
                    WHEN statusSist = 'MEDE' THEN 0
                    WHEN statusSist = 'MEDA' THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('dtFimMedida')
            ->orderBy('dtFimMedidaDesej')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'general_protests_page');

        $list->setCollection($list->getCollection()->map(function (MedProtest $measure) {
            $desiredAt = $this->resolveMeasureDesiredDate($measure);
            $statusSist = mb_strtoupper((string) ($measure->statusSist ?? ''));
            $isMede = $statusSist === 'MEDE';

            $desiredInfo = [
                'date' => $desiredAt?->format('d/m/Y') ?? '---',
                'class' => 'bg-secondary',
                'detail' => 'Sem data desejada',
            ];

            if ($desiredAt) {
                if ($isMede && $measure->dtFimMedida) {
                    $finishedAt = $measure->dtFimMedida->copy()->startOfDay();
                    $onTime = $finishedAt->lte($desiredAt->copy()->startOfDay());
                    $desiredInfo = [
                        'date' => $desiredAt->format('d/m/Y'),
                        'class' => $onTime ? 'bg-success' : 'bg-danger',
                        'detail' => 'Encerrado em ' . $measure->dtFimMedida->format('d/m/Y'),
                    ];
                } else {
                    $deltaDesired = now()->startOfDay()->diffInDays($desiredAt->copy()->startOfDay(), false);
                    if ($deltaDesired < 0) {
                        $desiredInfo['class'] = 'bg-danger';
                        $desiredInfo['detail'] = 'Vencido ha ' . abs($deltaDesired) . ' d';
                    } elseif ($deltaDesired === 0) {
                        $desiredInfo['class'] = 'bg-warning text-dark';
                        $desiredInfo['detail'] = 'Vence hoje';
                    } else {
                        $desiredInfo['class'] = 'bg-success';
                        $desiredInfo['detail'] = 'Faltam ' . $deltaDesired . ' d';
                    }
                }
            }

            $pendingJob = $measure->ProtestJobs->firstWhere('confirmed', '!=', true);

            return [
                'med_id' => $measure->med_id,
                'nota' => $measure->protest->nota ?? '---',
                'tipo_nota' => $measure->protest->tipoNota ?? '---',
                'codf' => $measure->protest->codecodf ?? '---',
                'tipo_reclamacao' => $measure->protest->txtGrpCodificacao ?? '---',
                'classificacao_reclamacao' => $measure->protest->type ?? '---',
                'is_btzero' => $this->isBtzeroMeasure($measure),
                'abertura_reclamacao' => $measure->protest?->dtAberturaNota?->format('d/m/Y') ?? '---',
                'abertura_medida' => $measure->dtCriacaoMedida?->format('d/m/Y') ?? '---',
                'desired_info' => $desiredInfo,
                'sap_status' => $isMede ? 'ENC' : 'ABER',
                'sap_class' => $isMede ? 'bg-success' : 'bg-warning text-dark',
                'responsavel' => $this->formatFirstAndLastName($pendingJob?->owner?->name, 'Sem responsável'),
                'resultado' => $measure->result ?? '---',
            ];
        }));

        return [
            'items' => $list,
            'total' => $list->total(),
            'search_mode' => $hasSearch,
            'period_label' => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
        ];
    }

    protected function buildComplaintsNaPanel(Carbon $start, Carbon $end): array
    {
        $windowStart = $start->copy()->startOfMonth();
        $windowEnd = $end->copy()->endOfMonth();

        $monthKeys = [];
        $monthLabels = [];
        $cursor = $windowStart->copy();
        $ptMonths = [1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'];
        while ($cursor->lte($windowEnd)) {
            $key = $cursor->format('Y-m');
            $monthKeys[] = $key;
            $monthLabels[] = ($ptMonths[(int) $cursor->format('n')] ?? $cursor->format('m')) . '-' . $cursor->format('y');
            $cursor->addMonth();
        }

        $insideByMonth = array_fill_keys($monthKeys, 0);
        $outsideByMonth = array_fill_keys($monthKeys, 0);
        $procedenteByMonth = array_fill_keys($monthKeys, 0);
        $improcedenteByMonth = array_fill_keys($monthKeys, 0);

        $protests = Protest::query()
            ->where('tipoNota', 'NA')
            ->whereIn('statUsuar', ['ENCI', 'ENCP'])
            ->whereNotNull('dtConclusaoDesej')
            ->whereBetween('dtConclusaoDesej', [$windowStart, $windowEnd])
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->tap(fn ($q) => $this->applyComplaintsCipUniverseFilter($q))
            ->with([
                'medProtests' => function ($q) {
                    $q->select('id', 'protest_id', 'protest_type', 'dtCriacaoMedida', 'dtFimMedida')
                        ->where('protest_type', ProtestType::CIP->value)
                        ->orderByDesc('dtCriacaoMedida')
                        ->orderByDesc('id');
                },
            ])
            ->get();

        foreach ($protests as $protest) {
            $monthKey = $protest->dtConclusaoDesej?->format('Y-m');
            if (!$monthKey || !array_key_exists($monthKey, $insideByMonth)) {
                continue;
            }

            if ((string) $protest->statUsuar === 'ENCP') {
                $procedenteByMonth[$monthKey]++;
            } else {
                $improcedenteByMonth[$monthKey]++;
            }

            $latestMeasure = $protest->medProtests->first();
            $finishedAt = $latestMeasure?->dtFimMedida;
            if (!$finishedAt) {
                continue;
            }

            if ($finishedAt->lte($protest->dtConclusaoDesej)) {
                $insideByMonth[$monthKey]++;
            } else {
                $outsideByMonth[$monthKey]++;
            }
        }

        $insideCounts = [];
        $outsideCounts = [];
        $totalCounts = [];
        $procedenteCounts = [];
        $improcedenteCounts = [];
        $procedencyTotals = [];
        $outsidePct = [];
        $procedentePct = [];

        foreach ($monthKeys as $key) {
            $inside = (int) ($insideByMonth[$key] ?? 0);
            $outside = (int) ($outsideByMonth[$key] ?? 0);
            $proc = (int) ($procedenteByMonth[$key] ?? 0);
            $improc = (int) ($improcedenteByMonth[$key] ?? 0);
            $totalPrazo = max($inside + $outside, 0);

            $insideCounts[] = $inside;
            $outsideCounts[] = $outside;
            $totalCounts[] = $inside + $outside;
            $procedenteCounts[] = $proc;
            $improcedenteCounts[] = $improc;
            $procedencyTotals[] = $proc + $improc;
            $outsidePct[] = $totalPrazo > 0 ? round(($outside / $totalPrazo) * 100, 1) : 0;
            $procedentePct[] = ($proc + $improc) > 0 ? round(($proc / ($proc + $improc)) * 100, 1) : 0;
        }

        $metaCutoff = Carbon::create(2026, 1, 1)->startOfMonth();
        $metaPct = array_map(function (string $key) use ($metaCutoff) {
            $monthRef = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
            return $monthRef->lt($metaCutoff) ? 3.0 : 0.5;
        }, $monthKeys);
        $metaProcedentePct = array_fill(0, count($monthKeys), 85.0);

        $outsideMax = max($outsideCounts ?: [0]);
        $procedenteMax = max($procedenteCounts ?: [0]);
        $outsideAxisMax = max(5, (int) ceil($outsideMax * 1.25));
        $procedenteAxisMax = max(5, (int) ceil($procedenteMax * 1.25));
        $outsidePctMax = max(array_merge($outsidePct ?: [0], $metaPct ?: [0]));
        $outsidePctAxisMax = max(1, (float) ceil(($outsidePctMax * 1.35) * 10) / 10);
        $procedentePctMax = max(array_merge($procedentePct ?: [0], $metaProcedentePct ?: [0]));
        $procedentePctAxisMax = max(16, (float) ceil(($procedentePctMax * 1.1) * 10) / 10);

        return [
            'window_label' => $windowStart->format('m/Y') . ' - ' . $windowEnd->format('m/Y'),
            'total' => array_sum($insideCounts) + array_sum($outsideCounts),
            'btzero_filter' => $this->complaintsBtzeroFilter,
            'charts' => [
                'sla_stack' => [
                    'type' => 'bar',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Fora do prazo',
                                'data' => $outsideCounts,
                                'backgroundColor' => 'rgba(15,23,42,0.85)',
                                'borderColor' => '#0f172a',
                                'borderWidth' => 1,
                                'datalabels' => [
                                    'display' => true,
                                    'labels' => [
                                        'inside' => [
                                            'display' => true,
                                            'anchor' => 'center',
                                            'align' => 'center',
                                            'color' => '#ffffff',
                                            'font' => ['weight' => 'bold', 'size' => 11],
                                            'formatter' => '__VALUE_LABEL__',
                                        ],
                                        'total' => [
                                            'display' => true,
                                            'anchor' => 'end',
                                            'align' => 'top',
                                            'offset' => 4,
                                            'color' => '#1f2937',
                                            'font' => ['weight' => 'bold', 'size' => 12],
                                            'formatter' => '__TOTAL_FROM_SERIES__',
                                            'totalSeries' => $totalCounts,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'NA - Encerramentos dentro x fora do prazo'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'x' => [
                                'grid' => ['display' => false],
                            ],
                            'y' => [
                                'beginAtZero' => true,
                                'max' => $outsideAxisMax,
                                'ticks' => ['precision' => 0],
                                'grid' => ['display' => false],
                            ],
                        ],
                    ],
                ],
                'sla_line' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Meta % fora do prazo',
                                'data' => $metaPct,
                                'borderColor' => '#7c3aed',
                                'backgroundColor' => 'rgba(124,58,237,0.15)',
                                'tension' => 0,
                                'fill' => false,
                                'borderDash' => [8, 5],
                                'pointRadius' => 0,
                                'pointHoverRadius' => 0,
                                'datalabels' => [
                                    'display' => false,
                                ],
                            ],
                            [
                                'label' => '% Fora do prazo',
                                'data' => $outsidePct,
                                'borderColor' => '#0f766e',
                                'backgroundColor' => 'rgba(15,118,110,0.2)',
                                'tension' => 0,
                                'fill' => false,
                                'pointRadius' => 4,
                                'pointHoverRadius' => 5,
                                'datalabels' => [
                                    'display' => true,
                                    'anchor' => 'end',
                                    'align' => 'top',
                                    'offset' => 4,
                                    'color' => '#0f766e',
                                    'font' => ['weight' => 'bold', 'size' => 11],
                                    'formatter' => '__PERCENT_LABEL__',
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'NA - Percentual mensal dentro x fora do prazo'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'y' => [
                                'beginAtZero' => true,
                                'max' => $outsidePctAxisMax,
                                'grid' => ['display' => false],
                            ],
                            'x' => [
                                'grid' => ['display' => false],
                            ],
                        ],
                    ],
                ],
                'procedency_stack' => [
                    'type' => 'bar',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Procedente (ENCP)',
                                'data' => $procedenteCounts,
                                'backgroundColor' => 'rgba(30,41,59,0.82)',
                                'borderColor' => '#0f172a',
                                'borderWidth' => 1,
                                'datalabels' => [
                                    'display' => true,
                                    'labels' => [
                                        'inside' => [
                                            'display' => true,
                                            'anchor' => 'center',
                                            'align' => 'center',
                                            'color' => '#ffffff',
                                            'font' => ['weight' => 'bold', 'size' => 11],
                                            'formatter' => '__VALUE_LABEL__',
                                        ],
                                        'total' => [
                                            'display' => true,
                                            'anchor' => 'end',
                                            'align' => 'top',
                                            'offset' => 4,
                                            'color' => '#1f2937',
                                            'font' => ['weight' => 'bold', 'size' => 12],
                                            'formatter' => '__TOTAL_FROM_SERIES__',
                                            'totalSeries' => $procedencyTotals,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'NA - Entradas procedentes (total no topo)'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'x' => ['grid' => ['display' => false]],
                            'y' => ['beginAtZero' => true, 'max' => $procedenteAxisMax, 'ticks' => ['precision' => 0], 'grid' => ['display' => false]],
                        ],
                    ],
                ],
                'procedency_line' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Meta % procedente',
                                'data' => $metaProcedentePct,
                                'borderColor' => '#7c3aed',
                                'backgroundColor' => 'rgba(124,58,237,0.15)',
                                'tension' => 0,
                                'fill' => false,
                                'borderDash' => [8, 5],
                                'pointRadius' => 0,
                                'pointHoverRadius' => 0,
                                'datalabels' => [
                                    'display' => false,
                                ],
                            ],
                            [
                                'label' => '% Procedente',
                                'data' => $procedentePct,
                                'borderColor' => '#0f172a',
                                'backgroundColor' => 'rgba(15,23,42,0.2)',
                                'tension' => 0,
                                'fill' => false,
                                'pointRadius' => 4,
                                'pointHoverRadius' => 5,
                                'datalabels' => [
                                    'display' => true,
                                    'anchor' => 'end',
                                    'align' => 'top',
                                    'offset' => 4,
                                    'color' => '#0f172a',
                                    'font' => ['weight' => 'bold', 'size' => 11],
                                    'formatter' => '__PERCENT_LABEL__',
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'NA - Percentual mensal de procedente'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'y' => [
                                'beginAtZero' => true,
                                'max' => $procedentePctAxisMax,
                                'grid' => ['display' => false],
                            ],
                            'x' => [
                                'grid' => ['display' => false],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildComplaintsOuPanel(Carbon $start, Carbon $end): array
    {
        $windowStart = $start->copy()->startOfMonth();
        $windowEnd = $end->copy()->endOfMonth();

        $monthKeys = [];
        $monthLabels = [];
        $cursor = $windowStart->copy();
        $ptMonths = [1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr', 5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'];
        while ($cursor->lte($windowEnd)) {
            $key = $cursor->format('Y-m');
            $monthKeys[] = $key;
            $monthLabels[] = ($ptMonths[(int) $cursor->format('n')] ?? $cursor->format('m')) . '-' . $cursor->format('y');
            $cursor->addMonth();
        }

        $insideByMonth = array_fill_keys($monthKeys, 0);
        $outsideByMonth = array_fill_keys($monthKeys, 0);
        $procedenteByMonth = array_fill_keys($monthKeys, 0);
        $improcedenteByMonth = array_fill_keys($monthKeys, 0);

        $normalizeResult = function (?string $value): string {
            return Str::of((string) $value)
                ->lower()
                ->ascii()
                ->replace(['-', '_'], ' ')
                ->squish()
                ->value();
        };

        $protests = Protest::query()
            ->where('tipoNota', 'OU')
            ->whereNotNull('dtConclusaoDesej')
            ->whereBetween('dtConclusaoDesej', [$windowStart, $windowEnd])
            ->tap(fn ($q) => $this->applyComplaintFiltersToProtestQuery($q))
            ->tap(fn ($q) => $this->applyProtestTypeFilter($q))
            ->tap(fn ($q) => $this->applyComplaintsCipUniverseFilter($q))
            ->with([
                'medProtests' => function ($q) {
                    $q->select('id', 'protest_id', 'protest_type', 'dtCriacaoMedida', 'dtFimMedida', 'dtFimMedidaDesej', 'result')
                        ->where('protest_type', ProtestType::CIP->value)
                        ->orderByDesc('dtCriacaoMedida')
                        ->orderByDesc('id');
                },
            ])
            ->get();

        foreach ($protests as $protest) {
            $monthKey = $protest->dtConclusaoDesej?->format('Y-m');
            if (!$monthKey || !array_key_exists($monthKey, $insideByMonth)) {
                continue;
            }

            $measures = $protest->medProtests ?? collect();

            $hasAnyResult = false;
            $hasProcedenteResult = false;
            foreach ($measures as $measure) {
                $normalized = $normalizeResult($measure->result);
                if ($normalized === '') {
                    continue;
                }
                $hasAnyResult = true;
                if (str_contains($normalized, 'procedente') && !str_contains($normalized, 'improcedente')) {
                    $hasProcedenteResult = true;
                    break;
                }
            }

            if ($hasAnyResult) {
                if ($hasProcedenteResult) {
                    $procedenteByMonth[$monthKey]++;
                } else {
                    $improcedenteByMonth[$monthKey]++;
                }
            } else {
                if ((string) $protest->statUsuar === 'ENCP') {
                    $procedenteByMonth[$monthKey]++;
                } else {
                    $improcedenteByMonth[$monthKey]++;
                }
            }

            $isOutside = false;
            $hasComparableMeasure = false;
            foreach ($measures as $measure) {
                if (!$measure->dtFimMedida || !$measure->dtFimMedidaDesej) {
                    continue;
                }
                $hasComparableMeasure = true;
                if ($measure->dtFimMedida->gt($measure->dtFimMedidaDesej)) {
                    $isOutside = true;
                    break;
                }
            }

            if (!$hasComparableMeasure) {
                $latestMeasure = $measures->first();
                if ($latestMeasure?->dtFimMedida && $protest->dtConclusaoDesej) {
                    $hasComparableMeasure = true;
                    $isOutside = $latestMeasure->dtFimMedida->gt($protest->dtConclusaoDesej);
                }
            }

            if (!$hasComparableMeasure) {
                continue;
            }

            if ($isOutside) {
                $outsideByMonth[$monthKey]++;
            } else {
                $insideByMonth[$monthKey]++;
            }
        }

        $insideCounts = [];
        $outsideCounts = [];
        $totalCounts = [];
        $procedenteCounts = [];
        $improcedenteCounts = [];
        $procedencyTotals = [];
        $outsidePct = [];
        $procedentePct = [];

        foreach ($monthKeys as $key) {
            $inside = (int) ($insideByMonth[$key] ?? 0);
            $outside = (int) ($outsideByMonth[$key] ?? 0);
            $proc = (int) ($procedenteByMonth[$key] ?? 0);
            $improc = (int) ($improcedenteByMonth[$key] ?? 0);
            $totalPrazo = max($inside + $outside, 0);

            $insideCounts[] = $inside;
            $outsideCounts[] = $outside;
            $totalCounts[] = $inside + $outside;
            $procedenteCounts[] = $proc;
            $improcedenteCounts[] = $improc;
            $procedencyTotals[] = $proc + $improc;
            $outsidePct[] = $totalPrazo > 0 ? round(($outside / $totalPrazo) * 100, 1) : 0;
            $procedentePct[] = ($proc + $improc) > 0 ? round(($proc / ($proc + $improc)) * 100, 1) : 0;
        }

        $metaCutoff = Carbon::create(2026, 1, 1)->startOfMonth();
        $metaPct = array_map(function (string $key) use ($metaCutoff) {
            $monthRef = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
            return $monthRef->lt($metaCutoff) ? 3.0 : 0.5;
        }, $monthKeys);
        $metaProcedentePct = array_fill(0, count($monthKeys), 85.0);

        $outsideMax = max($outsideCounts ?: [0]);
        $procedenteMax = max($procedenteCounts ?: [0]);
        $outsideAxisMax = max(5, (int) ceil($outsideMax * 1.25));
        $procedenteAxisMax = max(5, (int) ceil($procedenteMax * 1.25));
        $outsidePctMax = max(array_merge($outsidePct ?: [0], $metaPct ?: [0]));
        $outsidePctAxisMax = max(1, (float) ceil(($outsidePctMax * 1.35) * 10) / 10);
        $procedentePctMax = max(array_merge($procedentePct ?: [0], $metaProcedentePct ?: [0]));
        $procedentePctAxisMax = max(16, (float) ceil(($procedentePctMax * 1.1) * 10) / 10);

        return [
            'window_label' => $windowStart->format('m/Y') . ' - ' . $windowEnd->format('m/Y'),
            'total' => array_sum($insideCounts) + array_sum($outsideCounts),
            'btzero_filter' => $this->complaintsBtzeroFilter,
            'charts' => [
                'sla_stack' => [
                    'type' => 'bar',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Fora do prazo',
                                'data' => $outsideCounts,
                                'backgroundColor' => 'rgba(15,23,42,0.85)',
                                'borderColor' => '#0f172a',
                                'borderWidth' => 1,
                                'datalabels' => [
                                    'display' => true,
                                    'labels' => [
                                        'inside' => [
                                            'display' => true,
                                            'anchor' => 'center',
                                            'align' => 'center',
                                            'color' => '#ffffff',
                                            'font' => ['weight' => 'bold', 'size' => 11],
                                            'formatter' => '__VALUE_LABEL__',
                                        ],
                                        'total' => [
                                            'display' => true,
                                            'anchor' => 'end',
                                            'align' => 'top',
                                            'offset' => 4,
                                            'color' => '#1f2937',
                                            'font' => ['weight' => 'bold', 'size' => 12],
                                            'formatter' => '__TOTAL_FROM_SERIES__',
                                            'totalSeries' => $totalCounts,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'OU - Encerramentos dentro x fora do prazo'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'x' => [
                                'grid' => ['display' => false],
                            ],
                            'y' => [
                                'beginAtZero' => true,
                                'max' => $outsideAxisMax,
                                'ticks' => ['precision' => 0],
                                'grid' => ['display' => false],
                            ],
                        ],
                    ],
                ],
                'sla_line' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Meta % fora do prazo',
                                'data' => $metaPct,
                                'borderColor' => '#7c3aed',
                                'backgroundColor' => 'rgba(124,58,237,0.15)',
                                'tension' => 0,
                                'fill' => false,
                                'borderDash' => [8, 5],
                                'pointRadius' => 0,
                                'pointHoverRadius' => 0,
                                'datalabels' => [
                                    'display' => false,
                                ],
                            ],
                            [
                                'label' => '% Fora do prazo',
                                'data' => $outsidePct,
                                'borderColor' => '#0f766e',
                                'backgroundColor' => 'rgba(15,118,110,0.2)',
                                'tension' => 0,
                                'fill' => false,
                                'pointRadius' => 4,
                                'pointHoverRadius' => 5,
                                'datalabels' => [
                                    'display' => true,
                                    'anchor' => 'end',
                                    'align' => 'top',
                                    'offset' => 4,
                                    'color' => '#0f766e',
                                    'font' => ['weight' => 'bold', 'size' => 11],
                                    'formatter' => '__PERCENT_LABEL__',
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'OU - Percentual mensal dentro x fora do prazo'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'y' => [
                                'beginAtZero' => true,
                                'max' => $outsidePctAxisMax,
                                'grid' => ['display' => false],
                            ],
                            'x' => [
                                'grid' => ['display' => false],
                            ],
                        ],
                    ],
                ],
                'procedency_stack' => [
                    'type' => 'bar',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Procedente',
                                'data' => $procedenteCounts,
                                'backgroundColor' => 'rgba(30,41,59,0.82)',
                                'borderColor' => '#0f172a',
                                'borderWidth' => 1,
                                'datalabels' => [
                                    'display' => true,
                                    'labels' => [
                                        'inside' => [
                                            'display' => true,
                                            'anchor' => 'center',
                                            'align' => 'center',
                                            'color' => '#ffffff',
                                            'font' => ['weight' => 'bold', 'size' => 11],
                                            'formatter' => '__VALUE_LABEL__',
                                        ],
                                        'total' => [
                                            'display' => true,
                                            'anchor' => 'end',
                                            'align' => 'top',
                                            'offset' => 4,
                                            'color' => '#1f2937',
                                            'font' => ['weight' => 'bold', 'size' => 12],
                                            'formatter' => '__TOTAL_FROM_SERIES__',
                                            'totalSeries' => $procedencyTotals,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'OU - Entradas procedentes (total no topo)'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'x' => ['grid' => ['display' => false]],
                            'y' => ['beginAtZero' => true, 'max' => $procedenteAxisMax, 'ticks' => ['precision' => 0], 'grid' => ['display' => false]],
                        ],
                    ],
                ],
                'procedency_line' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $monthLabels,
                        'datasets' => [
                            [
                                'label' => 'Meta % procedente',
                                'data' => $metaProcedentePct,
                                'borderColor' => '#7c3aed',
                                'backgroundColor' => 'rgba(124,58,237,0.15)',
                                'tension' => 0,
                                'fill' => false,
                                'borderDash' => [8, 5],
                                'pointRadius' => 0,
                                'pointHoverRadius' => 0,
                                'datalabels' => [
                                    'display' => false,
                                ],
                            ],
                            [
                                'label' => '% Procedente',
                                'data' => $procedentePct,
                                'borderColor' => '#0f172a',
                                'backgroundColor' => 'rgba(15,23,42,0.2)',
                                'tension' => 0,
                                'fill' => false,
                                'pointRadius' => 4,
                                'pointHoverRadius' => 5,
                                'datalabels' => [
                                    'display' => true,
                                    'anchor' => 'end',
                                    'align' => 'top',
                                    'offset' => 4,
                                    'color' => '#0f172a',
                                    'font' => ['weight' => 'bold', 'size' => 11],
                                    'formatter' => '__PERCENT_LABEL__',
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'plugins' => [
                            'legend' => ['position' => 'top'],
                            'title' => ['display' => true, 'text' => 'OU - Percentual mensal de procedente'],
                            'datalabels' => [
                                'display' => true,
                            ],
                        ],
                        'scales' => [
                            'y' => [
                                'beginAtZero' => true,
                                'max' => $procedentePctAxisMax,
                                'grid' => ['display' => false],
                            ],
                            'x' => [
                                'grid' => ['display' => false],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function resolveMeasureDesiredDate(MedProtest $measure): ?Carbon
    {
        $isNa = mb_strtoupper((string) ($measure->protest?->tipoNota ?? '')) === 'NA';
        $desired = $isNa
            ? $measure->protest?->dtConclusaoDesej
            : $measure->dtFimMedidaDesej;

        return $desired ? $desired->copy()->startOfDay() : null;
    }

    protected function isBtzeroMeasure(MedProtest $measure): bool
    {
        $normalize = function (?string $value): string {
            return Str::of((string) $value)->lower()->replace(['-', ' '], '')->value();
        };

        $rawProtestType = $measure->protest_type;
        $protestType = $rawProtestType instanceof ProtestType
            ? $rawProtestType->value
            : (int) ($rawProtestType ?? 0);
        $txtCodMedida = $normalize($measure->txtCodMedida ?? '');
        $txtGrpCodificacao = $normalize($measure->protest?->txtGrpCodificacao ?? '');

        return $protestType === ProtestType::BTZERO->value
            || str_contains($txtCodMedida, 'btzero')
            || str_contains($txtGrpCodificacao, 'btzero');
    }

    protected function buildDailyDispatchCompletionChart(Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd   = $end->copy()->endOfDay();

        $dispatchRows = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'sent_at')
            ->whereNotNull('protest_jobs.sent_at')
            ->selectRaw('DATE(protest_jobs.sent_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $completionRows = $this->jobsBaseQuery($rangeStart, $rangeEnd, 'finished_at')
            ->whereNotNull('protest_jobs.finished_at')
            ->selectRaw('DATE(protest_jobs.finished_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels           = [];
        $dispatchSeries   = [];
        $completionSeries = [];

        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $dispatchSeries[]   = (int)($dispatchRows[$key] ?? 0);
            $completionSeries[] = (int)($completionRows[$key] ?? 0);
            $cursor->addDay();
        }

        $points = max(count($labels), 1);
        $avgDispatch   = $points > 0 ? round(array_sum($dispatchSeries) / $points, 2) : 0;
        $avgCompletion = $points > 0 ? round(array_sum($completionSeries) / $points, 2) : 0;

        return [
            'type' => 'line',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'type'            => 'line',
                        'label'           => 'Despachos diários',
                        'data'            => $dispatchSeries,
                        'backgroundColor' => 'rgba(59,130,246,0.25)',
                        'borderColor'     => '#2563eb',
                        'borderWidth'     => 3,
                        'tension'         => 0.25,
                        'fill'            => true,
                    ],
                    [
                        'type'            => 'line',
                        'label'           => 'Conclusões diárias',
                        'data'            => $completionSeries,
                        'backgroundColor' => 'rgba(220,38,38,0.25)',
                        'borderColor'     => '#dc2626',
                        'borderWidth'     => 3,
                        'tension'         => 0.25,
                        'fill'            => true,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média despachos',
                        'data'        => array_fill(0, count($labels), $avgDispatch),
                        'borderColor' => '#1d4ed8',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                    [
                        'type'        => 'line',
                        'label'       => 'Média conclusões',
                        'data'        => array_fill(0, count($labels), $avgCompletion),
                        'borderColor' => '#b91c1c',
                        'borderWidth' => 2,
                        'borderDash'  => [6, 4],
                        'pointRadius' => 0,
                        'fill'        => false,
                    ],
                ],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend' => ['position' => 'top'],
                    'title'  => [
                        'display' => true,
                        'text'    => 'Despachos x Conclusões por dia',
                    ],
                    'tooltip' => [
                        'mode' => 'index',
                        'intersect' => false,
                    ],
                ],
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title'       => [
                            'display' => true,
                            'text'    => 'Quantidade de Atividades de Reclamação',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function dispatchDailyOpeningsChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-dailyOpenings', $chart);
        $this->dispatchBrowserEvent('grafico-atualizar-aberturas-diarias', $chart);
    }

    protected function dispatchMedaJobsChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-medaJobs', $chart);
    }

    protected function dispatchMedaOpenDesiredChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-medaOpenDesiredHistogram', $chart);
    }

    protected function dispatchDailyDispatchCompletionChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-dailyDispatchCompletion', $chart);
    }

    protected function dispatchComplaintsNaCharts(array $charts): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsNaSlaStack', $charts['sla_stack'] ?? []);
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsNaSlaLine', $charts['sla_line'] ?? []);
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsNaProcedencyStack', $charts['procedency_stack'] ?? []);
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsNaProcedencyLine', $charts['procedency_line'] ?? []);
    }

    protected function dispatchComplaintsOuCharts(array $charts): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsOuSlaStack', $charts['sla_stack'] ?? []);
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsOuSlaLine', $charts['sla_line'] ?? []);
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsOuProcedencyStack', $charts['procedency_stack'] ?? []);
        $this->dispatchBrowserEvent('grafico-atualizar-complaintsOuProcedencyLine', $charts['procedency_line'] ?? []);
    }

    protected function dispatchProtestTypeDonutChart(array $chart): void
    {
        $this->dispatchBrowserEvent('grafico-atualizar-protestTypeDonut', $chart);
    }

    protected function buildProtestTypeDonut(Carbon $start, Carbon $end): array
    {
        $rows = $this->jobsBaseQuery($start, $end, 'sent_at')
            ->whereNotNull('protest_jobs.sent_at')
            ->leftJoin('protests', 'protests.id', '=', 'protest_jobs.protest_id')
            ->selectRaw('COALESCE(NULLIF(TRIM(protests.type), ""), "Sem classificacao") as protest_type_label')
            ->selectRaw('COUNT(DISTINCT protest_jobs.protest_id) as total')
            ->groupBy('protest_type_label')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->pluck('protest_type_label')->map(fn ($value) => (string) $value)->toArray();
        $series = $rows->pluck('total')->map(fn ($value) => (int) $value)->toArray();
        $total = array_sum($series);

        $palette = [
            'rgba(37,99,235,.45)',
            'rgba(16,185,129,.45)',
            'rgba(245,158,11,.45)',
            'rgba(239,68,68,.45)',
            'rgba(139,92,246,.45)',
            'rgba(14,165,233,.45)',
            'rgba(20,184,166,.45)',
            'rgba(99,102,241,.45)',
        ];

        $borders = [
            '#2563eb',
            '#10b981',
            '#f59e0b',
            '#ef4444',
            '#8b5cf6',
            '#0ea5e9',
            '#14b8a6',
            '#6366f1',
        ];

        return [
            'total' => $total,
            'rows' => $rows->map(fn ($row) => [
                'label' => (string) $row->protest_type_label,
                'total' => (int) $row->total,
            ])->toArray(),
            'chart' => [
                'type' => 'doughnut',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Protestos por classificacao',
                        'data' => $series,
                        'backgroundColor' => array_map(
                            fn ($i) => $palette[$i % count($palette)],
                            array_keys($series)
                        ),
                        'borderColor' => array_map(
                            fn ($i) => $borders[$i % count($borders)],
                            array_keys($series)
                        ),
                        'borderWidth' => 1,
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => true,
                    'aspectRatio' => 1,
                    'plugins' => [
                        'legend' => ['position' => 'right'],
                        'title' => [
                            'display' => true,
                            'text' => 'Distribuicao por protest.type',
                        ],
                    ],
                    'cutout' => '58%',
                ],
            ],
        ];
    }

    protected function buildJobSlaList(Carbon $start, Carbon $end): array
    {
        $base = $this->jobsBaseQuery($start, $end);

        $rows = (clone $base)
            ->leftJoin('protests', 'protests.id', '=', 'protest_jobs.protest_id')
            ->leftJoin('med_protests', 'med_protests.id', '=', 'protest_jobs.med_protest_id')
            ->whereNotNull('protest_jobs.sla_due_at')
            ->select([
                'protest_jobs.id',
                'protests.nota as protest_number',
                'med_protests.med_id as med_id',
                'med_protests.dtFimMedidaDesej as med_sla_due',
                'protest_jobs.sla_due_at',
                'protest_jobs.finished_at',
                'protest_jobs.sla_breached_at',
            ])
            ->orderByDesc('protest_jobs.sla_due_at')
            ->limit(50)
            ->get();

        return $rows->map(function ($row) {
            $slaDue    = $row->sla_due_at ? Carbon::parse($row->sla_due_at) : null;
            $medSlaDue = $row->med_sla_due ? Carbon::parse($row->med_sla_due) : null;
            $finished  = $row->finished_at ? Carbon::parse($row->finished_at) : null;
            $reference = $finished ?? now();
            $diffSeconds = $medSlaDue ? $reference->diffInSeconds($medSlaDue, false) : null;

            $isBreached = $diffSeconds !== null && $diffSeconds > 0;

            $statusLabel = $isBreached
                ? 'Fora do prazo'
                : ($finished ? 'Dentro do prazo' : 'Em andamento');

            $statusBadge = $isBreached
                ? 'bg-danger'
                : ($finished ? 'bg-success' : 'bg-secondary');

            $deltaLabel = null;
            if ($diffSeconds !== null) {
                if ($diffSeconds > 0) {
                    $deltaLabel = '+' . $this->secondsToHuman($diffSeconds) . ' de atraso';
                } elseif ($diffSeconds < 0) {
                    $deltaLabel = '-' . $this->secondsToHuman(abs($diffSeconds)) . ' restante';
                } else {
                    $deltaLabel = 'No prazo';
                }
            }

            return [
                'job_id'          => $row->id,
                'protest_number'  => $row->protest_number ?? 'N/A',
                'med_id'          => $row->med_id ?? 'N/A',
                'med_sla_due_at'  => $medSlaDue ? $medSlaDue->format('d/m/Y H:i') : 'N/A',
                'sla_due_at'      => $slaDue ? $slaDue->format('d/m/Y H:i') : 'N/A',
                'finished_at'     => $finished ? $finished->format('d/m/Y H:i') : 'Em aberto',
                'status_label'    => $statusLabel,
                'status_badge'    => $statusBadge,
                'delta_label'     => $deltaLabel,
            ];
        })->toArray();
    }

    protected function buildDueMeasures(): array
    {
        $base = MedProtest::query()
            ->with(['protest:id,nota'])
            ->where('statusSist', 'MEDA')
            ->whereNotNull('dtFimMedidaDesej');

        $base = $this->applyMedProtestTypeFilter($base);
        $base = $this->applyComplaintFiltersToMedProtestQuery($base);

        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();
        $perPage    = 10;

        $dueTodayQuery = (clone $base)
            ->whereBetween('dtFimMedidaDesej', [$todayStart, $todayEnd])
            ->orderBy('dtFimMedidaDesej');

        $overdueQuery = (clone $base)
            ->where('dtFimMedidaDesej', '<', $todayStart)
            ->orderBy('dtFimMedidaDesej');

        $dueToday = $dueTodayQuery->paginate($perPage, ['*'], 'due_today_page');
        $overdue  = $overdueQuery->paginate($perPage, ['*'], 'overdue_page');

        $dueToday->setCollection($this->transformDueMeasures($dueToday->getCollection()));
        $overdue->setCollection($this->transformDueMeasures($overdue->getCollection()));

        return [
            'due_today' => $dueToday,
            'overdue'   => $overdue,
        ];
    }

    protected function transformDueMeasures($collection)
    {
        return $collection->map(function (MedProtest $measure) {
            return [
                'protest_id'         => $measure->protest_id,
                'protest_number'     => $measure->protest->nota ?? 'N/A',
                'med_id'             => $measure->med_id ?? 'N/A',
                'due_date'           => optional($measure->dtFimMedidaDesej)->format('d/m/Y'),
                'protest_type_label' => $this->resolveProtestTypeLabel($measure->protest_type),
            ];
        });
    }

    protected function toast(string $status, string $message): void
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => $status,
            'menssage' => $message,
        ]);
    }

    public function exportJobs(): void
    {
        [$start, $end] = $this->getDateRange();

        ExportProtestJobsJob::dispatch([
            'start'         => $start->toDateTimeString(),
            'end'           => $end->toDateTimeString(),
            'advanceFilter' => $this->advanceFilter,
            'userId'        => $this->userId,
        ], (string) auth()->id());

        $this->toast('info', 'Estamos gerando o Excel com os filtros aplicados. Você será notificado ao final.');
    }



    public function exportDispatcherMeasures(): void
    {
        [$start, $end] = $this->getDateRange();

        ExportDispatcherMeasuresJob::dispatch([
            'start'        => $start->toDateTimeString(),
            'end'          => $end->toDateTimeString(),
            'userId'       => $this->userId,
            'protestTypes' => $this->getSelectedProtestTypes(),
        ], (string) auth()->id());

        $this->toast('info', 'Estamos gerando o Excel de medidas MEDE. Voce sera notificado ao final.');
    }

    public function render()
    {
        [$start, $end] = $this->getDateRange();

        $summary         = $this->buildSummary($start, $end);
        $productivity    = $this->buildProductivityPanel($start, $end);
        $backlogPanel    = $this->buildBacklogPanel($start, $end);
        $slaPanel        = $this->buildSlaPanel($start, $end);
        $bottlenecks     = $this->buildBottlenecksPanel($start, $end);
        $dispatcherStats = $this->buildDispatcherStats($start, $end);
        $ownerStats      = $this->buildOwnerStats($start, $end);
        $dailyOpenings          = $this->buildDailyOpeningsChart($start, $end);
        $medaJobsChart          = $this->buildMedaJobsChart($start, $end);
        $dailyDispatchCompletion = $this->buildDailyDispatchCompletionChart($start, $end);
        $protestTypeDonut       = $this->buildProtestTypeDonut($start, $end);
        $jobSlaList             = $this->buildJobSlaList($start, $end);
        $medaSnapshot           = $this->buildMedaSnapshot($start, $end);
        $medaOpenHistogram      = $this->buildMedaOpenDesiredHistogram($start, $end);
        $medaOpenDispatchList   = $this->buildMedaOpenDispatchList();
        $medaDoneOpenByOwner    = $this->buildMedaDoneOpenByOwnerPanel();
        $medaOpenNoteSummary    = $this->buildMedaNoteTypeSummaryCounts();
        $medaDueSummary         = $this->buildMedaDueWindowSummaryCounts();
        $generalProtestsList    = $this->buildGeneralProtestsList($start, $end);
        $complaintsNaPanel      = $this->buildComplaintsNaPanel($start, $end);
        $complaintsOuPanel      = $this->buildComplaintsOuPanel($start, $end);
        $dispatcherMeasuresPanel = $this->buildDispatcherMeasuresPanel($start, $end);

        $this->dispatchDailyOpeningsChart($dailyOpenings);
        $this->dispatchMedaJobsChart($medaJobsChart);
        $this->dispatchMedaOpenDesiredChart($medaOpenHistogram['chart']);
        $this->dispatchDailyDispatchCompletionChart($dailyDispatchCompletion);
        $this->dispatchComplaintsNaCharts($complaintsNaPanel['charts'] ?? []);
        $this->dispatchComplaintsOuCharts($complaintsOuPanel['charts'] ?? []);
        $this->dispatchProtestTypeDonutChart($protestTypeDonut['chart']);

        return view('livewire.protests.analytics.user-sla-dashboard', [
            'summary'                 => $summary,
            'productivity'            => $productivity,
            'backlogPanel'            => $backlogPanel,
            'slaPanel'                => $slaPanel,
            'bottlenecks'             => $bottlenecks,
            'dispatcherStats'         => $dispatcherStats,
            'ownerStats'              => $ownerStats,
            'dailyOpenings'           => $dailyOpenings,
            'medaJobsChart'           => $medaJobsChart,
            'dailyDispatchCompletion' => $dailyDispatchCompletion,
            'protestTypeDonut'        => $protestTypeDonut,
            'jobSlaList'              => $jobSlaList,
            'medaSnapshot'            => $medaSnapshot,
            'medaOpenHistogram'       => $medaOpenHistogram,
            'medaOpenDispatchList'    => $medaOpenDispatchList,
            'medaDoneOpenByOwner'     => $medaDoneOpenByOwner,
            'medaOpenNoteSummary'     => $medaOpenNoteSummary,
            'medaDueSummary'          => $medaDueSummary,
            'generalProtestsList'     => $generalProtestsList,
            'complaintsNaPanel'       => $complaintsNaPanel,
            'complaintsOuPanel'       => $complaintsOuPanel,
            'dispatcherMeasuresPanel' => $dispatcherMeasuresPanel,
        ]);
    }
}
