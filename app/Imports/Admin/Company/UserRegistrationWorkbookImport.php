<?php

namespace App\Imports\Admin\Company;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserRegistrationWorkbookImport implements WithMultipleSheets
{
    public array $users = [];
    public array $units = [];

    public function sheets(): array
    {
        return [
            'Usuarios' => new class ($this) implements ToArray {
                public function __construct(private readonly UserRegistrationWorkbookImport $import)
                {
                }

                public function array(array $rows): void
                {
                    $this->import->users = $rows;
                }
            },
            'Filiais' => new class ($this) implements ToArray {
                public function __construct(private readonly UserRegistrationWorkbookImport $import)
                {
                }

                public function array(array $rows): void
                {
                    $this->import->units = $rows;
                }
            },
        ];
    }
}
