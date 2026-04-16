<?php

namespace App\Services\Wall\Contracts;

use App\Models\WallScreen;
use App\Services\Wall\Context\ScreenContext;

interface WallScreenDataService
{
    public function buildScreenPayload(WallScreen $screen, ScreenContext $context): array;

    public function buildScreenManifestPayload(WallScreen $screen, ScreenContext $context): array;

    public function buildSingleItemPayload(WallScreen $screen, ScreenContext $context, string $serviceId): ?array;
}
