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

it('normaliza campos monetários vazios e mantém o rateio fechado no total', function () {
    $rules = new BudgetOrderRules();

    expect($rules->normalizeCosts([
        'total_cost'   => '100,00',
        'company_cost' => '',
        'client_cost'  => '',
    ]))->toBe([
        'total_cost'   => 100.0,
        'company_cost' => 0.0,
        'client_cost'  => 100.0,
    ])->and($rules->normalizeCosts([
        'total_cost'   => '',
        'company_cost' => '35,50',
        'client_cost'  => '',
    ]))->toBe([
        'total_cost'   => 35.5,
        'company_cost' => 35.5,
        'client_cost'  => 0.0,
    ])->and($rules->normalizeCosts([
        'total_cost'   => '',
        'company_cost' => '',
        'client_cost'  => '',
    ]))->toBe([
        'total_cost'   => 0.0,
        'company_cost' => 0.0,
        'client_cost'  => 0.0,
    ]);
});
