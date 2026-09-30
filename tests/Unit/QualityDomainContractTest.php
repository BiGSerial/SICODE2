<?php

use App\Enum\{QualityEventType, QualityProcessState, QualityProcessStatus, QualityStageLevel, QualityStageType};
use App\Services\Quality\BudgetOrderRules;

it('mantém contratos estáveis dos enums e o titular de cada estado', function () {
    expect(QualityProcessStatus::ACTIVE->value)->toBe('ACTIVE')
        ->and(QualityStageType::PROJECT->short())->toBe('1º ciclo')
        ->and(QualityStageType::BUDGET->short())->toBe('2º ciclo')
        ->and(QualityEventType::REJECTED->value)->toBe('REJECTED');

    expect(QualityProcessState::AWAITING_N1_DISPATCH->holder())->toBe(QualityStageLevel::N1)
        ->and(QualityProcessState::N2_RETURNED->holder())->toBe(QualityStageLevel::N1)
        ->and(QualityProcessState::AWAITING_DESIGNER->holder())->toBe(QualityStageLevel::DRAWING)
        ->and(QualityProcessState::SAP_FAILED->holder())->toBe(QualityStageLevel::N2)
        ->and(QualityProcessState::COMPLETED->holder())->toBeNull();
});

it('todo estado e todo evento possuem rótulo em português', function () {
    foreach (QualityProcessState::cases() as $state) {
        expect($state->label())->not->toBe('')->not->toMatch('/^[A-Z_]+$/');
    }

    foreach (QualityEventType::cases() as $type) {
        expect($type->label())->not->toBe('')->not->toMatch('/^[A-Z_]+$/');
    }
});

it('valida ordens de orçamento com as regras do encerramento normal', function () {
    $rules = new BudgetOrderRules();

    expect($rules->orderNumberError('170123456789', '100'))->toBeNull()
        ->and($rules->orderNumberError('12345', '100'))->toContain('12 dígitos')
        ->and($rules->orderNumberError('999123456789', '100'))->toContain('prefixo')
        ->and($rules->orderNumberError('170123456789', '300'))->toContain('200')
        ->and($rules->normalizeNumber('1.234,50'))->toBe(1234.5);
});
