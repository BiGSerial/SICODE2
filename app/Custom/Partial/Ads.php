<?php

namespace App\Custom\Partial;

use PhpOffice\PhpSpreadsheet\IOFactory;

class Ads
{
    public string $note;
    public string $company;
    public bool $partial;
    public float $value;
    public $spreadsheet;

    private bool $exists = false;

    public function __construct(string $path)
    {
        try {

            $this->spreadsheet = IOFactory::load($path);

            if (
                !$this->spreadsheet->sheetNameExists('ADS') ||
                !$this->spreadsheet->sheetNameExists('Check-list')
            ) {
                throw new \Exception("O Arquivo não parece ser uma ADS Válida..");
            }

            $this->spreadsheet->setActiveSheetIndex(0);
            $sheet = $this->spreadsheet->getActiveSheet();

            if ($sheet) {

                $this->note = trim($sheet->getCell('G4')->getCalculatedValue());
                $this->company = trim($sheet->getCell('G5')->getCalculatedValue());
                $this->partial = $sheet->getCell('W7')->getValue() <> '' ? true : false;
                $this->value = (float) trim($sheet->getCell('Q13')->getCalculatedValue());

                $this->setExists(true);
            } else {
                $this->setExists(false);
            }

        } catch (\Exception $e) {
            throw new \Exception("Não foi possível carregar o arquivo: " . $e->getMessage());
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



}
