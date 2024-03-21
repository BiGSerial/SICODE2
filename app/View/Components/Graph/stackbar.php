<?php

namespace App\View\Components\Graph;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class stackbar extends Component
{
    public $type;

    public $labels;

    public $data;

    public $chartId;

    /**
     * Create a new component instance.
     */
    public function __construct($type, $labels, $data, $chartId)
    {
        $this->type    = $type;
        $this->labels  = $labels;
        $this->data    = $data;
        $this->chartId = $chartId;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.graph.stackbar');
    }
}
