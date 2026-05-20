<?php

namespace App\Http\Livewire\Legal\Management;

use App\Models\Legal\LegalCase;
use Livewire\Component;

class ReportExport extends Component
{
    public string  $activeReport = '';

    public bool    $showConfigModal = false;

    // Campos comuns
    public string $reportPeriodFrom = '';

    public string $reportPeriodTo = '';

    public string $reportSourceType = '';

    public string $reportArea = '';

    public string $reportRegional = '';

    // Específicos
    public string  $reportAssigneeId = '';

    public string  $caseSearch = '';

    public ?int    $selectedCaseId = null;

    public string  $criticalityWindow = 'overdue';

    public string  $monthlyRange = '6';

    // Casos para autocomplete
    public array $caseOptions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('legal.reports'), 403);
    }

    public function openConfig(string $report): void
    {
        $this->activeReport    = $report;
        $this->showConfigModal = true;
    }

    public function closeModal(): void
    {
        $this->showConfigModal = false;
        $this->activeReport    = '';
    }

    public function updatedCaseSearch(): void
    {
        if (strlen($this->caseSearch) >= 2) {
            $this->caseOptions = LegalCase::query()
                ->where('case_number', 'like', "%{$this->caseSearch}%")
                ->orWhere('company_name', 'like', "%{$this->caseSearch}%")
                ->limit(10)
                ->get(['id', 'case_number', 'company_name'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => "{$c->case_number} — {$c->company_name}"])
                ->toArray();
        } else {
            $this->caseOptions = [];
        }
    }

    public function selectCase(int $id, string $label): void
    {
        $this->selectedCaseId = $id;
        $this->caseSearch     = $label;
        $this->caseOptions    = [];
    }

    public function generate(): void
    {
        $this->validate([
            'reportPeriodFrom' => 'required_if:activeReport,position,criticality,by_assignee,by_area,monthly',
            'reportPeriodTo'   => 'required_if:activeReport,position,criticality,by_assignee,by_area,monthly',
        ]);

        $jobClass = match ($this->activeReport) {
            'position'    => \App\Jobs\Legal\Reports\ExportLegalPositionReportJob::class,
            'criticality' => \App\Jobs\Legal\Reports\ExportLegalCriticalityReportJob::class,
            'by_assignee' => \App\Jobs\Legal\Reports\ExportLegalByAssigneeReportJob::class,
            'by_area'     => \App\Jobs\Legal\Reports\ExportLegalByAreaReportJob::class,
            'case_full'   => \App\Jobs\Legal\Reports\ExportLegalCaseFullReportJob::class,
            'monthly'     => \App\Jobs\Legal\Reports\ExportLegalMonthlyEvolutionReportJob::class,
            default       => null,
        };

        if ($jobClass && class_exists($jobClass)) {
            $jobClass::dispatch([
                'period_from'        => $this->reportPeriodFrom,
                'period_to'          => $this->reportPeriodTo,
                'source_type'        => $this->reportSourceType,
                'area'               => $this->reportArea,
                'regional'           => $this->reportRegional,
                'assignee_id'        => $this->reportAssigneeId,
                'case_id'            => $this->selectedCaseId,
                'criticality_window' => $this->criticalityWindow,
                'monthly_range'      => $this->monthlyRange,
            ], auth()->id());
        }

        $this->closeModal();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Relatório em geração',
            'html'  => 'Você receberá uma notificação quando o relatório estiver pronto.',
            'timer' => 4000,
        ]);
    }

    public function render()
    {
        return view('livewire.legal.management.report-export');
    }
}
