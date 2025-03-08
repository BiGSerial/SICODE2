<?php

namespace App\View\Components\grafico;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class stackBar extends Component
{
    public $chartId;
    public $labels;
    public $dataset1Label;
    public $dataset1Data;
    public $dataset2Label;
    public $dataset2Data;
    public $title;
    public $height;
    public $width;
    public $yAxisTitle;

    /**
     * Create a new component instance.
     *
     * @param  array  $labels
     * @param  array  $dataset1Data
     * @param  string  $dataset1Label
     * @param  array  $dataset2Data
     * @param  string  $dataset2Label
     * @param  string|null $title
     * @param  string|null $height
     * @param  string|null $width
     * @param  string|null $yAxisTitle
     * @return void
     */
    public function __construct(
        string $chartId = 'default-chart',
        array $labels,
        array $dataset1Data,
        string $dataset1Label,
        array $dataset2Data,
        string $dataset2Label,
        string $title = null,
        string $height = null,
        string $width = null,
        string $yAxisTitle = null
    ) {
        $this->chartId = $chartId;
        $this->labels = $labels;
        $this->dataset1Label = $dataset1Label;
        $this->dataset1Data = $dataset1Data;
        $this->dataset2Label = $dataset2Label;
        $this->dataset2Data = $dataset2Data;
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
        return view('components.grafico.stack-bar');
    }
}
