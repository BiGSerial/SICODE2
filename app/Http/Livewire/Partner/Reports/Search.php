<?php

namespace App\Http\Livewire\Partner\Reports;

class Search extends \App\Http\Livewire\Reports\Search
{
    public function render()
    {
        return view('livewire.partner.reports.search', [
            'lists' => $this->lists,
        ]);
    }
}

