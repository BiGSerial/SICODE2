<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Wall\Fixed\ProjectReviewFixedDashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionWallV2FixedProjectReviewController extends Controller
{
    public function __invoke(Request $request, int $wall, int $screen, ProjectReviewFixedDashboardDataService $service): JsonResponse
    {
        $component = $request->query('component');

        return response()->json($service->getPayload($wall, $screen, $component));
    }
}
