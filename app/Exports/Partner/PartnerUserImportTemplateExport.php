<?php

namespace App\Exports\Partner;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PartnerUserImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return ['Nome', 'Email', 'Filial'];
    }

    public function array(): array
    {
        return [
            ['Maria Silva', 'maria.silva@example.com', 'Filial Centro'],
        ];
    }
}
