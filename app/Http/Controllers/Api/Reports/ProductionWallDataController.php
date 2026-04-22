<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProductionWallDataService;
use Illuminate\Http\JsonResponse;

class ProductionWallDataController extends Controller
{
    public function __invoke(ProductionWallDataService $service): JsonResponse
    {
        return response()->json($service->getPayload());
    }
}
