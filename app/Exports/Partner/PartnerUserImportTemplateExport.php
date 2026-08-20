<?php

namespace App\Exports\Partner;

use App\Models\Andresscompany;
use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PartnerUserImportTemplateExport implements FromArray, WithColumnWidths, WithEvents, WithHeadings
{
    private Collection $branches;

    public function __construct(private readonly Company $company)
    {
        $companyIds = Company::query()
            ->where('id', $this->company->id)
            ->orWhere('parent_id', $this->company->id)
            ->pluck('id');

        $this->branches = Andresscompany::query()
            ->with('Company:id,parent_id,name')
            ->whereIn('company_id', $companyIds)
            ->orderBy('city')
            ->orderBy('street')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Email',
            'Filial',
            'Orientacao',
            'ID da filial',
            'Filial valida',
            'Empresa',
            'Endereco',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $sampleBranch = $this->branches->first();
        $referenceRows = max(25, $this->branches->count());

        for ($index = 0; $index < $referenceRows; $index++) {
            $branch = $this->branches->get($index);

            $rows[] = [
                $index === 0 ? 'Maria Silva' : '',
                $index === 0 ? 'maria.silva@example.com' : '',
                $index === 0 ? ($sampleBranch?->id ?? '') : '',
                $index === 0 ? 'Preencha Nome, Email e Filial. Use o ID da filial listado ao lado.' : '',
                $branch?->id ?? '',
                $branch ? $this->branchLabel($branch) : '',
                $branch?->Company?->display_name ?? '',
                $branch ? $this->branchAddress($branch) : '',
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 34,
            'B' => 38,
            'C' => 14,
            'D' => 52,
            'E' => 14,
            'F' => 34,
            'G' => 34,
            'H' => 48,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $sheet->getHighestRow());
                $branchListEnd = max(2, $this->branches->count() + 1);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:H{$lastRow}");
                $sheet->getTabColor()->setRGB('2563EB');

                $sheet->getStyle("A1:H1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F172A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A2:C{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                $sheet->getStyle("E2:H{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
                $sheet->getStyle("D2:D{$lastRow}")->getFont()->getColor()->setRGB('64748B');
                $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getComment('A1')->getText()->createTextRun('Obrigatorio. Informe o nome completo do usuario.');
                $sheet->getComment('B1')->getText()->createTextRun('Obrigatorio. O email nao pode existir em outro usuario do sistema.');
                $sheet->getComment('C1')->getText()->createTextRun('Obrigatorio. Use preferencialmente o ID da filial listado na coluna E.');
                $sheet->getComment('E1')->getText()->createTextRun('Referencia. Copie este ID para a coluna Filial.');

                if ($this->branches->isNotEmpty()) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $validation = $sheet->getCell("C{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(false);
                        $validation->setShowDropDown(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setErrorTitle('Filial invalida');
                        $validation->setError('Selecione um ID valido na lista de filiais.');
                        $validation->setFormula1("\$E\$2:\$E\${$branchListEnd}");
                    }
                }
            },
        ];
    }

    private function branchLabel(Andresscompany $branch): string
    {
        return collect([$branch->city, $branch->street, $branch->complement])
            ->filter()
            ->implode(' - ');
    }

    private function branchAddress(Andresscompany $branch): string
    {
        return collect([
            $branch->street,
            $branch->complement,
            trim(collect([$branch->city, $branch->uf])->filter()->implode('/')),
        ])
            ->filter()
            ->implode(' - ');
    }
}
