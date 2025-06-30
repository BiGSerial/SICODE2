<?php

namespace App\Custom\Partial;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class Ads implements WithCalculatedFormulas
{
    /** @var Worksheet[] Caminho ⇒ sheet “Check-list” já carregada */
    private static array $cache = [];

    private Worksheet $checklist;
    private bool $exists = false;

    public string $note     = '';
    public string $company  = '';
    public string $contract = '';
    public string $center   = '';
    public string $deposit  = '';
    public float  $value    = 0.0;
    public bool   $partial  = false;

    /**
     * @param string $path Caminho completo do arquivo .xlsx/.xls
     */
    public function __construct(string $path)
    {
        if (! is_readable($path)) {
            return;
        }

        // Carrega e filtra só uma vez por caminho
        if (! isset(self::$cache[$path])) {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['Check-list']);
            $reader->setReadFilter(new ADSReadFilter());

            try {
                $ss = $reader->load($path);
                $sheet = $ss->getSheetByName('Check-list');
            } catch (\Throwable) {
                return;
            }

            self::$cache[$path] = $sheet;
        }

        $this->checklist = self::$cache[$path];

        if (! $this->checklist) {
            return;
        }

        // Preenche propriedades
        $this->note     = (string) $this->getCell('G4');
        $this->company  = (string) $this->getCell('G5');
        $this->contract = (string) $this->getCell('G6');
        $this->center   = (string) $this->getCell('G7');
        $this->deposit  = (string) $this->getCell('G8');
        $this->value    = (float)   $this->getCell('Q13');
        $this->partial  = (bool)    $this->getCell('W7');

        $this->exists = true;
    }

    private function getCell(string $coord): mixed
    {
        $cell = $this->checklist->getCell($coord);
        $old  = $cell->getOldCalculatedValue();

        return $old !== null
            ? $old
            : $cell->getCalculatedValue();
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
