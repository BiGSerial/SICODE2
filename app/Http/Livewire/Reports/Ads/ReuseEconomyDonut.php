<?php

namespace App\Http\Livewire\Reports\Ads;

use App\Services\Reports\AdsRequestedReportService;
use Livewire\Component;

class ReuseEconomyDonut extends Component
{
    public array $filters = [];

    public function mount(array $filters = []): void
    {
        $this->filters = $filters;
    }

    public function render(AdsRequestedReportService $service)
    {
        $series = $service->reuseEconomyDonutSeries($this->filters);

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
                        'text' => 'Economia por reaproveitamento de ADS',
                    ],
                ],
            ],
        ];

        return view('livewire.reports.ads.reuse-economy-donut', [
            'chart' => $chart,
            'total' => (int) ($series['total'] ?? 0),
            'reused' => (int) ($series['reused'] ?? 0),
            'queued' => (int) ($series['queued'] ?? 0),
            'reuseRate' => (float) ($series['reuse_rate'] ?? 0.0),
        ]);
    }
}

