<?php

namespace App\Exports\Dispatchs;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

class DispatchSupervisionStack implements FromView, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected Builder $builder;
    protected string $serviceUuid;

    public function __construct(Builder $builder, string $serviceUuid)
    {
        $this->builder = $builder;
        $this->serviceUuid = $serviceUuid;
    }

    public function view(): View
    {
        $lists = $this->builder->get();

        return view('exports.dispatchs.supervisionStack', [
            'lists' => $lists,
        ]);
    }

    public function title(): string
    {
        return 'Fiscalização_' . Carbon::now()->format('Ymd');
    }
}
