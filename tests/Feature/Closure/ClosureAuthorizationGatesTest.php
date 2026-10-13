<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function closureGateUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'closure_operator' => false,
        'closure_manager'  => false,
        'admin'            => false,
        'superadm'         => false,
    ], $attributes));
}

it('allows a closure operator to work and view, but not dispatch or manage', function () {
    $user = closureGateUser(['closure_operator' => true]);

    expect($user->can('closure.work'))->toBeTrue()
        ->and($user->can('closure.view'))->toBeTrue()
        ->and($user->can('closure.dispatch'))->toBeFalse()
        ->and($user->can('closure.manage'))->toBeFalse()
        ->and($user->can('closure.manager'))->toBeFalse();
});

it('allows a closure manager on every closure gate', function () {
    $user = closureGateUser(['closure_manager' => true]);

    expect($user->can('closure.dispatch'))->toBeTrue()
        ->and($user->can('closure.work'))->toBeTrue()
        ->and($user->can('closure.manage'))->toBeTrue()
        ->and($user->can('closure.view'))->toBeTrue()
        ->and($user->can('closure.manager'))->toBeTrue();
});

it('denies all closure gates without a closure, admin, or super administrator flag', function () {
    $user = closureGateUser();

    foreach (['closure.dispatch', 'closure.work', 'closure.manage', 'closure.view', 'closure.manager'] as $ability) {
        expect($user->can($ability))->toBeFalse();
    }
});
