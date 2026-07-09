<?php

namespace App\Http\Livewire\Dispatchs;

use App\Custom\RuleBuilder;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public $service;

    public string $noteType = '2';
    public bool $includeTrackingRubric = false;

    public string $statusChartId;
    public string $deadlineChartId;
    public string $userChartId;

    public function mount($service): void
    {
        $this->service = Service::query()
            ->with('Status')
            ->where('uuid', $service)
            ->firstOrFail();

        $suffix = Str::random(8);
        $this->statusChartId = 'dispatchStatusAge-' . $suffix;
        $this->deadlineChartId = 'dispatchDeadline-' . $suffix;
        $this->userChartId = 'dispatchUsers-' . $suffix;
    }

    public function updatedNoteType(): void
    {
        $this->noteType = in_array((string) $this->noteType, ['1', '2', ''], true)
            ? (string) $this->noteType
            : '2';

        $this->refreshCharts();
    }

    public function updatedIncludeTrackingRubric(): void
    {
        $this->refreshCharts();
    }

    protected function stackQuery(): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $this->service->Status);

        if ($this->noteType !== '') {
            $query->where('type_note', (int) $this->noteType);
        }

        if (!$this->includeTrackingRubric) {
            $query->whereRaw("LOWER(TRIM(COALESCE(notes.rubrica, ''))) <> ?", ['acompanhamento']);
        }

        return $query;
    }

    protected function chartStackQuery(): Builder
    {
        return $this->stackQuery();
    }

    protected function assignedExistsSql(): string
    {
        return 'EXISTS (
            SELECT 1
            FROM productions p
            WHERE p.note_id = notes.id
              AND p.service_id = ?
              AND p.confirmed = 0
        )';
    }

    protected function statusBucketSql(): string
    {
        return "CASE
            WHEN notes.dt_status IS NULL THEN 'Sem data'
            WHEN DATEDIFF(CURDATE(), notes.dt_status) >= 30 THEN '30+'
            WHEN DATEDIFF(CURDATE(), notes.dt_status) < 0 THEN '0'
            ELSE CAST(DATEDIFF(CURDATE(), notes.dt_status) AS CHAR)
        END";
    }

    protected function deadlineBucketSql(): string
    {
        return "CASE
            WHEN notes.days_left IS NULL THEN 'Sem prazo'
            WHEN notes.days_left < 0 THEN 'Vencido'
            WHEN notes.days_left >= 30 THEN '30+'
            ELSE CAST(notes.days_left AS CHAR)
        END";
    }

    protected function labelsForStatusAge(): array
    {
        return array_merge(array_map('strval', range(0, 29)), ['30+', 'Sem data']);
    }

    protected function labelsForDeadline(): array
    {
        return array_merge(['Sem prazo', '30+'], array_map('strval', range(29, 0)), ['Vencido']);
    }

    protected function aggregateStackedBuckets(string $bucketSql, array $labels): array
    {
        $rows = (clone $this->chartStackQuery())
            ->selectRaw(
                "{$bucketSql} as bucket,
                CASE WHEN {$this->assignedExistsSql()} THEN 'assigned' ELSE 'unassigned' END as assignment,
                COUNT(*) as total",
                [$this->service->uuid]
            )
            ->groupBy('bucket', 'assignment')
            ->get();

        $assigned = array_fill_keys($labels, 0);
        $unassigned = array_fill_keys($labels, 0);

        foreach ($rows as $row) {
            $bucket = (string) $row->bucket;
            $target = $row->assignment === 'assigned' ? 'assigned' : 'unassigned';

            if (!array_key_exists($bucket, $assigned)) {
                continue;
            }

            if ($target === 'assigned') {
                $assigned[$bucket] = (int) $row->total;
            } else {
                $unassigned[$bucket] = (int) $row->total;
            }
        }

        return [
            'labels' => $labels,
            'assigned' => array_values($assigned),
            'unassigned' => array_values($unassigned),
        ];
    }

    protected function aggregateDeadlineBucketsByCompany(): array
    {
        $labels = $this->labelsForDeadline();
        $bucketSql = $this->deadlineBucketSql();

        $rows = (clone $this->chartStackQuery())
            ->selectRaw(
                "{$bucketSql} as bucket,
                COALESCE((
                    SELECT companies.name
                    FROM productions p
                    LEFT JOIN companies ON companies.id = p.company_id
                    WHERE p.note_id = notes.id
                      AND p.service_id = ?
                      AND p.confirmed = 0
                    ORDER BY p.created_at DESC
                    LIMIT 1
                ), 'Na pilha') as series,
                COUNT(*) as total",
                [$this->service->uuid]
            )
            ->groupBy('bucket', 'series')
            ->get();

        $series = ['Na pilha' => array_fill_keys($labels, 0)];

        foreach ($rows as $row) {
            $bucket = (string) $row->bucket;
            $name = (string) $row->series;

            if (!array_key_exists($bucket, $series['Na pilha'])) {
                continue;
            }

            if (!array_key_exists($name, $series)) {
                $series[$name] = array_fill_keys($labels, 0);
            }

            $series[$name][$bucket] = (int) $row->total;
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    protected function summary(): array
    {
        $row = (clone $this->stackQuery())
            ->selectRaw(
                "COUNT(*) as total,
                SUM(CASE WHEN {$this->assignedExistsSql()} THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN notes.days_left < 0 THEN 1 ELSE 0 END) as overdue,
                AVG(CASE
                    WHEN notes.dt_status IS NULL THEN NULL
                    WHEN DATEDIFF(CURDATE(), notes.dt_status) < 0 THEN 0
                    ELSE DATEDIFF(CURDATE(), notes.dt_status)
                END) as avg_status_days",
                [$this->service->uuid]
            )
            ->first();

        $total = (int) ($row->total ?? 0);
        $assigned = (int) ($row->assigned ?? 0);

        return [
            'total' => $total,
            'assigned' => $assigned,
            'unassigned' => max(0, $total - $assigned),
            'overdue' => (int) ($row->overdue ?? 0),
            'avg_status_days' => round((float) ($row->avg_status_days ?? 0), 1),
        ];
    }

    protected function userDistribution(): array
    {
        $ageSql = "GREATEST(DATEDIFF(CURDATE(), COALESCE(productions.att_at, productions.dispatch_at, productions.created_at)), 0)";

        $rows = (clone $this->chartStackQuery())
            ->join('productions', 'productions.note_id', '=', 'notes.id')
            ->join('users', 'users.id', '=', 'productions.user_id')
            ->where('productions.service_id', $this->service->uuid)
            ->where('productions.confirmed', false)
            ->whereNotNull('productions.user_id')
            ->selectRaw(
                "users.name as user_name,
                CASE
                    WHEN {$ageSql} < 5 THEN 'Até 4 dias'
                    WHEN {$ageSql} <= 8 THEN '5 a 8 dias'
                    ELSE 'Mais de 8 dias'
                END as age_bucket,
                COUNT(DISTINCT notes.id) as total"
            )
            ->groupBy('user_name', 'age_bucket')
            ->get();

        $bucketLabels = ['Até 4 dias', '5 a 8 dias', 'Mais de 8 dias'];
        $users = $rows
            ->groupBy('user_name')
            ->map(fn ($items) => [
                'total' => (int) $items->sum('total'),
                'buckets' => $items->pluck('total', 'age_bucket'),
            ])
            ->sortByDesc('total')
            ->take(12);

        return [
            'labels' => $users->keys()->values()->toArray(),
            'buckets' => collect($bucketLabels)
                ->mapWithKeys(fn ($bucket) => [
                    $bucket => $users
                        ->map(fn ($user) => (int) ($user['buckets'][$bucket] ?? 0))
                        ->values()
                        ->toArray(),
                ])
                ->toArray(),
        ];
    }

    protected function criticalStackItems()
    {
        return $this->criticalStackQuery()
            ->limit(15)
            ->get();
    }

    protected function criticalStackQuery(): Builder
    {
        return (clone $this->stackQuery())
            ->select('notes.id')
            ->selectRaw("
                notes.note as document_number,
                CASE WHEN notes.type_note = 2 THEN 'OV' ELSE 'Nota' END as document_type,
                notes.rubrica as category_name,
                notes.lexp as city_name,
                notes.days_left as deadline_value
            ")
            ->selectRaw(
                "CASE WHEN {$this->assignedExistsSql()} THEN 1 ELSE 0 END as assigned,
                (
                    SELECT users.name
                    FROM productions p
                    LEFT JOIN users ON users.id = p.user_id
                    WHERE p.note_id = notes.id
                      AND p.service_id = ?
                      AND p.confirmed = 0
                    ORDER BY p.created_at DESC
                    LIMIT 1
                ) as assigned_user",
                [$this->service->uuid, $this->service->uuid]
            )
            ->orderByRaw('CASE WHEN notes.days_left IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('notes.days_left')
            ->orderBy('notes.dt_status');
    }

    public function exportCriticalStack()
    {
        $fileName = 'pilha-critica-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nota/OV',
                'Tipo',
                'Rubrica',
                'Municipio',
                'Status',
                'Prazo',
                'Usuario',
            ], ';', '"', '');

            $this->criticalStackQuery()
                ->chunk(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->document_number,
                            $item->document_type,
                            $item->category_name ?: '-',
                            $item->city_name ?: '-',
                            (int) $item->assigned === 1 ? 'Em atribuicao' : 'Na pilha',
                            $item->deadline_value ?? '-',
                            $item->assigned_user ?: '-',
                        ], ';', '"', '');
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function stackedChart(array $bucketData, string $xTitle): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $bucketData['labels'],
                'datasets' => [
                    [
                        'label' => 'Em atribuição',
                        'data' => $bucketData['assigned'],
                        'backgroundColor' => '#28FF52',
                        'borderColor' => '#28FF52',
                        'borderRadius' => 4,
                        'stack' => 'stack',
                    ],
                    [
                        'label' => 'Na pilha',
                        'data' => $bucketData['unassigned'],
                        'backgroundColor' => '#A9FFBA',
                        'borderColor' => '#A9FFBA',
                        'borderRadius' => 4,
                        'stack' => 'stack',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => [
                            'usePointStyle' => true,
                            'boxWidth' => 8,
                        ],
                    ],
                    'tooltip' => [
                        'mode' => 'index',
                        'intersect' => false,
                    ],
                    'datalabels' => [
                        'display' => '__VALUE_LABEL_NONZERO__',
                        'anchor' => 'end',
                        'align' => 'top',
                        'font' => ['weight' => '700', 'size' => 10],
                    ],
                ],
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'grid' => ['display' => false],
                        'title' => ['display' => true, 'text' => $xTitle],
                    ],
                    'y' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'title' => ['display' => true, 'text' => 'Qtd'],
                    ],
                ],
            ],
        ];
    }

    protected function deadlineCompanyChart(array $bucketData): array
    {
        $palette = [
            '#102033',
            '#263CC8',
            '#0CD3F8',
            '#E32C2C',
            '#F59E0B',
            '#155F67',
            '#7C9599',
            '#F7D200',
            '#225E66',
            '#A8B1E9',
        ];

        $datasets = [];
        $index = 0;

        foreach ($bucketData['series'] as $label => $values) {
            $color = $label === 'Na pilha'
                ? '#28F66A'
                : $palette[$index++ % count($palette)];

            $datasets[] = [
                'label' => $label,
                'data' => array_values($values),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderRadius' => 4,
                'stack' => 'stack',
            ];
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $bucketData['labels'],
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => [
                            'usePointStyle' => true,
                            'boxWidth' => 8,
                        ],
                    ],
                    'tooltip' => [
                        'mode' => 'index',
                        'intersect' => false,
                    ],
                    'datalabels' => [
                        'display' => '__VALUE_LABEL_NONZERO__',
                        'anchor' => 'end',
                        'align' => 'top',
                        'font' => ['weight' => '700', 'size' => 10],
                    ],
                ],
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'grid' => ['display' => false],
                        'title' => ['display' => true, 'text' => 'Prazo real'],
                    ],
                    'y' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'title' => ['display' => true, 'text' => 'Qtd'],
                    ],
                ],
            ],
        ];
    }

    protected function userChart(array $userData): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $userData['labels'],
                'datasets' => [
                    [
                        'label' => 'Até 4 dias',
                        'data' => $userData['buckets']['Até 4 dias'] ?? [],
                        'backgroundColor' => '#A9FFBA',
                        'borderColor' => '#A9FFBA',
                        'borderRadius' => 5,
                        'stack' => 'stack',
                    ],
                    [
                        'label' => '5 a 8 dias',
                        'data' => $userData['buckets']['5 a 8 dias'] ?? [],
                        'backgroundColor' => '#28FF52',
                        'borderColor' => '#28FF52',
                        'borderRadius' => 5,
                        'stack' => 'stack',
                    ],
                    [
                        'label' => 'Mais de 8 dias',
                        'data' => $userData['buckets']['Mais de 8 dias'] ?? [],
                        'backgroundColor' => '#A8B1E9',
                        'borderColor' => '#A8B1E9',
                        'borderRadius' => 5,
                        'stack' => 'stack',
                    ],
                ],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                        'labels' => [
                            'usePointStyle' => true,
                            'boxWidth' => 8,
                        ],
                    ],
                    'datalabels' => [
                        'display' => '__VALUE_LABEL_NONZERO__',
                        'anchor' => 'end',
                        'align' => 'right',
                        'font' => ['weight' => '700', 'size' => 10],
                    ],
                ],
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                        'grid' => ['color' => 'rgba(148, 163, 184, .18)'],
                    ],
                    'y' => [
                        'stacked' => true,
                        'grid' => ['display' => false],
                    ],
                ],
            ],
        ];
    }

    protected function chartPayloads(): array
    {
        $statusBuckets = $this->aggregateStackedBuckets($this->statusBucketSql(), $this->labelsForStatusAge());
        $deadlineBuckets = $this->aggregateDeadlineBucketsByCompany();
        $userData = $this->userDistribution();

        return [
            'statusAgeChart' => $this->stackedChart($statusBuckets, 'Tempo no status'),
            'deadlineChart' => $this->deadlineCompanyChart($deadlineBuckets),
            'userChart' => $this->userChart($userData),
        ];
    }

    protected function refreshCharts(): void
    {
        $payloads = $this->chartPayloads();

        $this->dispatchBrowserEvent('grafico-atualizar-' . $this->statusChartId, $payloads['statusAgeChart']);
        $this->dispatchBrowserEvent('grafico-atualizar-' . $this->deadlineChartId, $payloads['deadlineChart']);
        $this->dispatchBrowserEvent('grafico-atualizar-' . $this->userChartId, $payloads['userChart']);
    }

    public function render()
    {
        $payloads = $this->chartPayloads();

        return view('livewire.dispatchs.dashboard', [
            'summary' => $this->summary(),
            'statusAgeChart' => $payloads['statusAgeChart'],
            'deadlineChart' => $payloads['deadlineChart'],
            'userChart' => $payloads['userChart'],
            'criticalItems' => $this->criticalStackItems(),
            'lastUpdatedAt' => Carbon::now(),
        ]);
    }
}
