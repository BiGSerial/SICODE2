<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProductionWallV2DataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductionWallV2ItemChartsController extends Controller
{
    public function __invoke(Request $request, int $wall, int $screen, string $serviceId, ProductionWallV2DataService $serviceData): JsonResponse
    {
        $component = $request->query('component');

        return response()->json($serviceData->getItemChartsPayload($wall, $screen, $serviceId, $component));
    }
}
