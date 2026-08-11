<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class UserRegistrationErrorSheet implements FromArray, WithHeadings, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        if ($this->rows) {
            return $this->rows;
        }

        $row = array_fill(0, count($this->headings), '');
        $row[min(1, count($row) - 1)] = 'Nenhuma inconsistencia encontrada.';

        return [$row];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = chr(ord('A') + count($this->headings) - 1);
                $lastRow = max(2, $sheet->getHighestRow());
                $this->styleHeader($sheet, "A1:{$lastColumn}1");
                $this->styleBody($sheet, "A1:{$lastColumn}{$lastRow}");
                $this->freezeAndFilter($event, 'A2', "A1:{$lastColumn}{$lastRow}");
                $this->setWidths($sheet, ['A' => 10, 'B' => 14, 'C' => 28, 'D' => 34, 'E' => 34, 'F' => 38, 'G' => 54, 'H' => 54]);
            },
        ];
    }
}
