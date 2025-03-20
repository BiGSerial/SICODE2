<?php

namespace App\View\Components\grafico;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class multistackbar extends Component
{
    public $chartId;
    public $labels;
    public $datasets;
    public $title;
    public $height;
    public $width;
    public $yAxisTitle;

    /**
     * Create a new component instance.
     *
     * @param  array  $labels
     * @param  array  $datasets - An array of datasets.  Each dataset should have 'name' and 'data' keys.
     * @param  string|null $title
     * @param  string|null $height
     * @param  string|null $width
     * @param  string|null $yAxisTitle
     * @return void
     */
    public function __construct(
        string $chartId = 'default-chart',
        array $labels,
        array $datasets,
        string $title = null,
        string $height = null,
        string $width = null,
        string $yAxisTitle = null
    ) {
        $this->chartId = $chartId;
        $this->labels = $labels;
        $this->datasets = $datasets;
        $this->title = $title;
        $this->height = $height;
        $this->width = $width;
        $this->yAxisTitle = $yAxisTitle;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.grafico.multistackbar');
    }
}
