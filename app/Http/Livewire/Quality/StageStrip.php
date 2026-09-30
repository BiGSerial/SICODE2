<?php

namespace App\Http\Livewire\Quality;

use App\Services\Quality\QualityBoard;
use Livewire\Component;

/** Faixa de etapas (abas) do N1/N2 com contagens atualizadas por polling. */
class StageStrip extends Component
{
    public string $level = 'n1';

    public string $tab = '';

    public function mount(string $level, string $tab): void
    {
        $this->level = $level;
        $this->tab   = $tab;
    }

    public function render()
    {
        $board = app(QualityBoard::class);
        $n1    = $this->level === 'n1';

        return view('livewire.quality.stage-strip', [
            'tabs'      => $board->tabs(auth()->user(), $n1 ? QualityBoard::N1_TABS : QualityBoard::N2_TABS),
            'routeName' => $n1 ? 'quality.n1' : 'quality.n2',
            'extra'     => $n1 ? [] : [['label' => 'Por empresa', 'href' => route('quality.n2', 'empresas'), 'icon' => 'ri-building-line', 'hint' => 'Onde estão as obras', 'active' => $this->tab === 'empresas']],
        ]);
    }
}
