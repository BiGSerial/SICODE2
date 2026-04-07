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
                    'legend' => ['position' => 'bottom'],
                    'title' => [
                        'display' => true,
                        'text' => 'Fila atual (status pendentes)',
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
            'total' => $series['total'],
        ]);
    }
}
