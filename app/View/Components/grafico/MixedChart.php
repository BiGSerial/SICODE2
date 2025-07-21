<?php

namespace App\View\Components\grafico;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MixedChart extends Component
{
    public $chartId;
    public $labels;
    public $data;
    public $height;
    public $width;
    public $title;

    public function __construct(
        string $chartId = 'default-mixed-chart',
        array $labels = [],
        array $data = [], // Array de datasets, cada um com 'type', 'name', 'data'
        string $height = null,
        string $width = null,
        string $title = null
    ) {
        $this->chartId = $chartId;
        $this->labels = $labels;
        $this->data = $data; // Formato: [['type' => 'line', 'name' => 'Linha', 'data' => [1,2,3]], ['type' => 'bar', 'name' => 'Barra', 'data' => [4,5,6]]]
        $this->height = $height;
        $this->width = $width;
        $this->title = $title;
    }

    public function render(): View|Closure|string
    {
        return view('components.grafico.mixed-chart', [
            'chartId' => $this->chartId,
            'labels' => $this->labels,
            'data' => $this->data,
            'height' => $this->height,
            'width' => $this->width,
            'title' => $this->title,
        ]);
    }
}
