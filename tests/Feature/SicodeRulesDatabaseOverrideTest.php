<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Support\SicodeRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SicodeRulesDatabaseOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_override_replaces_analysis_conclusions_for_ruleset(): void
    {
        config(['sicode.ruleset' => 'sp']);

        SystemSetting::setValue('sicode.rules.sp.analysis.conclusions', json_encode([
            'CUSTOM STATUS' => 'CUSTOM STATUS',
        ]));

        $this->assertSame(['CUSTOM STATUS' => 'CUSTOM STATUS'], SicodeRules::analysisConclusionOptions());
        $this->assertTrue(SicodeRules::isValidAnalysisConclusion('CUSTOM STATUS'));
        $this->assertFalse(SicodeRules::isValidAnalysisConclusion('ENVIADO PARA O STATUS 3'));
    }

    public function test_database_override_controls_environment_reason_rule(): void
    {
        config(['sicode.ruleset' => 'es']);

        $this->assertFalse(SicodeRules::analysisEnvironmentWithoutReason());

        SystemSetting::setValue('sicode.rules.es.analysis.environment_without_reason', 'true');

        $this->assertTrue(SicodeRules::analysisEnvironmentWithoutReason());
    }
}
