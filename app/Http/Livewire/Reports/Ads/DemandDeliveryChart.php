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
        $labelsCount = max(1, count($series['labels'] ?? []));
        $requestedAvg = round(array_sum($series['requested'] ?? []) / $labelsCount, 2);
        $deliveredAvg = round(array_sum($series['delivered'] ?? []) / $labelsCount, 2);
        $openBacklogAvg = round(array_sum($series['open_backlog'] ?? []) / $labelsCount, 2);
        $overdueBacklogAvg = round(array_sum($series['overdue_backlog'] ?? []) / $labelsCount, 2);
        $requestedAvgSeries = array_fill(0, count($series['labels'] ?? []), $requestedAvg);
        $deliveredAvgSeries = array_fill(0, count($series['labels'] ?? []), $deliveredAvg);
        $openBacklogAvgSeries = array_fill(0, count($series['labels'] ?? []), $openBacklogAvg);
        $overdueBacklogAvgSeries = array_fill(0, count($series['labels'] ?? []), $overdueBacklogAvg);

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
                        'borderWidth' => 1.5,
                        'datalabels' => [
                            'display' => true,
                            'anchor' => 'end',
                            'align' => 'top',
                            'offset' => 6,
                        ],
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Atrasadas (apos dia seguinte)',
                        'data' => $series['overdue_backlog'],
                        'borderColor' => 'rgba(239,68,68,0.95)',
                        'backgroundColor' => 'rgba(239,68,68,0.18)',
                        'pointBackgroundColor' => 'rgba(239,68,68,0.95)',
                        'pointRadius' => 2,
                        'tension' => 0.25,
                        'fill' => false,
                        'borderWidth' => 1.5,
                        'datalabels' => [
                            'display' => true,
                            'anchor' => 'end',
                            'align' => 'bottom',
                            'offset' => 6,
                        ],
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Média (acumulado)',
                        'data' => $openBacklogAvgSeries,
                        'borderColor' => 'rgba(124,58,237,0.8)',
                        'borderWidth' => 1.5,
                        'borderDash' => [6, 6],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'tension' => 0,
                        'fill' => false,
                        'datalabels' => [
                            'display' => false,
                        ],
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Média (atrasadas)',
                        'data' => $overdueBacklogAvgSeries,
                        'borderColor' => 'rgba(239,68,68,0.8)',
                        'borderWidth' => 1.5,
                        'borderDash' => [6, 6],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'tension' => 0,
                        'fill' => false,
                        'datalabels' => [
                            'display' => false,
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
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                        'labels' => [
                            'generateLabels' => '__ADS_MIXED_DATASET_LEGEND__',
                        ],
                    ],
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
                    [
                        'type' => 'line',
                        'label' => 'Média (solicitadas)',
                        'data' => $requestedAvgSeries,
                        'borderColor' => 'rgba(30,41,59,0.75)',
                        'borderWidth' => 1.5,
                        'borderDash' => [6, 6],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'fill' => false,
                        'tension' => 0,
                        'order' => 0,
                        'datalabels' => [
                            'display' => false,
                        ],
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Média (concluídas)',
                        'data' => $deliveredAvgSeries,
                        'borderColor' => 'rgba(5,150,105,0.95)',
                        'borderWidth' => 1.5,
                        'borderDash' => [4, 4],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'fill' => false,
                        'tension' => 0,
                        'order' => 0,
                        'datalabels' => [
                            'display' => false,
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
                    'legend' => [
                        'position' => 'top',
                        'labels' => [
                            'generateLabels' => '__ADS_MIXED_DATASET_LEGEND__',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Entradas x Saídas (barras) - visão ' . $bucketLabel,
                    ],
                    'datalabels' => [
                        'display' => true,
                        'anchor' => 'end',
                        'align' => 'end',
                        'offset' => 6,
                        'clip' => false,
                        'clamp' => false,
                        'color' => 'rgba(31,41,55,0.95)',
                        'font' => [
                            'weight' => '600',
                            'size' => 11,
                        ],
                        'formatter' => '__VALUE_LABEL__',
                    ],
                ],
                'layout' => [
                    'padding' => [
                        'top' => 14,
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
