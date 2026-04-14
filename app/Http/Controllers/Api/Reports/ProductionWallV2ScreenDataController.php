<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProductionWallV2DataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionWallV2ScreenDataController extends Controller
{
    public function __invoke(Request $request, int $wall, int $screen, ProductionWallV2DataService $service): JsonResponse
    {
        $manifest = filter_var($request->query('manifest', false), FILTER_VALIDATE_BOOLEAN);
        if ($manifest) {
            return response()->json($service->getManifestForWall($wall, $screen));
        }

        return response()->json($service->getPayloadForWall($wall, $screen));
    }
}
