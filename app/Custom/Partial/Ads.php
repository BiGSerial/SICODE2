<?php

namespace App\Custom\Partial;

use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Ads implements WithCalculatedFormulas
{
    /** @var array<string, Worksheet|null> */
    private static array $cache = [];

    private ?Worksheet $checklist = null;

    private bool $exists = false;

    public string $note = '';
    public string $company = '';
    public string $contract = '';
    public string $center = '';
    public string $deposit = '';
    public float $value = 0.0;
    public bool $partial = false;

    public function __construct(string $path)
    {
        if (!is_readable($path)) {
            return;
        }

        if (!array_key_exists($path, self::$cache)) {
            self::$cache[$path] = $this->loadChecklist($path);
        }

        $this->checklist = self::$cache[$path];

        if (!$this->checklist) {
            return;
        }

        $this->fillProperties();

        $this->exists = true;
    }

    private function loadChecklist(string $path): ?Worksheet
    {
        try {
            $reader = IOFactory::createReaderForFile($path);

            /*
             * false é importante:
             * precisamos saber se a célula original é fórmula ou valor direto.
             */
            $reader->setReadDataOnly(false);

            $reader->setLoadSheetsOnly(['Check-list']);
            $reader->setReadFilter(new ADSReadFilter());

            $spreadsheet = $reader->load($path);

            return $spreadsheet->getSheetByName('Check-list');
        } catch (\Throwable) {
            return null;
        }
    }

    private function fillProperties(): void
    {
        $this->note = $this->stringValue('G4');
        $this->company = $this->stringValue('G5');
        $this->contract = $this->stringValue('G6');
        $this->center = $this->stringValue('G7');
        $this->deposit = $this->stringValue('G8');

        /*
         * Qualquer conteúdo diferente de null/vazio ativa a flag.
         */
        $this->partial = $this->hasValue('W7');

        $this->value = $this->resolveValue();
    }

    /**
     * Regra:
     *
     * 1. Tenta Q13.
     * 2. Se Q13 estiver vazio:
     *      verifica se O12 contém "Serviço".
     * 3. Se contiver, usa Q12.
     * 4. Senão, retorna zero.
     */
    private function resolveValue(): float
    {
        $q13 = $this->cellValue('Q13');

        if ($this->valueIsFilled($q13)) {
            return $this->toFloat($q13);
        }

        $description = $this->stringValue('O12');

        if (
            $description !== ''
            && stripos($description, 'serviço') !== false
        ) {
            return $this->floatValue('Q12');
        }

        return 0.0;
    }

    /**
     * Retorna o valor efetivo da célula.
     *
     * - valor comum: retorna diretamente;
     * - fórmula: usa primeiro o valor calculado salvo no Excel;
     * - se não houver cache calculado, tenta calcular.
     */
    private function cellValue(string $coord): mixed
    {
        if (!$this->checklist) {
            return null;
        }

        $cell = $this->checklist->getCell($coord);

        /*
         * Se NÃO for fórmula, não há motivo algum
         * para chamar getCalculatedValue().
         */
        if ($cell->getDataType() !== DataType::TYPE_FORMULA) {
            return $cell->getValue();
        }

        /*
         * Fórmula:
         * primeiro tenta o último resultado calculado
         * armazenado dentro do próprio Excel.
         */
        $cachedValue = $cell->getOldCalculatedValue();

        if ($this->valueIsFilled($cachedValue)) {
            return $cachedValue;
        }

        /*
         * Só recalcula como último recurso.
         */
        try {
            return $cell->getCalculatedValue();
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasValue(string $coord): bool
    {
        return $this->valueIsFilled(
            $this->cellValue($coord)
        );
    }

    private function valueIsFilled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        /*
         * Importante:
         * 0 e 0.0 são valores válidos.
         */
        return true;
    }

    private function stringValue(string $coord): string
    {
        $value = $this->cellValue($coord);

        return $value === null
            ? ''
            : trim((string) $value);
    }

    private function floatValue(string $coord): float
    {
        return $this->toFloat(
            $this->cellValue($coord)
        );
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        /*
         * Caso normal vindo do Excel.
         */
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        /*
         * Fallback caso o Excel entregue valor formatado:
         *
         * R$ 1.234,56
         * 1.234,56
         */
        $normalized = preg_replace(
            '/[^\d,.\-]/',
            '',
            (string) $value
        );

        if ($normalized === null || $normalized === '') {
            return 0.0;
        }

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized)
            ? round((float) $normalized, 2)
            : 0.0;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getContract(): string
    {
        return $this->contract;
    }

    public function getCenter(): string
    {
        return $this->center;
    }

    public function getDeposit(): string
    {
        return $this->deposit;
    }

    public function getPartial(): bool
    {
        return $this->partial;
    }

    public function getValue(): float
    {
        return $this->value;
    }
}
