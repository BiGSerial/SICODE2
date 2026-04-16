<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Wall\WallDataOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionWallV2DataController extends Controller
{
    public function __invoke(Request $request, int $wall, WallDataOrchestrator $service): JsonResponse
    {
        $manifest = filter_var($request->query('manifest', false), FILTER_VALIDATE_BOOLEAN);

        return response()->json(
            $manifest
                ? $service->getManifestForWall($wall)
                : $service->getPayloadForWall($wall)
        );
    }
}
