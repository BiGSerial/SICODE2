<?php

namespace Tests\Unit;

use App\Support\SicodeRules;
use Tests\TestCase;

class SicodeRulesAnalysisClosureTest extends TestCase
{
    public function test_es_analysis_keeps_status_21_and_environment_reason(): void
    {
        config(['sicode.ruleset' => 'es']);

        $this->assertFalse(SicodeRules::analysisEnvironmentWithoutReason());
        $this->assertArrayHasKey('ENVIADO PARA O STATUS 21', SicodeRules::analysisConclusionOptions());
        $this->assertTrue(SicodeRules::isValidAnalysisConclusion('ENVIADO PARA O STATUS 21'));
        $this->assertFalse(SicodeRules::isValidAnalysisConclusion('ENVIADO PARA O STATUS 3'));
    }

    public function test_sp_analysis_replaces_status_21_with_status_3_and_4(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $this->assertTrue(SicodeRules::analysisEnvironmentWithoutReason());
        $this->assertArrayNotHasKey('ENVIADO PARA O STATUS 21', SicodeRules::analysisConclusionOptions());
        $this->assertTrue(SicodeRules::isValidAnalysisConclusion('ENVIADO PARA O STATUS 3'));
        $this->assertTrue(SicodeRules::isValidAnalysisConclusion('ENVIADO PARA O STATUS 4'));
        $this->assertFalse(SicodeRules::isValidAnalysisConclusion('ENVIADO PARA O STATUS 21'));
    }

    public function test_sp_pre_analysis_allows_status_3_and_4_without_status_21(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $this->assertArrayNotHasKey('ENVIADO PARA O STATUS 21', SicodeRules::preAnalysisConclusionOptions());
        $this->assertTrue(SicodeRules::isValidPreAnalysisConclusion('ENVIADO PARA O STATUS 3'));
        $this->assertTrue(SicodeRules::isValidPreAnalysisConclusion('ENVIADO PARA O STATUS 4'));
        $this->assertFalse(SicodeRules::isValidPreAnalysisConclusion('ENVIADO PARA O STATUS 21'));
    }
}
