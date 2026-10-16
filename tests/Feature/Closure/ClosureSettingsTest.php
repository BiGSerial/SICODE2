<?php

use App\Models\Closure\ClosureSetting;
use App\Models\User;
use App\Services\Closure\ClosureSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('returns the documented defaults', function () {
    $settings = new ClosureSettings();

    expect($settings->allowFutureCompletion())->toBeFalse()
        ->and($settings->selfAssignEnabled())->toBeFalse()
        ->and($settings->selfAssignTypes())->toBe(['P', 'M']);
});

it('persists values with the actor and invalidates the short cache on set', function () {
    $actor    = User::factory()->create();
    $settings = new ClosureSettings();

    // Aquece o cache com os defaults antes de gravar.
    expect($settings->allowFutureCompletion())->toBeFalse()->and($settings->selfAssignEnabled())->toBeFalse();

    $settings->setAllowFutureCompletion(true, $actor);
    $settings->setSelfAssignEnabled(true, $actor);
    $settings->setSelfAssignTypes(['f', 'M', 'X', 'M'], $actor);

    expect($settings->allowFutureCompletion())->toBeTrue()
        ->and($settings->selfAssignEnabled())->toBeTrue()
        ->and($settings->selfAssignTypes())->toBe(['M', 'F'])
        ->and(ClosureSetting::query()->where('key', 'allow_future_completion')->value('updated_by'))->toBe($actor->id)
        ->and(ClosureSetting::query()->count())->toBe(3);

    $settings->setAllowFutureCompletion(false, $actor);

    expect((new ClosureSettings())->allowFutureCompletion())->toBeFalse()
        ->and(ClosureSetting::query()->count())->toBe(3);
});
