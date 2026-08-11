<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class ContractsSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(private readonly Company $root, private readonly Collection $units)
    {
    }

    public function array(): array
    {
        $rows = [[
            'Empresa/Unidade',
            'Contrato',
            'Validade',
            'Atividade',
            'Executar',
            'Despachar',
        ]];

        foreach ($this->units as $unit) {
            foreach ($unit->contracts as $contract) {
                foreach ($contract->services as $service) {
                    $rows[] = [
                        $unit->display_name,
                        $contract->number,
                        $contract->date_end ? date('d/m/Y', strtotime($contract->date_end)) : '',
                        $service->service,
                        'Sim',
                        $service->pivot->dispatch ? 'Sim' : 'Nao',
                    ];
                }
            }
        }

        return count($rows) > 1 ? $rows : array_merge($rows, [[$this->root->name, 'Sem contrato ativo', '', '', '', '']]);
    }

    public function title(): string
    {
        return 'Contratos Ativos';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $sheet->getHighestRow());
                $this->styleHeader($sheet, 'A1:F1');
                $this->styleBody($sheet, "A1:F{$lastRow}");
                $this->setWidths($sheet, ['A' => 36, 'B' => 22, 'C' => 14, 'D' => 32, 'E' => 12, 'F' => 12]);
                $this->freezeAndFilter($event, 'A2', "A1:F{$lastRow}");
                $this->protectSheet($sheet);
                $sheet->getTabColor()->setRGB('1D4ED8');
            },
        ];
    }
}
