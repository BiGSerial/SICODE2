<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Services\Admin\Company\CompanyBulkSchema as S;
use Maatwebsite\Excel\Concerns\{FromArray, WithCustomValueBinder, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DataValidation, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Color, Conditional, Fill, NumberFormat};

/**
 * Aba de dados da planilha em massa: cabeçalho fixo, colunas protegidas,
 * listas suspensas, validação de entrada e destaque de duplicidade/obrigatórios.
 */
class BulkDataSheet extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(
        private readonly string $title,
        private readonly array $headers,
        private readonly array $rows,
        private readonly array $config
    ) {
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Texto sempre fica texto: nada de "=cmd" virar fórmula nem "0012" virar 12.
        if (is_string($value) && $value !== '') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function array(): array
    {
        return array_merge([$this->headers], $this->rows);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->decorate($event)];
    }

    private function decorate(AfterSheet $event): void
    {
        $sheet   = $event->sheet->getDelegate();
        $cfg     = $this->config;
        $count   = count($this->headers);
        $lastCol = chr(64 + $count);
        $last    = S::MAX_ROWS + 1;
        $dataEnd = $lastCol;

        // cabeçalho: obrigatórios em verde, opcionais em cinza-azulado
        $this->styleHeader($sheet, "A1:{$lastCol}1");
        $sheet->getRowDimension(1)->setRowHeight(26);

        foreach ($this->headers as $index => $header) {
            $col = chr(65 + $index);

            if (!str_contains($header, '*') && $col !== 'A') {
                $sheet->getStyle("{$col}1")->getFill()->getStartColor()->setRGB('475569');
            }
        }

        $this->styleBody($sheet, "A1:{$lastCol}{$last}");
        $this->setWidths($sheet, array_combine(array_map(fn ($i) => chr(65 + $i), array_keys($cfg['widths'])), $cfg['widths']));

        // formatos
        foreach ($cfg['text'] ?? [] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        foreach ($cfg['dates'] ?? [] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle("{$col}2:{$col}{$last}")->getAlignment()->setHorizontal('center');
        }

        // colunas calculadas
        foreach ($cfg['formulas'] ?? [] as $col => $builder) {
            for ($r = 2; $r <= $last; $r++) {
                $sheet->setCellValueExplicit("{$col}{$r}", $builder($r), DataType::TYPE_FORMULA);
            }
        }

        // edição liberada só nas colunas de dados
        $locked = $cfg['locked'] ?? [];

        foreach (range('A', $lastCol) as $col) {
            if (!in_array($col, $locked, true)) {
                $this->unlockRange($sheet, "{$col}2:{$col}{$last}");
            }
        }

        foreach ($locked as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        }

        foreach ($cfg['hidden'] ?? [] as $col) {
            $sheet->getColumnDimension($col)->setVisible(false);
        }

        // validação
        foreach ($cfg['dv'] as [$col, $spec]) {
            $sheet->setDataValidation("{$col}2:{$col}{$last}", $this->validation($spec));
        }

        // destaques: obrigatório faltando e duplicidade
        $rules = [];

        foreach ($cfg['required'] ?? [] as $col) {
            $rules["{$col}2:{$col}{$last}"][] = $this->conditional("AND(COUNTA(\$B2:\${$dataEnd}2)>0,{$col}2=\"\",\$A2<>\"Manter\")", 'FEE2E2', '991B1B');
        }

        if (!empty($cfg['dupe'])) {
            [$col, $formula]                  = $cfg['dupe'];
            $rules["{$col}2:{$col}{$last}"][] = $this->conditional("AND({$col}2<>\"\",{$formula})", 'FECACA', '7F1D1D');
        }

        foreach ($rules as $range => $conditions) {
            $sheet->getStyle($range)->setConditionalStyles($conditions);
        }

        $this->freezeAndFilter($event, 'A2', "A1:{$lastCol}{$last}");
        $this->protectSheet($sheet);
        $sheet->getTabColor()->setRGB($cfg['tab']);
    }

    private function validation(array $spec): DataValidation
    {
        $dv = new DataValidation();
        $dv->setShowErrorMessage(true)->setShowInputMessage(false)->setAllowBlank(true)->setErrorStyle(DataValidation::STYLE_STOP);
        $dv->setErrorTitle('Valor inválido')->setError($spec['msg'] ?? 'Escolha um valor da lista.');

        if (isset($spec['list'])) {
            $dv->setType(DataValidation::TYPE_LIST)->setShowDropDown(true)->setFormula1($spec['list']);
        } elseif (isset($spec['len'])) {
            $dv->setType(DataValidation::TYPE_TEXTLENGTH)->setOperator(DataValidation::OPERATOR_BETWEEN)->setFormula1((string) $spec['len'][0])->setFormula2((string) $spec['len'][1]);
        } elseif (isset($spec['date'])) {
            $dv->setType(DataValidation::TYPE_DATE)->setOperator(DataValidation::OPERATOR_BETWEEN)->setFormula1('36526')->setFormula2('73415');
        } else {
            $dv->setType(DataValidation::TYPE_CUSTOM)->setFormula1($spec['custom']);
        }

        return $dv;
    }

    private function conditional(string $formula, string $bg, string $fg): Conditional
    {
        $c = new Conditional();
        $c->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition($formula);
        $c->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setRGB($bg);
        $c->getStyle()->getFont()->setColor(new Color($fg));

        return $c;
    }
}
