<?php

namespace App\View\Components\Production;

use App\Models\Production;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ActionProduction extends Component
{
    public ?Production $production = null;
    /**
     * Create a new component instance.
     */
    public function __construct(?Production $production = null)
    {
        $this->production = $production;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.production.action-production', [
            'production' => $this->production,
        ]);
    }
}
