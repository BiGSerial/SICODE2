<?php

namespace App\Services\Wall\Context;

use App\Models\WallScreen;

class ScreenContextResolver
{
    public function resolve(WallScreen $screen): ScreenContext
    {
        $screenType = (string) ($screen->screen_type ?: 'production_services');
        $screenConfig = (array) ($screen->screen_config ?? []);
        $fixedChart = (string) ($screenConfig['fixed_chart'] ?? '');

        if ($screenType === 'ads_chart') {
            return new ScreenContext('fixed_chart', 'ads_dashboard');
        }

        if ($screenType === 'fixed_chart') {
            return new ScreenContext('fixed_chart', $fixedChart);
        }

        return new ScreenContext('production_services', '');
    }
}
