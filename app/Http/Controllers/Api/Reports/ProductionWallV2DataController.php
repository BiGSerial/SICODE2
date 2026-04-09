<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProductionWallV2DataService;
use Illuminate\Http\JsonResponse;

class ProductionWallV2DataController extends Controller
{
    public function __invoke(int $wall, ProductionWallV2DataService $service): JsonResponse
    {
        return response()->json($service->getPayloadForWall($wall));
    }
}
