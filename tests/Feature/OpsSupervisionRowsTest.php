<?php

namespace Tests\Feature;

use App\Models\{Note, Partial, Production};
use App\Services\Supervision\{SupervisionControlRowPresenter, SupervisionListRowPresenter};
use App\Support\Ops\RowState;
use Tests\TestCase;

// Regras de linha e de cor das listas de Fiscalização (sem banco): docs/PADRAO_LISTAS_OPERACIONAIS.md

class OpsSupervisionRowsTest extends TestCase
{
    public function test_row_state_maps_the_supervision_evaluation_to_the_five_states(): void
    {
        $presenter = new SupervisionListRowPresenter();
        $note      = $this->blankNote();
        $row       = fn (array $eval, $stack = null) => $presenter->present(
            $note,
            ['eval' => $eval + ['command' => false, 'production' => null, 'isPartial' => false], 'stack' => $stack, 'canDispatch' => false, 'count' => 0, 'key' => 'k'],
            'srv'
        );

        $this->assertSame(RowState::FREE, $row(['block' => 0, 'reason' => 'sem_producao'])['state']);
        $this->assertSame(RowState::ASSIGNED, $row(['block' => 1, 'reason' => 'producao_em_andamento'])['state']);
        $this->assertSame(RowState::STACK, $row(['block' => 2, 'reason' => 'producao_status_guarda'])['state']);
        $this->assertSame(RowState::DONE, $row(['block' => 3, 'reason' => 'producao_concluida'])['state']);
        $this->assertSame(RowState::LATE, $row(['block' => 4, 'reason' => 'producao_confirmada'])['state']);
        // Informe rejeitado conta como problema (vermelho), como diz a legenda.
        $this->assertSame(RowState::LATE, $row(['block' => 2, 'reason' => 'workform_rejeitado'])['state']);
        // Com atividade aberta na pilha da empresa a linha fica livre (a empresa pode assumir).
        $this->assertSame(RowState::FREE, $row(['block' => 2, 'reason' => 'producao_status_guarda'], new Production())['state']);
        // O tooltip é texto em português, não o código interno do avaliador.
        $this->assertStringNotContainsString('producao_', $row(['block' => 1, 'reason' => 'producao_em_andamento'])['reason']);
    }

    public function test_informed_date_tone_keeps_the_original_thresholds(): void
    {
        $presenter = new SupervisionListRowPresenter();
        $tone      = function (int $days) use ($presenter) {
            $note    = $this->blankNote();
            $partial = (new Partial())->forceFill(['allow' => true, 'deny' => false, 'created_at' => now()->subDays($days)])->setRelation('Orders', collect());
            $note->setRelation('Partials', collect([$partial]));

            return $presenter->present($note, ['eval' => ['block' => 0, 'command' => true, 'reason' => 'x', 'production' => null, 'isPartial' => true], 'stack' => null, 'canDispatch' => true, 'count' => 0, 'key' => 'k'], 'srv')['informedTone'];
        };

        $this->assertSame('green', $tone(20));
        $this->assertSame('amber', $tone(21));
        $this->assertSame('amber', $tone(27));
        $this->assertSame('red', $tone(28));
    }

    public function test_control_row_tones_keep_the_original_thresholds(): void
    {
        $presenter = new SupervisionControlRowPresenter();
        $make      = function (int $informed, int $dispatched, int $assigned) {
            $item = (new Production())->forceFill([
                'id'          => 1, 'note_id' => 1, 'status' => 2, 'dt_inform' => now()->subDays($informed)->toDateTimeString(),
                'dispatch_at' => now()->subDays($dispatched), 'att_at' => now()->subDays($assigned),
            ]);
            $item->setRelation('note', $this->blankNote());
            $item->setRelation('wpas', collect());
            $item->setRelation('WorkReportFlowProductions', collect());

            return $item;
        };

        $green = $presenter->present($make(5, 21, 7));
        $this->assertSame(['green', 'green', 'green'], [$green['informedTone'], $green['dispatchTone'], $green['attTone']]);

        $amber = $presenter->present($make(8, 30, 9));
        $this->assertSame(['amber', 'amber', 'amber'], [$amber['informedTone'], $amber['dispatchTone'], $amber['attTone']]);

        $red = $presenter->present($make(9, 31, 10));
        $this->assertSame(['red', 'red', 'red'], [$red['informedTone'], $red['dispatchTone'], $red['attTone']]);
    }

