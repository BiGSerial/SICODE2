<?php

namespace App\Http\Controllers;

use App\Services\Reports\AdsRequestedReportService;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    public function dashboard()
    {
        return view('ads.dashboard');
    }

    public function realtimeQueueDonut(Request $request, AdsRequestedReportService $service)
    {
        $filters = [
            'date_in' => $request->query('date_in'),
            'date_out' => $request->query('date_out'),
            'completed_in' => $request->query('completed_in'),
            'completed_out' => $request->query('completed_out'),
            'status_exact' => $request->query('status_exact'),
            'search' => $request->query('search'),
            'companyIds' => (array) $request->query('companyIds', []),
        ];

        $series = $service->queueDonutSeries($filters);

        return response()->json([
            'total' => $series['total'],
            'chart' => [
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
            ],
        ]);
    }

    public function realtimeDemandDelivery(Request $request, AdsRequestedReportService $service)
    {
        $filters = [
            'date_in' => $request->query('date_in'),
            'date_out' => $request->query('date_out'),
            'completed_in' => $request->query('completed_in'),
            'completed_out' => $request->query('completed_out'),
            'status_exact' => $request->query('status_exact'),
            'search' => $request->query('search'),
            'companyIds' => (array) $request->query('companyIds', []),
            'chart_granularity' => $request->query('chart_granularity'),
        ];

        $series = $service->demandVsDeliverySeries($filters);
        $bucketLabel = (string) ($series['bucket_label'] ?? 'diária');

        return response()->json([
            'analytics' => $series['analytics'] ?? [],
            'line_chart' => [
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
                            'yAxisID' => 'y1',
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
                            'yAxisID' => 'y1',
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
            ],
            'bar_chart' => [
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
            ],
        ]);
    }
}
