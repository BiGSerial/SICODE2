<?php

namespace App\Custom\Partial;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class Ads implements WithCalculatedFormulas
{
    public string $note;
    public string $company;
    public string $contract;
    public string $center;
    public string $deposit;
    public float $value = 0.0;
    public bool $partial;

    public $spreadsheet;

    private bool $exists = false;

    public function __construct(string $path)
    {

        if (! is_readable($path)) {
            return;
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['ADS', 'Check-list']);
        $reader->setReadFilter(new ADSReadFilter());


        try {
            $this->spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            return;
        }

        $cl = $this->spreadsheet->getSheetByName('Check-list');
        if (! $cl) {
            return;
        }



        $this->note     = (string) $this->getCachedValue($cl, 'G4');
        $this->company  = (string) $this->getCachedValue($cl, 'G5');
        $this->contract = (string) $this->getCachedValue($cl, 'G6');
        $this->center   = (string) $this->getCachedValue($cl, 'G7');
        $this->deposit  = (string) $this->getCachedValue($cl, 'G8');
        $this->value  = (float) $this->getCachedValue($cl, 'Q13');
        $this->partial  = (bool)   $this->getCachedValue($cl, 'W7');

        $this->exists = true;

    }

    private function getCachedValue($sheet, string $coord)
    {
        $cell = $sheet->getCell($coord);
        $old  = $cell->getOldCalculatedValue();
        if ($old !== null) {
            return $old;
        }
        return $cell->getCalculatedValue();
    }

    public function setExists(bool $exists)
    {
        $this->exists = $exists;
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
