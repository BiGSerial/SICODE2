<?php

namespace App\Services\Reports\WallV2\Contracts;

use App\Models\WallScreen;
use App\Services\Reports\WallV2\ScreenContext;

interface WallScreenDataService
{
    public function buildScreenPayload(WallScreen $screen, ScreenContext $context): array;

    public function buildScreenManifestPayload(WallScreen $screen, ScreenContext $context): array;

    public function buildSingleItemPayload(WallScreen $screen, ScreenContext $context, string $serviceId): ?array;
}
