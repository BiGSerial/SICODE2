<?php

namespace App\Services\Wall\Screen;

use App\Custom\ProductionQueryBuilder;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\WallScreen;
use App\Repositories\PublishRepository;
use App\Repositories\SupervisionRepository;
use App\Repositories\SurveyRepository;
use App\Services\Payment\NoteFilter as PaymentNoteFilter;
use App\Services\Publication\NoteFilter as PublicationNoteFilter;
use App\Services\Wall\Contracts\WallScreenDataService;
use App\Services\Wall\Context\ScreenContext;
use App\Services\Wall\Support\CacheLockTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionScreenDataService implements WallScreenDataService
{
    use CacheLockTrait;

    /** TTL do cache por item de produção (segundos). */
    private const ITEM_CACHE_TTL = 45;

    /** Colunas permitidas em query_filters para evitar injeção de SQL via coluna. */
    private const ALLOWED_FILTER_COLUMNS = [
        'note', 'rubrica', 'lexp', 'nstats', 'type_note', 'dt_status',
        'status', 'order', 'ordem', 'statusSist', 'operacao',
        'company_id', 'service_id', 'note_id', 'production_id',
    ];

    private bool $queueAgeDateColumnResolved = false;
    private ?string $queueAgeDateColumn = null;

    public function __construct(
        private readonly PublicationNoteFilter $publicationNoteFilter,
        private readonly PaymentNoteFilter $paymentNoteFilter,
        private readonly PublishRepository $publishRepository,
        private readonly SupervisionRepository $supervisionRepository,
        private readonly SurveyRepository $surveyRepository,
    ) {
    }

    // =========================================================================
    // WallScreenDataService contract
    // =========================================================================

    public function buildScreenPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id'                       => (int) $screen->id,
            'name'                     => (string) $screen->name,
            'screen_type'              => 'production_services',
            'duration_seconds'         => (int) ($screen->duration_seconds ?: $this->defaultRotation()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'items'                    => $screen->items
                ->filter(fn ($item) => $item->service)
                ->map(fn ($item) => $this->buildItemPayload(
                    $item->service,
                    $item->previousService,
                    (bool) $item->use_rule_builder,
                    $this->resolveItemSourceConfig($screen, $item),
                    (int) $screen->id,
                ))
                ->values()
                ->all(),
        ];
    }

    public function buildScreenManifestPayload(WallScreen $screen, ScreenContext $context): array
    {
        return [
            'id'                       => (int) $screen->id,
            'name'                     => (string) $screen->name,
            'screen_type'              => 'production_services',
            'duration_seconds'         => (int) ($screen->duration_seconds ?: $this->defaultRotation()),
            'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?: 180),
            'loaded'                   => false,
            'items'                    => $screen->items
                ->filter(fn ($item) => $item->service)
                ->map(fn ($item) => [
                    'service_id'                => (string) $item->service->uuid,
                    'service_name'              => (string) $item->service->service,
                    'previous_service_id'       => $item->previousService?->uuid,
                    'previous_service_name'     => $item->previousService?->service,
                    'ads_chart'                 => null,
                    'cards'                     => [],
                    'queue_histogram'           => ['labels' => [], 'values' => []],
                    'note_type_donut'           => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
                    'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
                    'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
                    'internal_return_donut'     => ['labels' => [], 'values' => []],
                    'recent_completed'          => [],
                    'week'                      => null,
                ])
                ->values()
                ->all(),
        ];
    }

    public function buildSingleItemPayload(WallScreen $screen, ScreenContext $context, string $serviceId): ?array
    {
        $screenItem = $screen->items()
            ->where('enabled', true)
            ->where('service_id', $serviceId)
            ->with(['service', 'previousService'])
            ->first();

        if (!$screenItem || !$screenItem->service) {
            return null;
        }

        return $this->buildItemPayload(
            $screenItem->service,
            $screenItem->previousService,
            (bool) $screenItem->use_rule_builder,
            $this->resolveItemSourceConfig($screen, $screenItem),
            (int) $screen->id,
        );
    }

    // =========================================================================
    // Item payload (com cache curto para aliviar banco em polls frequentes)
    // =========================================================================

    private function buildItemPayload(
        Service $service,
        ?Service $previousService,
        bool $useRuleBuilder,
        array $sourceConfig,
        int $screenId,
    ): array {
        $cacheKey = sprintf(
            'wall_v2:prod:s%d:svc:%s:rb:%d',
            $screenId,
            $service->uuid,
            (int) $useRuleBuilder
        );

        return $this->rememberWithOptionalLock($cacheKey, self::ITEM_CACHE_TTL, function () use ($service, $previousService, $useRuleBuilder, $sourceConfig) {
            return $this->compute($service, $previousService, $useRuleBuilder, $sourceConfig);
        });
    }

    private function compute(Service $service, ?Service $previousService, bool $useRuleBuilder, array $sourceConfig): array
    {
        [$start, $end] = $this->weeklyWindow();
        $dayLabels = $this->dailyDateLabels($start, $end);

        $queueAllQuery  = $this->buildActivityQueueQuery($service, $useRuleBuilder, false, $sourceConfig);
        $noteIdsQuery   = (clone $queueAllQuery)->select('notes.id');
        $queueOvQuery   = (clone $queueAllQuery)->where('notes.type_note', 2);
        $queueNotesQuery = (clone $queueAllQuery)->where(function ($q) {
            $q->whereNull('notes.type_note')->orWhere('notes.type_note', '!=', 2);
        });

        $queueTotalAll  = (clone $queueAllQuery)->count();
        $queueTotalOv   = (clone $queueOvQuery)->count();

        $queueHistogram = $this->buildQueueAgeHistogram($queueOvQuery, $service->uuid);
        $noteTypeDonut  = $this->buildNoteTypeCoverageDonut($queueNotesQuery, $service->uuid);

        $productionOpenQuery = Production::query()
            ->where('service_id', $service->uuid)
            ->whereNotNull('att_at')
            ->whereHas('Note', fn ($q) => $q->where(fn ($r) => $r->whereNull('rubrica')->orWhere('rubrica', '!=', 'Acompanhamento')))
            ->whereRaw('NOT (status = ? AND completed = ?)', [5, 1]);

        $openAssigned = (clone $productionOpenQuery)->count();

        $internalOpen = (clone $productionOpenQuery)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('reclaims')->whereColumn('reclaims.production_id', 'productions.id')->where('reclaims.completed', false))
            ->count();

        $openHistogramNormal = $this->buildAgeHistogram((clone $productionOpenQuery)->where(fn ($q) => $q->whereNull('productions.d5')->orWhere('productions.d5', false)), 'productions.att_at');
        $openHistogramRi     = $this->buildAgeHistogram((clone $productionOpenQuery)->where('productions.d5', true), 'productions.att_at');
        $openHistogram = [
            'labels'        => $openHistogramNormal['labels'],
            'normal_values' => $openHistogramNormal['values'],
            'ri_values'     => $openHistogramRi['values'],
            'values'        => array_map(fn ($i) => (int) ($openHistogramNormal['values'][$i] ?? 0) + (int) ($openHistogramRi['values'][$i] ?? 0), array_keys($openHistogramNormal['labels'])),
        ];

        $productionDaily = $this->buildProductionDailyFlow($service->uuid, $dayLabels, $start, $end);
        $internalTypes   = $this->buildInternalReturnTypesDonut($service->uuid);

        $previousDone = 0;
        $nextEntry    = 0;
        if ($previousService) {
            $prevDoneQuery = Production::query()
                ->where('service_id', $previousService->uuid)
                ->where('rejected', false)
                ->where('completed', true)
                ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

            $previousDone = (clone $prevDoneQuery)->count();
            $nextEntry    = (clone $prevDoneQuery)
                ->whereIn('productions.note_id', $noteIdsQuery)
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('analises')->whereColumn('analises.production_id', 'productions.id')->whereIn('analises.conclusion', ['ENVIADO AO DESENHO/ORÇAMENTO', 'ENVIADO AO DESENHO', 'ENVIADO PARA ORÇAMENTO']))
                ->count();
        }

        $recentCompleted = Production::query()
            ->with(['Note:id,note', 'User:id,name', 'Company:id,name', 'Reclaim:id,production_id'])
            ->where('service_id', $service->uuid)
            ->where('rejected', false)
            ->where('completed', true)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderByDesc('completed_at')
            ->limit(30)
            ->get()
            ->map(fn (Production $p) => [
                'note'         => (string) ($p->Note?->note ?? '-'),
                'user_name'    => $this->compactName((string) ($p->User?->name ?? '-')),
                'company_name' => $this->compactCompany((string) ($p->Company?->name ?? '-')),
                'type'         => $p->Reclaim ? 'RI' : 'Normal',
                'completed_at' => optional($p->completed_at)->format('d/m/Y H:i') ?? '-',
            ])
            ->values()->all();

        return [
            'service_id'           => (string) $service->uuid,
            'service_name'         => (string) $service->service,
            'previous_service_id'  => $previousService?->uuid,
            'previous_service_name' => $previousService?->service,
            'week' => [
                'start' => $start->format('Y-m-d'),
                'end'   => $end->format('Y-m-d'),
                'label' => sprintf('%s a %s', $start->format('d/m'), $end->format('d/m')),
            ],
            'cards' => [
                'queue_total'   => (int) $queueTotalAll,
                'queue_ov'      => (int) $queueTotalOv,
                'queue_notes'   => max(0, $queueTotalAll - $queueTotalOv),
                'returned'      => (int) $internalOpen,
                'previous_done' => (int) $previousDone,
                'next_entry'    => (int) $nextEntry,
                'queue_ov_only' => true,
            ],
            'queue_histogram'           => $queueHistogram,
            'note_type_donut'           => $noteTypeDonut,
            'production_open_histogram' => $openHistogram,
            'production_daily'          => $productionDaily,
            'internal_return_donut'     => $internalTypes,
            'recent_completed'          => $recentCompleted,
            'production_histogram' => [
                'labels'   => $openHistogram['labels'],
                'datasets' => [['label' => 'Atribuido aberto', 'backgroundColor' => 'rgba(0, 206, 201, .65)', 'borderColor' => '#00cec9', 'data' => $openHistogram['values']]],
            ],
        ];
    }

    // =========================================================================
    // Query building
    // =========================================================================

    private function buildActivityQueueQuery(Service $service, bool $useRuleBuilder, bool $notAssignedOnly = false, array $sourceConfig = []): Builder
    {
        $source = (string) ($sourceConfig['source'] ?? 'rule_builder');
        $query  = $this->buildQueryBySource($source, $service, $useRuleBuilder, $sourceConfig);
        $this->applyConfiguredQueryFilters($query, (array) ($sourceConfig['query_filters'] ?? []));

        if ($notAssignedOnly) {
            $query->where(fn ($q) => $q->doesntHave('Productions')->orWhereDoesntHave('Productions', fn ($s) => $s->where('service_id', $service->uuid)->where('confirmed', false)));
        }

        return $query;
    }

    private function buildQueryBySource(string $source, Service $service, bool $useRuleBuilder, array $sourceConfig): Builder
    {
        return match ($source) {
            'publication_note_filter' => $this->publicationNoteFilter->filter(
                (string) ($sourceConfig['filter_group'] ?? 'publication'),
                (bool) ($sourceConfig['btzeroform'] ?? true)
            ),
            'payment_note_filter' => $this->paymentNoteFilter->filter(
                $sourceConfig['search'] ?? null,
                (string) ($sourceConfig['filter_group'] ?? 'payment')
            ),
            'publish_repository'    => $this->publishRepository->getBaseQuery((bool) ($sourceConfig['all_services'] ?? false)),
            'supervision_repository' => $this->supervisionRepository->getBaseQuery(),
            'survey_repository'     => $this->surveyRepository->getBaseQuery(),
            default                 => $this->buildLegacyProductionQuery($service, $useRuleBuilder),
        };
    }

    private function buildLegacyProductionQuery(Service $service, bool $useRuleBuilder): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        if ($useRuleBuilder) {
            $service->loadMissing('Status');
            ProductionQueryBuilder::applyRules($query, $service->Status);
        } else {
            $query->where('nstats', $service->status);
        }

        return $query;
    }

    private function resolveItemSourceConfig(WallScreen $screen, mixed $item): array
    {
        $config        = (array) ($screen->screen_config ?? []);
        $defaultSource = (string) ($config['production_source'] ?? 'rule_builder');
        $map           = (array) ($config['production_sources'] ?? []);
        $serviceId     = (string) ($item->service_id ?? '');
        $raw           = $serviceId !== '' ? ($map[$serviceId] ?? []) : [];
        $itemConfig    = is_string($raw) ? ['source' => $raw] : (array) $raw;

        if (!isset($itemConfig['source']) || trim((string) $itemConfig['source']) === '') {
            $itemConfig['source'] = $defaultSource;
        }

        return $itemConfig;
    }

    // =========================================================================
    // Filter application (com whitelist de coluna para prevenir SQL injection)
    // =========================================================================

    private function applyConfiguredQueryFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $filter) {
            if (!is_array($filter)) continue;

            $mode     = (string) ($filter['mode'] ?? 'include');
            $scope    = (string) ($filter['scope'] ?? 'base');
            $relation = trim((string) ($filter['relation'] ?? ''));
            $column   = trim((string) ($filter['column'] ?? ''));
            $operator = (string) ($filter['operator'] ?? 'equals');
            $value    = array_key_exists('value', $filter) ? (string) ($filter['value'] ?? '') : '';

            if (!$this->isColumnAllowed($column)) continue;

            if ($scope === 'relation' && $relation !== '') {
                if ($mode === 'exclude') {
                    $query->whereDoesntHave($relation, fn ($q) => $this->applyAtomicFilter($q, $column, $operator, $value, false));
                } else {
                    $query->whereHas($relation, fn ($q) => $this->applyAtomicFilter($q, $column, $operator, $value, false));
                }
                continue;
            }

            $this->applyAtomicFilter($query, $column, $operator, $value, $mode === 'exclude');
        }
    }

    private function applyAtomicFilter(Builder $query, string $column, string $operator, string $value, bool $exclude): void
    {
        $op = match ($operator) {
            'starts_with', 'contains', 'ends_with' => $exclude ? 'not like' : 'like',
            default                                 => $exclude ? '!=' : '=',
        };

        $sqlValue = match ($operator) {
            'starts_with' => $value . '%',
            'contains'    => '%' . $value . '%',
            'ends_with'   => '%' . $value,
            default       => $value,
        };

        $query->where($column, $op, $sqlValue);
    }

    /**
     * Valida se a coluna é permitida para uso em query_filters.
     * Aceita nomes simples (ex: "rubrica") e qualificados (ex: "Orders.statusSist").
     */
    private function isColumnAllowed(string $column): bool
    {
        if ($column === '') return false;

        // Formato: palavra simples ou tabela.coluna
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column)) return false;

        // Extrai somente a parte da coluna (descarta prefixo de tabela)
        $bare = str_contains($column, '.') ? explode('.', $column)[1] : $column;

        return in_array($bare, self::ALLOWED_FILTER_COLUMNS, true);
    }

    // =========================================================================
    // Chart builders
    // =========================================================================

    private function buildQueueAgeHistogram(Builder $baseQuery, string $serviceId, int $maxDays = 30): array
    {
        $labels   = [...array_map('strval', range(0, $maxDays - 1)), "{$maxDays}+"];
        $totals   = array_fill_keys($labels, 0);
        $assigned = array_fill_keys($labels, 0);
        $maxLabel = "{$maxDays}+";
        $dateColumn = $this->resolveQueueAgeDateColumn();

        if ($dateColumn === null) {
            return [
                'labels'                  => $labels,
                'values'                  => array_values($totals),
                'assigned_values'         => array_values($assigned),
                'without_assigned_values' => array_values($totals),
            ];
        }

        $statusDateExpr = $this->buildSafeDateExpression('notes.status_ref');

        $assignedSub = Production::query()
            ->selectRaw('note_id, MIN(att_at) as first_att_at')
            ->where('service_id', $serviceId)
            ->where('rejected', false)
            ->whereNotNull('att_at')
            ->groupBy('note_id');

        $safeRef    = "CASE WHEN DATE({$statusDateExpr}) > CURDATE() THEN CURDATE() ELSE DATE({$statusDateExpr}) END";
        $ageDays    = "GREATEST(0, DATEDIFF(CURDATE(), {$safeRef}))";
        $bucketExpr = "CASE WHEN q.age_days >= {$maxDays} THEN '{$maxLabel}' ELSE CAST(q.age_days AS CHAR) END";

        $queueRows = DB::query()
            ->fromSub((clone $baseQuery)->reorder()->selectRaw("notes.id, notes.{$dateColumn} as status_ref"), 'notes')
            ->leftJoinSub($assignedSub, 'pa', fn ($j) => $j->on('pa.note_id', '=', 'notes.id'))
            ->whereRaw("{$statusDateExpr} IS NOT NULL")
            ->selectRaw('notes.id as id')
            ->selectRaw("{$ageDays} as age_days")
            ->selectRaw('CASE WHEN pa.note_id IS NULL THEN 0 ELSE 1 END as has_assigned');

        $rows = DB::query()
            ->fromSub($queueRows, 'q')
            ->selectRaw("{$bucketExpr} as bucket")
            ->selectRaw('q.has_assigned')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket', 'q.has_assigned')
            ->get();

        foreach ($rows as $row) {
            $bucket = (string) $row->bucket;
            if (!array_key_exists($bucket, $totals)) continue;
            $qty = (int) $row->total;
            $totals[$bucket] += $qty;
            if ((int) $row->has_assigned === 1) $assigned[$bucket] += $qty;
        }

        $totalValues    = array_values($totals);
        $assignedValues = array_values($assigned);

        return [
            'labels'                  => $labels,
            'values'                  => $totalValues,
            'assigned_values'         => $assignedValues,
            'without_assigned_values' => array_map(fn ($t, $a) => max(0, $t - $a), $totalValues, $assignedValues),
        ];
    }

    private function resolveQueueAgeDateColumn(): ?string
    {
        if ($this->queueAgeDateColumnResolved) {
            return $this->queueAgeDateColumn;
        }

        $this->queueAgeDateColumnResolved = true;

        if (Schema::hasColumn('notes', 'dt_status')) {
            $this->queueAgeDateColumn = 'dt_status';
            return $this->queueAgeDateColumn;
        }

        $this->queueAgeDateColumn = null;
        return null;
    }

    private function buildSafeDateExpression(string $columnExpr): string
    {
        return "
            COALESCE(
                NULLIF(CAST({$columnExpr} AS DATETIME), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y-%m-%dT%H:%i:%s.%fZ'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y-%m-%dT%H:%i:%sZ'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y-%m-%dT%H:%i:%s.%f'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y-%m-%dT%H:%i:%s'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y-%m-%d %H:%i:%s'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y-%m-%d'), '0000-00-00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%Y%m%d'), '0000-00-00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%d/%m/%Y %H:%i:%s'), '0000-00-00 00:00:00'),
                NULLIF(STR_TO_DATE({$columnExpr}, '%d/%m/%Y'), '0000-00-00')
            )
        ";
    }

    private function buildNoteTypeCoverageDonut(Builder $queueNotesQuery, string $serviceId): array
    {
        $rows = DB::query()
            ->fromSub((clone $queueNotesQuery)->reorder()->select('notes.id'), 'n')
            ->leftJoinSub(
                Production::query()->select('note_id')->where('service_id', $serviceId)->where('rejected', false)->whereNotNull('att_at')->groupBy('note_id'),
                'pa',
                fn ($j) => $j->on('pa.note_id', '=', 'n.id')
            )
            ->selectRaw('CASE WHEN pa.note_id IS NULL THEN 0 ELSE 1 END as has_associated')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('has_associated')
            ->get();

        $associated = 0; $without = 0;
        foreach ($rows as $row) {
            $qty = (int) ($row->total ?? 0);
            if ((int) ($row->has_associated ?? 0) === 1) $associated += $qty;
            else $without += $qty;
        }
        $total = $associated + $without;

        return [
            'labels'     => ['Com produção associada', 'Sem produção associada'],
            'values'     => [(int) $associated, (int) $without],
            'total'      => (int) $total,
            'associated' => (int) $associated,
            'relation'   => $total > 0 ? [round(($associated / $total) * 100, 1), round(($without / $total) * 100, 1)] : [0, 0],
        ];
    }

    private function buildAgeHistogram(Builder $baseQuery, string $sqlDateExpr, int $maxDays = 30): array
    {
        $labels   = [...array_map('strval', range(0, $maxDays - 1)), "{$maxDays}+"];
        $buckets  = array_fill_keys($labels, 0);
        $maxLabel = "{$maxDays}+";
        $ageExpr  = "GREATEST(0, DATEDIFF(CURDATE(), DATE({$sqlDateExpr})))";
        $bucketEx = "CASE WHEN {$ageExpr} >= {$maxDays} THEN '{$maxLabel}' ELSE CAST({$ageExpr} AS CHAR) END";

        $rows = (clone $baseQuery)
            ->whereNotNull($sqlDateExpr)
            ->selectRaw("{$bucketEx} as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->get();

        foreach ($rows as $row) {
            $b = (string) ($row->bucket ?? '');
            if (array_key_exists($b, $buckets)) $buckets[$b] = (int) ($row->total ?? 0);
        }

        return ['labels' => $labels, 'values' => array_map(fn ($l) => (int) ($buckets[$l] ?? 0), $labels)];
    }

    private function buildProductionDailyFlow(string $serviceId, array $dayLabels, Carbon $start, Carbon $end): array
    {
        $rubricaFilter = fn ($q) => $q->where(fn ($r) => $r->whereNull('rubrica')->orWhere('rubrica', '!=', 'Acompanhamento'));

        $assigned = Production::query()
            ->where('service_id', $serviceId)->where('rejected', false)
            ->whereHas('Note', $rubricaFilter)
            ->whereNotNull('att_at')
            ->whereBetween('att_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(att_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')->get()->pluck('total', 'day_key');

        $delivered = Production::query()
            ->where('service_id', $serviceId)->where('rejected', false)->where('completed', true)
            ->whereHas('Note', $rubricaFilter)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(completed_at) as day_key, COUNT(*) as total')
            ->groupBy('day_key')->get()->pluck('total', 'day_key');

        return [
            'labels'    => array_map(fn ($d) => Carbon::parse($d)->format('d/m'), $dayLabels),
            'assigned'  => array_map(fn ($d) => (int) ($assigned[$d] ?? 0), $dayLabels),
            'delivered' => array_map(fn ($d) => (int) ($delivered[$d] ?? 0), $dayLabels),
        ];
    }

    private function buildInternalReturnTypesDonut(string $serviceId): array
    {
        // Uma única query agrupa todos os tipos em vez de 4 round-trips
        $base = Reclaim::query()
            ->where('service_id', $serviceId)
            ->where('completed', false);

        $counts = [
            'Viabilities' => (clone $base)->whereHas('Viabilities')->count(),
            'Waiting'     => (clone $base)->whereHas('Waiting')->count(),
            'Approvals'   => (clone $base)->whereHas('Approvals')->count(),
            'Externals'   => (clone $base)->whereHas('Externals')->count(),
        ];

        $labels   = array_keys($counts);
        $values   = array_values($counts);
        $total    = (int) array_sum($values);
        $relation = array_map(fn ($v) => $total > 0 ? round($v / $total * 100, 1) : 0.0, $values);

        return ['labels' => $labels, 'values' => $values, 'relation' => $relation, 'total' => $total];
    }

    // =========================================================================
    // Utilities
    // =========================================================================

    private function weeklyWindow(): array
    {
        return [now()->subDays(6)->startOfDay(), now()->endOfDay()];
    }

    private function dailyDateLabels(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }
        return $labels;
    }

    private function defaultRotation(): int
    {
        return max(10, (int) (SystemSetting::getValue('wall_v2_rotation_seconds', '180') ?? '180'));
    }

    private function compactName(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)), fn ($p) => $p !== ''));
        if (empty($parts)) return '-';
        return count($parts) === 1 ? $parts[0] : $parts[0] . ' ' . $parts[count($parts) - 1];
    }

    private function compactCompany(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)), fn ($p) => $p !== ''));
        if (empty($parts)) return '-';
        if (count($parts) === 1) return $parts[0];
        $initials = '';
        for ($i = 1; $i < count($parts); $i++) $initials .= mb_strtoupper(mb_substr($parts[$i], 0, 1));
        return $parts[0] . ' ' . $initials;
    }
}
