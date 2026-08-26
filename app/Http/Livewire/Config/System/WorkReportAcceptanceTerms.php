<?php

namespace App\Http\Livewire\Config\System;

use App\Models\{SystemSetting, SystemSettingVersion};
use App\Services\WorkReports\WorkReportAcceptanceSignature;
use Livewire\Component;

class WorkReportAcceptanceTerms extends Component
{
    public string $statementText = '';

    public string $contractText = '';

    protected $rules = [
        'statementText' => 'required|string|max:5000',
        'contractText'  => 'required|string|max:5000',
    ];

    protected $messages = [
        'statementText.required' => 'A declaração de responsabilidade não pode ficar vazia.',
        'contractText.required'  => 'A citação contratual não pode ficar vazia.',
    ];

    public function mount(WorkReportAcceptanceSignature $signature): void
    {
        $this->statementText = $signature->statementText();
        $this->contractText  = $signature->contractText();
    }

    public function save(WorkReportAcceptanceSignature $signature): void
    {
        $this->validate();

        SystemSetting::setValue(WorkReportAcceptanceSignature::STATEMENT_SETTING_KEY, $this->statementText);
        SystemSetting::setValue(WorkReportAcceptanceSignature::CONTRACT_SETTING_KEY, $this->contractText);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Termo atualizado com sucesso.',
            'html'     => 'Vale apenas para novos informes; assinaturas já registradas mantêm o texto que foi aceito na época.',
        ]);
    }

    public function resetToDefault(WorkReportAcceptanceSignature $signature): void
    {
        $this->statementText = $signature->defaultStatementText();
        $this->contractText  = $signature->defaultContractText();
    }

    public function restoreVersion(string $field, int $versionId): void
    {
        $key = match ($field) {
            'statementText' => WorkReportAcceptanceSignature::STATEMENT_SETTING_KEY,
            'contractText'  => WorkReportAcceptanceSignature::CONTRACT_SETTING_KEY,
            default         => null,
        };

        if (!$key) {
            return;
        }

        $version = SystemSettingVersion::query()->where('key', $key)->find($versionId);

        if ($version && property_exists($this, $field)) {
            $this->{$field} = (string) $version->value;
        }
    }

    public function render()
    {
        return view('livewire.config.system.work-report-acceptance-terms', [
            'statementHistory' => SystemSettingVersion::historyFor(WorkReportAcceptanceSignature::STATEMENT_SETTING_KEY),
            'contractHistory'  => SystemSettingVersion::historyFor(WorkReportAcceptanceSignature::CONTRACT_SETTING_KEY),
        ]);
    }
}
