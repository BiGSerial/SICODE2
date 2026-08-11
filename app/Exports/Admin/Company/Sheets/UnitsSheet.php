<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class UnitsSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(private readonly Company $root, private readonly Collection $units)
    {
    }

    public function array(): array
    {
        $rows = [
            ['FICHA DE FILIAIS E UNIDADES'],
            ['Empresa concentradora', $this->root->name, 'Gerado em', now()->format('d/m/Y H:i')],
            ['Use Criar para novas unidades. Linhas existentes aparecem como referencia e podem ser revisadas antes do upload.'],
            [],
            [
            'Acao',
            'Empresa concentradora',
            'Unidade',
            'Email',
            'Telefone',
            'Endereco',
            'Municipio',
            'UF',
            ],
        ];

        foreach ($this->units->where('id', '!=', $this->root->id) as $unit) {
            $address = $unit->Address->first();
            $rows[] = [
                'Manter',
                $this->root->name,
                $unit->name,
                $unit->email,
                $unit->telephone,
                $address?->street,
                $address?->city,
                $address?->uf,
            ];
        }

        for ($i = count($rows); $i < 29; $i++) {
            $rows[] = ['Criar', $this->root->name, '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Filiais';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 5;
                $firstDataRow = 6;
                $lastRow = max(29, $sheet->getHighestRow());
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A3:H3');
                $this->styleTitle($sheet, 'A1', 'FICHA DE FILIAIS E UNIDADES');
                $this->styleSubtleHeader($sheet, 'A2:D2');
                $sheet->getStyle('A3:H3')->getFont()->setItalic(true);
                $sheet->getStyle('A3:H3')->getAlignment()->setWrapText(true);
                $this->styleHeader($sheet, "A{$headerRow}:H{$headerRow}");
                $this->styleBody($sheet, "A1:H{$lastRow}");
                $this->setWidths($sheet, ['A' => 14, 'B' => 30, 'C' => 30, 'D' => 34, 'E' => 18, 'F' => 34, 'G' => 22, 'H' => 8]);
                $this->freezeAndFilter($event, "A{$firstDataRow}", "A{$headerRow}:H{$lastRow}");
                $sheet->getTabColor()->setRGB('F59E0B');
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(30);

                for ($row = $firstDataRow; $row <= $lastRow; $row++) {
                    $validation = $sheet->getCell("A{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"Manter,Criar,Atualizar"');
                }
            },
        ];
    }
}
