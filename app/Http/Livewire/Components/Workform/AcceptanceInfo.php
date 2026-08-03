<?php

namespace App\Http\Livewire\Components\Workform;

use App\Models\WorkReport;
use App\Services\WorkReports\WorkReportAcceptanceSignature;
use Livewire\Component;

class AcceptanceInfo extends Component
{
    public ?WorkReport $workReport = null;

    protected $listeners = [
        'openAcceptanceInfo',
    ];

    public function openAcceptanceInfo(WorkReport $workReport)
    {
        $this->workReport = WorkReport::query()
            ->with([
                'Note:id,note',
                'Company:id,name',
                'User:id,name,email',
            ])
            ->find($workReport->id);

        if (!$this->workReport) {
            return;
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'workAcceptanceInfoModal',
        ]);
    }

    public function render()
    {
        $meta = $this->workReport?->acceptance_meta ?? [];
        $signature = $meta['current']['signature'] ?? $meta['signature'] ?? [];
        $signatureService = app(WorkReportAcceptanceSignature::class);

        return view('livewire.components.workform.acceptance-info', [
            'signature' => is_array($signature) ? $signature : [],
            'acceptedText' => is_array($signature) && isset($signature['signed_text'])
                ? (string) $signature['signed_text']
                : $signatureService->signedText(),
        ]);
    }
}
