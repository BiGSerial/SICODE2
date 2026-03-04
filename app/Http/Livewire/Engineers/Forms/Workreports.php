<?php

namespace App\Http\Livewire\Engineers\Forms;

class Workreports extends \App\Http\Livewire\Partner\Forms\Workreports
{
    public bool $requireFilesForSubmit = false;
    public bool $canSelectCompany = true;

    public function render()
    {
        return view('livewire.partner.forms.workreports');
    }
}

