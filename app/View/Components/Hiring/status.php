<?php

namespace App\View\Components\Hiring;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;




class status extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public $badge)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.hiring.status');
    }
}
