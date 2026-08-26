<?php

namespace App\Http\Livewire\Config\System;

use App\Models\{SystemSetting, SystemSettingVersion};
use App\Services\Partner\DashboardLegalNotices;
use Livewire\Component;

class PartnerDashboardLegalNotes extends Component
{
    public string $adsDueText = '';

    public string $adsOverdueText = '';

    public string $valuesDisclaimerText = '';

    protected $rules = [
        'adsDueText'           => 'required|string|max:5000',
        'adsOverdueText'       => 'required|string|max:5000',
        'valuesDisclaimerText' => 'required|string|max:5000',
    ];

    protected $messages = [
        'adsDueText.required'           => 'O aviso de "Entregas de ADS a vencer" não pode ficar vazio.',
        'adsOverdueText.required'       => 'O aviso de "Entregas de ADS em atraso" não pode ficar vazio.',
        'valuesDisclaimerText.required' => 'O aviso de "Valores meramente informativos" não pode ficar vazio.',
    ];

    public function mount(DashboardLegalNotices $notices): void
    {
        $this->adsDueText           = $notices->adsDueText();
        $this->adsOverdueText       = $notices->adsOverdueText();
        $this->valuesDisclaimerText = $notices->valuesDisclaimerText();
    }

    public function save(): void
    {
        $this->validate();

        SystemSetting::setValue(DashboardLegalNotices::ADS_DUE_SETTING_KEY, $this->adsDueText);
        SystemSetting::setValue(DashboardLegalNotices::ADS_OVERDUE_SETTING_KEY, $this->adsOverdueText);
        SystemSetting::setValue(DashboardLegalNotices::VALUES_DISCLAIMER_SETTING_KEY, $this->valuesDisclaimerText);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Avisos atualizados com sucesso.',
            'timer'    => 2000,
        ]);
    }

    public function resetToDefault(DashboardLegalNotices $notices): void
    {
        $this->adsDueText           = $notices->defaultAdsDueText();
        $this->adsOverdueText       = $notices->defaultAdsOverdueText();
        $this->valuesDisclaimerText = $notices->defaultValuesDisclaimerText();
    }

    public function restoreVersion(string $field, int $versionId): void
    {
        $key = match ($field) {
            'adsDueText'           => DashboardLegalNotices::ADS_DUE_SETTING_KEY,
            'adsOverdueText'       => DashboardLegalNotices::ADS_OVERDUE_SETTING_KEY,
            'valuesDisclaimerText' => DashboardLegalNotices::VALUES_DISCLAIMER_SETTING_KEY,
            default                => null,
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
        return view('livewire.config.system.partner-dashboard-legal-notes', [
            'adsDueHistory'           => SystemSettingVersion::historyFor(DashboardLegalNotices::ADS_DUE_SETTING_KEY),
            'adsOverdueHistory'       => SystemSettingVersion::historyFor(DashboardLegalNotices::ADS_OVERDUE_SETTING_KEY),
            'valuesDisclaimerHistory' => SystemSettingVersion::historyFor(DashboardLegalNotices::VALUES_DISCLAIMER_SETTING_KEY),
        ]);
    }
}
