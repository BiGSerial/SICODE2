<?php

namespace Tests\Feature;

use App\Services\Design\BlockEvaluator;
use App\Services\Survey\SurveyListRowPresenter;
use App\Support\Ops\RowState;
use Tests\TestCase;

// Regras puras (sem banco): situação da linha, faixas de prazo e higiene das views.
class OpsSurveyPatternTest extends TestCase
{
    private const VIEWS = [
        'dispatchs/survey/main',
        'dispatchs/survey/stack',
        'dispatchs/survey/partials/stack-note-extras',
        'dispatchs/levantamento/partner_main',
        'dispatchs/levantamento/partner_stack',
        'services/levantamento/main',
    ];

    public function test_row_state_maps_the_design_evaluator_levels_to_the_five_situations(): void
    {
        $presenter = new SurveyListRowPresenter();

        $this->assertSame(RowState::FREE, $presenter->rowState(BlockEvaluator::FREE));
        $this->assertSame(RowState::ASSIGNED, $presenter->rowState(BlockEvaluator::HOLD_BLUE));
        $this->assertSame(RowState::STACK, $presenter->rowState(BlockEvaluator::HOLD_YELLOW));
        $this->assertSame(RowState::DONE, $presenter->rowState(BlockEvaluator::HOLD_GREEN));
        $this->assertSame(RowState::LATE, $presenter->rowState(BlockEvaluator::HOLD_RED));
        $this->assertSame(RowState::ASSIGNED, $presenter->rowState(BlockEvaluator::HOLD_INCONST));
    }

    public function test_real_deadline_tone_keeps_the_original_limits(): void
    {
        $presenter = new SurveyListRowPresenter();

        $this->assertSame('red', $presenter->realDeadline(-1)['tone']);
        $this->assertSame('red', $presenter->realDeadline(5)['tone']);
        $this->assertSame('amber', $presenter->realDeadline(6)['tone']);
        $this->assertSame('amber', $presenter->realDeadline(9)['tone']);
        $this->assertSame('green', $presenter->realDeadline(10)['tone']);
        $this->assertSame(20, $presenter->realDeadline(10)['elapsed']);

        $this->assertSame('green', $presenter->limitTone(21, 30));
        $this->assertSame('amber', $presenter->limitTone(22, 30));
        $this->assertSame('red', $presenter->limitTone(31, 30));
        $this->assertSame('green', $presenter->limitTone(7, 9));
        $this->assertSame('red', $presenter->limitTone(10, 9));
    }

    public function test_views_have_no_inline_style_script_or_legacy_classes(): void
    {
        foreach (self::VIEWS as $view) {
            $source = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

            $this->assertStringNotContainsString('<style', $source, $view);
            $this->assertStringNotContainsString('<script', $source, $view);
            $this->assertStringNotContainsString('text-bg-', $source, $view);
            $this->assertStringNotContainsString('table-danger', $source, $view);
            $this->assertStringNotContainsString('table-warning', $source, $view);
            $this->assertStringNotContainsString('table-success', $source, $view);
            $this->assertStringNotContainsString('components.filter.filter', $source, $view);
            $this->assertStringNotContainsString('.bounce.', $source, $view);
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', preg_replace('/&#\d+;|href="#"|data-bs-target="#[\w-]+"|id="#|#\{/', '', $source), $view);
            // Único style aceito: largura de coluna do colgroup (dado da view).
            $this->assertDoesNotMatchRegularExpression('/style="/', preg_replace('/<col style="width: [^"]+">/', '', $source), $view);
        }
    }
}
