<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectDownloadProjectOnly extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public $files, public $filtro = 'PROJETO')
    {
        if ($this->files->count() > 0) {

            $this->files = $this->files->filter(function ($file) {
                return str_starts_with($file->file_name, $this->filtro);
            });
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.select-download-project-only');
    }
}
