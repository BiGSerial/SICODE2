<?php

namespace App\Http\Livewire\Components\Filter;

use Livewire\Component;

class RemoveAll extends Component
{
    public $group_filter;

    public function mount($group_filter)
    {
        $this->group_filter = $group_filter;
    }

    public function clean_all_filters()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        session()->forget("filter.{$this->group_filter}");

        if (isset($_SESSION['filter'][$this->group_filter])) {
            unset($_SESSION['filter'][$this->group_filter]);

        }

        $this->emitUp('refresh_list');
        $this->emit('refresh_All_Filter');

    }

    public function render()
    {
        return view('livewire.components.filter.remove-all');
    }
}
