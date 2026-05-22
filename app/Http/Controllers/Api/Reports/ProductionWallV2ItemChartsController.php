<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Wall\WallDataOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionWallV2ItemChartsController extends Controller
{
    public function __invoke(Request $request, int $wall, int $screen, string $serviceId, WallDataOrchestrator $service): JsonResponse
    {
        $component = $request->query('component');

        return response()->json($service->getItemChartsPayload($wall, $screen, $serviceId, $component));
    }
}
