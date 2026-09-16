<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportGeneralSicodeReportJob;
use App\Services\Reports\GeneralSicodeReportService;
use Livewire\Component;

class GeneralSicode extends Component
{
    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $company_id = null;

    protected $queryString = [
        'date_from' => ['except' => '', 'as' => 'ini'],
        'date_to' => ['except' => '', 'as' => 'fim'],
        'company_id' => ['except' => '', 'as' => 'empresa'],
    ];

    public function mount(): void
    {
        $this->date_from = $this->date_from ?: now()->startOfMonth()->format('Y-m-d');
        $this->date_to = $this->date_to ?: now()->format('Y-m-d');
    }

    public function clearFilters(): void
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
        $this->company_id = null;
    }

    public function exportReport(): void
    {
        ExportGeneralSicodeReportJob::dispatch($this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>O Relatório Geral SICODE está sendo gerado em Excel.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver disponível.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function filters(): array
    {
        return [
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'company_id' => $this->company_id,
        ];
    }

    public function render()
    {
        $service = app(GeneralSicodeReportService::class);
        $report = $service->report($this->filters());

        return view('livewire.reports.general-sicode', [
            'report' => $report,
            'rows' => $report['rows'],
            'companies' => $service->companies(),
        ]);
    }
}