    public function test_d5_second_cycle_rows_use_secondary_dates_with_d5_instead_of_days(): void
    {
        // Lista de Despacho: D5 concluída aguardando fiscalização.
        $note    = $this->blankNote();
        $partial = (new Partial())->forceFill(['allow' => true, 'deny' => false, 'created_at' => now()->subDays(40)])->setRelation('Orders', collect());
        $note->setRelation('Partials', collect([$partial]));
        $note->setRelation('FiveNote', (new \App\Models\FiveNote())->forceFill(['is_completed' => true, 'is_supervisioned' => false]));

        $row = (new SupervisionListRowPresenter())->present($note, ['eval' => ['block' => 0, 'command' => true, 'reason' => 'x', 'production' => null, 'isPartial' => true], 'stack' => null, 'canDispatch' => true, 'count' => 0, 'key' => 'k'], 'srv');

        $this->assertTrue($row['cycleTwo']);
        $this->assertSame(['secondary', 'D5'], [$row['informedTone'], $row['informedText']]);

        // Controle: produção de D5 (dfive) que não é RI.
        $item = (new Production())->forceFill(['id' => 1, 'note_id' => 1, 'status' => 2, 'dfive' => true, 'd5' => false, 'dt_inform' => now()->subDays(40)->toDateTimeString()]);
        $item->setRelation('note', $this->blankNote());
        $item->setRelation('wpas', collect());
        $item->setRelation('WorkReportFlowProductions', collect());

        $control = (new SupervisionControlRowPresenter())->present($item);

        $this->assertTrue($control['cycleTwo']);
        $this->assertSame('secondary', $control['informedTone']);
    }

    public function test_views_have_no_inline_css_js_or_bootstrap_color_classes(): void
    {
        $files = [
            'livewire/dispatchs/supervision/main.blade.php',
            'livewire/dispatchs/supervision/stack.blade.php',
            'livewire/dispatchs/supervision/partials/dispatch-list.blade.php',
            'livewire/dispatchs/supervision/partials/control-note-extras.blade.php',
            'livewire/dispatchs/fiscalizacao/partner_main.blade.php',
            'livewire/dispatchs/fiscalizacao/partner_stack.blade.php',
            'livewire/services/supervision/main.blade.php',
            'livewire/services/supervision/partials/note-extras.blade.php',
        ];

        foreach ($files as $file) {
            $view = file_get_contents(resource_path('views/' . $file));

            $this->assertStringNotContainsString('<style', $view, $file);
            $this->assertStringNotContainsString('<script', $view, $file);
            $this->assertStringNotContainsString('text-bg-', $view, $file);
            $this->assertStringNotContainsString('components.filter.filter', $view, $file);
            $this->assertDoesNotMatchRegularExpression('/\btable-(primary|warning|success|danger|dark|info|striped)\b/', $view, $file);
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $view, $file);
            $this->assertDoesNotMatchRegularExpression('/style="cursor/', $view, $file);
            $this->assertStringNotContainsString('.bounce.', $view, $file);
        }
    }

    private function blankNote(): Note
    {
        $note     = new Note(['note' => '4000009999']);
        $note->id = 99;

        foreach (['Partials', 'OldAds', 'orders'] as $relation) {
            $note->setRelation($relation, collect());
        }

        foreach (['WorkForm', 'workform', 'Adsform', 'FiveNote'] as $relation) {
            $note->setRelation($relation, null);
        }

        foreach (['Productions', 'Wpas'] as $relation) {
            $note->setRelation($relation, collect());
        }

        return $note;
    }
}
