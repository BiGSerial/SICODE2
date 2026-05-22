<?php

namespace App\Http\Livewire\Reports;

use App\Services\Reports\ProductionWallDataService;
use Livewire\Component;

class ProductionWall extends Component
{
    public array $payload = [
        'updated_at' => '',
        'slides' => [],
    ];

    public int $rotationSeconds = 180;

    public int $refreshSeconds = 60;

    public function mount(ProductionWallDataService $service): void
    {
        $this->payload = $service->getPayload();
    }

    public function render()
    {
        return view('livewire.reports.production-wall', [
            'initialPayload' => $this->payload,
            'apiEndpoint' => route('api.v1.reports.production_wall'),
        ]);
    }
}
