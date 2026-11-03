<?php

namespace App\Exports\Dispatchs;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{Exportable, FromView, ShouldAutoSize, WithTitle};

class DispatchSurveyStack implements FromView, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected Builder $builder;

    protected string $serviceUuid;

    public function __construct(Builder $builder, string $serviceUuid)
    {
        $this->builder     = $builder;
        $this->serviceUuid = $serviceUuid;
    }

    public function view(): View
    {
        $lists = $this->builder->get();

        return view('exports.dispatchs.surveyStack', [
            'lists' => $lists,
        ]);
    }

    public function title(): string
    {
        return 'Levantamento_' . Carbon::now()->format('Ymd');
    }
}
