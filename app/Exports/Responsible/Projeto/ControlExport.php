<?php

namespace App\Exports\Responsible\Projeto;

use App\Models\Note;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;

class ControlExport implements FromQuery
{


    public function query()
    {
        return Note::query(); 
    }
}
