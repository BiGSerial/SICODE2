<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PartnerUserBulkImport implements ToArray, WithHeadingRow
{
    public array $rows = [];

    public function array(array $rows): void
    {
        $this->rows = $rows;
    }
}
