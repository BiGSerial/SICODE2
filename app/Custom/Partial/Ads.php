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
    public bool $partial;

    public $spreadsheet;

    private bool $exists = false;

    public function __construct(string $path)
    {
        
        try {
            // Configurar o filtro de leitura
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadFilter(new ADSReadFilter());
            $this->spreadsheet = $reader->load($path);

            if (
                !$this->spreadsheet->sheetNameExists('ADS') ||
                !$this->spreadsheet->sheetNameExists('Check-list')
            ) {
                $this->setExists(false);
            }

            $sheet = $this->spreadsheet->getSheetByName('Check-list');

            if ($sheet) {
                $this->note = trim($sheet->getCell('G4')->getCalculatedValue());
                $this->company = trim($sheet->getCell('G5')->getCalculatedValue());
                $this->contract = trim($sheet->getCell('G6')->getCalculatedValue());
                $this->center = trim($sheet->getCell('G7')->getCalculatedValue());
                $this->deposit = trim($sheet->getCell('G8')->getCalculatedValue());
                $this->partial = $sheet->getCell('W7')->getValue() ? true : false;


                $this->setExists(true);
            } else {
                $this->setExists(false);
            }

        } catch (\Exception $e) {
            $this->setExists(false);
        }

    }

    public function setExists(bool $exists)
    {
        $this->exists = $exists;
    }


    public function exist()
    {
        return $this->exists;
    }
    // Métodos GET
    public function getNote(): string
    {
        return $this->note ?? '';
    }

    public function getCompany(): string
    {
        return $this->company ?? '';
    }

    public function getContract(): string
    {
        return $this->contract ?? '';
    }

    public function getCenter(): string
    {
        return $this->center ?? '';
    }

    public function getDeposit(): string
    {
        return $this->deposit ?? '';
    }

    public function getPartial(): bool
    {
        return $this->partial ?? false;
    }



}
