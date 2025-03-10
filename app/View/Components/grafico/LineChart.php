<?php

namespace App\View\Components\grafico;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LineChart extends Component
{
    public $chartId;
    public $labels;
    public $dataset;
    public $height;
    public $width;
    public $title;

    public function __construct(string $chartId = 'default-chart', array $labels = [], array $dataset = [], string $height = null, string $width = null, string $title = null)
    {
        $this->chartId = $chartId;
        $this->labels = $labels;
        $this->dataset = $dataset;
        $this->height = $height;
        $this->width = $width;
        $this->title = $title;
    }



    public function render(): View|Closure|string
    {
        return view('components.grafico.line-chart', [
            'chartId' => $this->chartId,
            'labels'  => $this->labels,
            'dataset' => $this->dataset,
            'height'  => $this->height,
            'width'   => $this->width,
            'title'   => $this->title,
        ]);
    }
}
