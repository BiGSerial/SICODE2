<?php

namespace App\Exports\Admin\Company;

use App\Exports\Admin\Company\Sheets\ContractsSheet;
use App\Exports\Admin\Company\Sheets\MatrixSheet;
use App\Exports\Admin\Company\Sheets\ReferenceListsSheet;
use App\Exports\Admin\Company\Sheets\UnitsSheet;
use App\Exports\Admin\Company\Sheets\UsersSheet;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserRegistrationWorkbookExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly Company $company)
    {
        $this->company->loadMissing(
            'parent',
            'Address',
            'contracts.services',
            'branches.Address',
            'branches.contracts.services'
        );
    }

    public function sheets(): array
    {
        $root = $this->company->parent ?: $this->company;
        $units = collect([$root])
            ->merge($root->branches)
            ->unique('id')
            ->values();

        return [
            new MatrixSheet($root),
            new ContractsSheet($root, $units),
            new UnitsSheet($root, $units),
            new UsersSheet($root, $units),
            new ReferenceListsSheet($root, $units),
        ];
    }
}
