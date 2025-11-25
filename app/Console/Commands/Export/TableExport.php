<?php

namespace App\Console\Commands\Export;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TableExport implements FromCollection, WithHeadings, WithTitle
{
    protected string $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function collection()
    {
        return DB::table($this->table)->get();
    }

    public function headings(): array
    {
        $first = DB::table($this->table)->first();

        if ($first) {
            return array_keys((array) $first);
        }

        return DB::getSchemaBuilder()->getColumnListing($this->table);
    }

    public function title(): string
    {
        return ucfirst(substr($this->table, 0, 31));
    }
}
