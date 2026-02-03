<?php

namespace App\Exports\Dispatchs;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

class CancellationHistoryExport implements FromView, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected Builder $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function view(): View
    {
        return view('exports.dispatchs.cancellation-history', [
            'rows' => $this->builder->get(),
        ]);
    }

    public function title(): string
    {
        return 'Cancelamentos_' . Carbon::now()->format('Ymd');
    }
}
