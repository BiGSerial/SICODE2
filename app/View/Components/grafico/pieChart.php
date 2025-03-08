<?php

namespace App\View\Components\grafico;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class pieChart extends Component
{
    public $chartId;
    public $labels;
    public $dataset;
    public $height;
    public $width;

    public function __construct(string $chartId = 'default-chart', array $labels = [], array $dataset = [], string $height = null, string $width = null)
    {
        $this->chartId = $chartId;
        $this->labels = $labels;
        $this->dataset = $dataset;
        $this->height = $height;
        $this->width = $width;
    }

    public function render()
    {
        return view('components.grafico.pie-chart', [
            'chartId' => $this->chartId,
            'labels'  => $this->labels,
            'dataset' => $this->dataset,
            'height'  => $this->height,
            'width'   => $this->width,
        ]);
    }
}
