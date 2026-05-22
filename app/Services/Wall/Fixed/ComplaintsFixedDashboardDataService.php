<?php

namespace App\Services\Wall\Fixed;

use App\Models\WallScreen;

class ComplaintsFixedDashboardDataService
{
    public function buildItemPayload(WallScreen $screen): array
    {
        return $this->placeholder();
    }

    public function buildManifestItem(): array
    {
        return $this->placeholder();
    }

    private function placeholder(): array
    {
        return [
            'service_id'           => 'fixed-complaints_dashboard',
            'service_name'         => 'RECLAMAÇÃO',
            'previous_service_id'  => null,
            'previous_service_name' => null,
            'ads_chart'            => ['kind' => 'dashboard'],
            'cards'                => [],
            'complaints_dashboard' => [
                'top_cards'    => [['label' => 'Status', 'value' => 'Em desenvolvimento']],
                'middle_cards' => [],
                'line_chart'   => ['labels' => [], 'datasets' => []],
                'bar_chart'    => ['labels' => [], 'datasets' => []],
                'queue_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0],
                'reuse_donut'  => ['labels' => ['Sem dados'], 'values' => [1], 'colors' => ['rgba(107,114,128,0.8)'], 'total' => 0, 'reuse_rate' => 0],
            ],
            'queue_histogram'           => ['labels' => [], 'values' => []],
            'note_type_donut'           => ['labels' => [], 'values' => [], 'total' => 0, 'associated' => 0],
            'production_open_histogram' => ['labels' => [], 'values' => [], 'normal_values' => [], 'ri_values' => []],
            'production_daily'          => ['labels' => [], 'assigned' => [], 'delivered' => []],
            'internal_return_donut'     => ['labels' => [], 'values' => []],
            'recent_completed'          => [],
            'week'                      => null,
        ];
    }
}
