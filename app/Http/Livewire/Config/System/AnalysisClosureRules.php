<?php

namespace App\Http\Livewire\Config\System;

use App\Models\SystemSetting;
use App\Support\SicodeRules;
use Livewire\Component;

class AnalysisClosureRules extends Component
{
    public string $ruleset = 'es';
    public bool $environmentWithoutReason = false;
    public string $analysisConclusions = '';
    public string $preAnalysisConclusions = '';

    public function mount(): void
    {
        $this->ruleset = SicodeRules::ruleset();
        $this->loadRules();
    }

    public function updatedRuleset(): void
    {
        $this->loadRules();
    }

    public function loadRules(): void
    {
        $this->environmentWithoutReason = $this->boolSetting(
            'analysis.environment_without_reason',
            (bool) config("sicode.rules.{$this->ruleset}.analysis.environment_without_reason", false)
        );

        $this->analysisConclusions = $this->optionsToText($this->optionsSetting(
            'analysis.conclusions',
            (array) config("sicode.rules.{$this->ruleset}.analysis.conclusions", [])
        ));

        $this->preAnalysisConclusions = $this->optionsToText($this->optionsSetting(
            'analysis.pre_analysis_conclusions',
            (array) config("sicode.rules.{$this->ruleset}.analysis.pre_analysis_conclusions", [])
        ));
    }

    public function save(): void
    {
        SystemSetting::setValue(
            $this->settingKey('analysis.environment_without_reason'),
            $this->environmentWithoutReason ? 'true' : 'false'
        );

        SystemSetting::setValue(
            $this->settingKey('analysis.conclusions'),
            json_encode($this->textToOptions($this->analysisConclusions), JSON_UNESCAPED_UNICODE)
        );

        SystemSetting::setValue(
            $this->settingKey('analysis.pre_analysis_conclusions'),
            json_encode($this->textToOptions($this->preAnalysisConclusions), JSON_UNESCAPED_UNICODE)
        );

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Parâmetros salvos',
            'text' => 'As regras de encerramento foram atualizadas.',
            'timer' => 5000,
        ]);
    }

    public function resetToConfig(): void
    {
        foreach ([
            'analysis.environment_without_reason',
            'analysis.conclusions',
            'analysis.pre_analysis_conclusions',
        ] as $key) {
            SystemSetting::setValue($this->settingKey($key), null);
        }

        $this->loadRules();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Parâmetros restaurados',
            'text' => 'As regras voltaram a usar o fallback do arquivo de configuração.',
            'timer' => 5000,
        ]);
    }

    protected function settingKey(string $key): string
    {
        return "sicode.rules.{$this->ruleset}.{$key}";
    }

    protected function boolSetting(string $key, bool $default): bool
    {
        $value = SystemSetting::getValue($this->settingKey($key));

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    protected function optionsSetting(string $key, array $default): array
    {
        $value = SystemSetting::getValue($this->settingKey($key));

        if ($value === null || $value === '') {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    protected function optionsToText(array $options): string
    {
        return collect($options)
            ->map(fn ($label, $value) => ((string) $value) . '|' . ((string) $label))
            ->implode("\n");
    }

    protected function textToOptions(string $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $options = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            [$optionValue, $label] = array_pad(explode('|', $line, 2), 2, null);
            $optionValue = trim((string) $optionValue);
            $label = trim((string) ($label ?: $optionValue));

            if ($optionValue !== '' && $label !== '') {
                $options[$optionValue] = $label;
            }
        }

        return $options;
    }

    public function render()
    {
        return view('livewire.config.system.analysis-closure-rules');
    }
}
