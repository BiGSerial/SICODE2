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
        $acceptedText = is_array($signature) && isset($signature['signed_text'])
            ? (string) $signature['signed_text']
            : $signatureService->signedText();

        return view('livewire.components.workform.acceptance-info', [
            'signature' => is_array($signature) ? $signature : [],
            'acceptedHtml' => $this->formatAcceptedText($acceptedText),
        ]);
    }

    private function formatAcceptedText(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '<p>Termo nao informado.</p>';
        }

        if ($text === strip_tags($text)) {
            return '<p>' . nl2br(e($text), false) . '</p>';
        }

        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $text) ?? '';
        $text = strip_tags($text, '<p><br><strong><b><em><i><u><ol><ul><li>');

        return preg_replace_callback(
            '#<(/?)(p|br|strong|b|em|i|u|ol|ul|li)\b[^>]*>#i',
            fn (array $match) => '<' . $match[1] . strtolower($match[2]) . '>',
            $text
        ) ?: '<p>Termo nao informado.</p>';
    }
}
