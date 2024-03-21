<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{Exportable, FromView, WithProperties};

class ProductionServiceExport implements FromView, WithProperties
{
    use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // public function getData()
    // {
    //     return $this->data;
    // }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE REPORTS',
            'lastModifiedBy' => 'SICODE REPORTS',
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function view(): View
    {
        return view('exports.productionsService', [
            'lists' => $this->data,
        ]);
    }
}
