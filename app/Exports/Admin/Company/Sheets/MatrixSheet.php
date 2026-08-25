<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class MatrixSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(private readonly Company $company)
    {
    }

    public function array(): array
    {
        $address = $this->company->Address->first();

        return [
            ['FICHA DA EMPRESA CONCENTRADORA'],
            ['Gerado pelo SICODE', now()->format('d/m/Y H:i')],
            ['Esta aba é informativa. Os dados da matriz ficam fixos para orientar o preenchimento das demais abas.'],
            [],
            ['Campo', 'Valor'],
            ['Empresa concentradora', $this->company->name],
            ['Email principal', $this->company->email],
            ['Telefone', $this->company->telephone],
            ['Endereco principal', $address?->street],
            ['Municipio', $address?->city],
            ['UF', $address?->uf],
            [],
            ['Orientacao', 'Preencha a aba Usuarios. Use a aba Filiais apenas para cadastrar novas unidades ou revisar unidades existentes.'],
        ];
    }

    public function title(): string
    {
        return 'Matriz';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:B1');
                $sheet->mergeCells('A3:B3');
                $this->styleTitle($sheet, 'A1', 'FICHA DA EMPRESA CONCENTRADORA');
                $this->styleSubtleHeader($sheet, 'A2:B2');
                $sheet->getStyle('A3:B3')->getFont()->setItalic(true);
                $sheet->getStyle('A3:B3')->getAlignment()->setWrapText(true);
                $this->styleHeader($sheet, 'A5:B5');
                $this->styleBody($sheet, 'A1:B13');
                $this->setWidths($sheet, ['A' => 28, 'B' => 82]);
                $this->protectSheet($sheet);
                $sheet->getTabColor()->setRGB('0F766E');
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(34);
                $sheet->getStyle('A13:B13')->getFont()->setBold(true);
                $sheet->getRowDimension(13)->setRowHeight(36);
                $sheet->getStyle('B13')->getAlignment()->setWrapText(true);
            },
        ];
    }
}
