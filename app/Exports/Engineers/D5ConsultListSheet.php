<?php

namespace App\Exports\Engineers;

use App\Exports\Partner\FiveNotesExport;
use Maatwebsite\Excel\Concerns\WithTitle;

/** A lista de D5 da consulta (mesma do Excel de sempre) como uma aba do workbook analítico. */
class D5ConsultListSheet extends FiveNotesExport implements WithTitle
{
    public function title(): string
    {
        return 'Lista D5';
    }
}
