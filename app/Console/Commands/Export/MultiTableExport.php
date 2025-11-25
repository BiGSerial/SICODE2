<?php

namespace App\Console\Commands\Export;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiTableExport implements WithMultipleSheets
{
    /**
     * @var array<string>
     */
    protected array $tables;

    /**
     * @param array<string> $tables
     */
    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->tables as $table) {
            $sheets[] = new TableExport($table);
        }

        return $sheets;
    }
}
