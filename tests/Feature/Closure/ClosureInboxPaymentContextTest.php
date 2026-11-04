<?php

use App\Services\Closure\ClosureInboxPaymentContext;

it('returns nothing for an empty list of notes', function () {
    expect(app(ClosureInboxPaymentContext::class)->forNotes([]))->toBe([]);
});

it('ignores notes that are not in the payment queue', function () {
    expect(app(ClosureInboxPaymentContext::class)->forNotes([999999999]))->toBe([]);
});
