<?php

namespace App\Custom\Partial;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ADSReadFilter implements IReadFilter
{
    public function readCell($column, $row, $worksheetName = '')
    {



        $cellsToRead = [

            'Check-list' => ['G4', 'G5', 'G6', 'G7', 'G8', 'W7', 'Q13', 'R13', 'S13'] // Outras células, se necessário
        ];

        return isset($cellsToRead[$worksheetName]) &&
            in_array($column . $row, $cellsToRead[$worksheetName]);
    }
}
