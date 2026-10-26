<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Services\Admin\Company\CompanyBulkSchema as S;
use Maatwebsite\Excel\Concerns\{FromArray, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/** Fonte das listas suspensas: itens já cadastrados + itens digitados nas abas desta mesma planilha. */
class BulkListsSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(
        private readonly array $staticUnits,
        private readonly array $staticContracts,
        private readonly array $services,
        private readonly array $roots
    ) {
    }

    public function title(): string
    {
        return S::LISTS;
    }

    public function array(): array
    {
        $rows = [['Empresas / Unidades', 'Contratos', 'Atividades', 'Matrizes']];
        $max  = max(count($this->services), count($this->roots), count($this->staticUnits), count($this->staticContracts));

        for ($i = 0; $i < $max; $i++) {
            $rows[] = [$this->staticUnits[$i] ?? null, $this->staticContracts[$i] ?? null, $this->services[$i] ?? null, $this->roots[$i] ?? null];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Colunas A e B continuam com fórmulas que puxam o que foi digitado nas outras abas.
                foreach ([['A', count($this->staticUnits)], ['B', count($this->staticContracts)], ['D', count($this->roots)]] as [$col, $static]) {
                    for ($n = 0; $n < S::MAX_ROWS; $n++) {
                        $src     = $n + 2;
                        $formula = match ($col) {
                            'A'     => "=IF('" . S::COMPANIES . "'!K{$src}=\"\",\"\",'" . S::COMPANIES . "'!K{$src})",
                            'B'     => "=IF('" . S::CONTRACTS . "'!F{$src}=\"\",\"\",'" . S::CONTRACTS . "'!F{$src})",
                            default => "=IF(AND('" . S::COMPANIES . "'!B{$src}<>\"\",'" . S::COMPANIES . "'!C{$src}=\"\"),'" . S::COMPANIES . "'!B{$src},\"\")",
                        };
                        $sheet->setCellValueExplicit($col . ($static + 2 + $n), $formula, DataType::TYPE_FORMULA);
                    }
                }

                $this->styleSubtleHeader($sheet, 'A1:D1');
                $this->setWidths($sheet, ['A' => 46, 'B' => 56, 'C' => 40, 'D' => 34]);
                $this->protectSheet($sheet);
                $sheet->getTabColor()->setRGB('94A3B8');
            },
        ];
    }
}
