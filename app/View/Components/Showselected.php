<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Coponente para exibir a quantidade de intens Checkboks Selecionados
 *
 * Componente exibirá um display no canto inferior direito da tela.
 *
 * @property $count Entrada da quantidade de intem (Array ou inteiro);
 */
class Showselected extends Component
{
    public $count;

    /**
     * Create a new component instance.
     */
    public function __construct($count)
    {
        if (is_array($count)) {
            $this->count = count($count);
        } else {
            $this->count = (int) $count;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.showselected');
    }
}
