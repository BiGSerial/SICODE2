<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class ReferenceListsSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(private readonly Company $root, private readonly Collection $units)
    {
    }

    public function array(): array
    {
        $contracts = $this->units
            ->flatMap(fn ($unit) => $unit->contracts->map(fn ($contract) => [
                'label' => "{$contract->number} | {$unit->display_name}",
                'unit' => $unit->display_name,
                'validity' => $contract->date_end ? date('d/m/Y', strtotime($contract->date_end)) : '',
            ]))
            ->values();

        $maxRows = max($this->units->count(), $contracts->count(), 1);
        $rows = [['Empresas/Unidades validas', 'Contratos validos', 'Contrato pertence a', 'Validade']];

        for ($index = 0; $index < $maxRows; $index++) {
            $rows[] = [
                $this->units[$index]->display_name ?? '',
                $contracts[$index]['label'] ?? '',
                $contracts[$index]['unit'] ?? '',
                $contracts[$index]['validity'] ?? '',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Listas';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $sheet->getHighestRow());
                $this->styleHeader($sheet, 'A1:D1');
                $this->styleBody($sheet, "A1:D{$lastRow}");
                $this->setWidths($sheet, ['A' => 38, 'B' => 44, 'C' => 38, 'D' => 14]);
                $this->freezeAndFilter($event, 'A2', "A1:D{$lastRow}");
                $this->protectSheet($sheet);
                $sheet->getTabColor()->setRGB('64748B');
            },
        ];
    }
}
