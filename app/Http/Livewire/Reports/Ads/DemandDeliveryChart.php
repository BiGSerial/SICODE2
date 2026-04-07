<?php

namespace App\Http\Livewire\Reports\Ads;

use App\Services\Reports\AdsRequestedReportService;
use Livewire\Component;

class DemandDeliveryChart extends Component
{
    public array $filters = [];

    public function mount(array $filters = []): void
    {
        $this->filters = $filters;
    }

    public function render(AdsRequestedReportService $service)
    {
        $series = $service->demandVsDeliverySeries($this->filters);
        $bucketLabel = (string) ($series['bucket_label'] ?? 'diária');

        $lineChart = [
            'type' => 'line',
            'data' => [
                'labels' => $series['labels'],
                'datasets' => [
                    [
                        'type' => 'line',
                        'label' => 'Acumulado em aberto',
                        'data' => $series['open_backlog'],
                        'borderColor' => 'rgba(124,58,237,0.95)',
                        'backgroundColor' => 'rgba(124,58,237,0.18)',
                        'pointBackgroundColor' => 'rgba(124,58,237,0.95)',
                        'pointRadius' => 2,
                        'tension' => 0.25,
                        'fill' => false,
                        'borderWidth' => 2,
                        'datalabels' => [
                            'anchor' => 'end',
                            'align' => 'top',
                            'offset' => 6,
                        ],
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Atrasadas (>24h)',
                        'data' => $series['overdue_backlog'],
                        'borderColor' => 'rgba(239,68,68,0.95)',
                        'backgroundColor' => 'rgba(239,68,68,0.18)',
                        'pointBackgroundColor' => 'rgba(239,68,68,0.95)',
                        'pointRadius' => 2,
                        'tension' => 0.25,
                        'fill' => false,
                        'borderWidth' => 2,
                        'datalabels' => [
                            'anchor' => 'end',
                            'align' => 'bottom',
                            'offset' => 6,
                        ],
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'animation' => [
                    'duration' => 500,
                    'easing' => 'easeOutCubic',
                ],
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => [
                        'display' => true,
                        'text' => 'Acumulado e Atrasadas (linha) - visão ' . $bucketLabel,
                    ],
                ],
                'onClickFilter' => [
                    'enabled' => true,
                    'jsEvent' => 'ads-chart-day-clicked',
                    'keys' => $series['date_keys'] ?? [],
                    'allowLabelFallback' => true,
                    'mode' => 'nearest',
                    'intersect' => false,
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                    ],
                ],
            ],
        ];

        $barChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $series['labels'],
                'datasets' => [
                    [
                        'type' => 'bar',
                        'label' => 'Demandas (solicitadas)',
                        'data' => $series['requested'],
                        'backgroundColor' => 'rgba(37,99,235,0.75)',
                        'borderColor' => 'rgba(15,23,42,.45)',
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                    [
                        'type' => 'bar',
                        'label' => 'Saídas (concluídas)',
                        'data' => $series['delivered'],
                        'backgroundColor' => 'rgba(5,150,105,0.75)',
                        'borderColor' => 'rgba(15,23,42,.45)',
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                ],
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
                        'text' => 'Entradas x Saídas (barras) - visão ' . $bucketLabel,
                    ],
                ],
                'onClickFilter' => [
                    'enabled' => true,
                    'jsEvent' => 'ads-chart-day-clicked',
                    'keys' => $series['date_keys'] ?? [],
                    'allowLabelFallback' => true,
                    'mode' => 'nearest',
                    'intersect' => false,
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                    ],
                ],
            ],
        ];

        return view('livewire.reports.ads.demand-delivery-chart', [
            'lineChart' => $lineChart,
            'barChart' => $barChart,
            'analytics' => $series['analytics'] ?? [],
        ]);
    }
}
