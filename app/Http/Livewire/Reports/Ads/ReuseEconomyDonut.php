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
                    'legend' => ['position' => 'top'],
                    'title' => [
                        'display' => true,
                        'text' => 'Economia por reaproveitamento de ADS',
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#ffffff',
                        'font' => ['weight' => '600', 'size' => 11],
                        'formatter' => '__DOUGHNUT_PERCENT_LABEL__',
                    ],
                    'centerText' => [
                        'display' => true,
                        'text' => (string) ((int) ($series['total'] ?? 0)),
                        'subtext' => 'Total',
                        'font' => '700 34px sans-serif',
                        'subFont' => '600 12px sans-serif',
                        'color' => '#1f2937',
                        'subColor' => '#6b7280',
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
