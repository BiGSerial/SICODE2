<?php

namespace App\Http\Livewire\Reports\Ads;

use App\Services\Reports\AdsRequestedReportService;
use Livewire\Component;

class QueueStatusDonut extends Component
{
    public array $filters = [];

    public function mount(array $filters = []): void
    {
        $this->filters = $filters;
    }

    public function render(AdsRequestedReportService $service)
    {
        $series = $service->queueDonutSeries($this->filters);
        $total = (int) ($series['total'] ?? 0);

        $chart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $series['labels'],
                'datasets' => [[
                    'data' => $series['values'],
                    'backgroundColor' => $series['colors'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'animation' => [
                    'duration' => 500,
                    'easing' => 'easeOutCubic',
                ],
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title' => [
                        'display' => true,
                        'text' => 'Fila atual (status pendentes)',
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#ffffff',
                        'font' => ['weight' => '600', 'size' => 11],
                        'formatter' => '__DOUGHNUT_PERCENT_LABEL__',
                    ],
                    'centerText' => [
                        'display' => true,
                        'text' => (string) $total,
                        'subtext' => 'Total',
                        'font' => '700 34px sans-serif',
                        'subFont' => '600 12px sans-serif',
                        'color' => '#1f2937',
                        'subColor' => '#6b7280',
                    ],
                ],
                'onClickFilter' => [
                    'enabled' => true,
                    'jsEvent' => 'ads-chart-status-clicked',
                    'keys' => $series['status_keys'] ?? [],
                    'mode' => 'nearest',
                    'intersect' => true,
                ],
            ],
        ];

        return view('livewire.reports.ads.queue-status-donut', [
            'chart' => $chart,
            'total' => $total,
        ]);
    }
}
