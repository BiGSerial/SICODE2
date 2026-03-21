<?php

namespace App\Exports\ProjectReview\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ArraySheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $headings,
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }
}

