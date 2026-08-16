<?php

use App\Enums\PayrollStatus;

test('payroll status has valid transition map', function () {
    expect(PayrollStatus::DRAFT->allowedTransitions())->toBe([PayrollStatus::SUBMITTED])
        ->and(PayrollStatus::SUBMITTED->allowedTransitions())->toBe([PayrollStatus::VERIFIED, PayrollStatus::DRAFT])
        ->and(PayrollStatus::VERIFIED->allowedTransitions())->toBe([PayrollStatus::APPROVED, PayrollStatus::DRAFT])
        ->and(PayrollStatus::APPROVED->allowedTransitions())->toBe([PayrollStatus::PAID])
        ->and(PayrollStatus::PAID->allowedTransitions())->toBe([]);
});

test('payroll status canTransitionTo enforces legal transitions only', function () {
    expect(PayrollStatus::DRAFT->canTransitionTo(PayrollStatus::SUBMITTED))->toBeTrue()
        ->and(PayrollStatus::DRAFT->canTransitionTo(PayrollStatus::PAID))->toBeFalse()
        ->and(PayrollStatus::SUBMITTED->canTransitionTo(PayrollStatus::DRAFT))->toBeTrue()
        ->and(PayrollStatus::SUBMITTED->canTransitionTo(PayrollStatus::APPROVED))->toBeFalse()
        ->and(PayrollStatus::VERIFIED->canTransitionTo(PayrollStatus::APPROVED))->toBeTrue()
        ->and(PayrollStatus::VERIFIED->canTransitionTo(PayrollStatus::PAID))->toBeFalse()
        ->and(PayrollStatus::APPROVED->canTransitionTo(PayrollStatus::PAID))->toBeTrue()
        ->and(PayrollStatus::APPROVED->canTransitionTo(PayrollStatus::DRAFT))->toBeFalse()
        ->and(PayrollStatus::PAID->canTransitionTo(PayrollStatus::DRAFT))->toBeFalse();
});

test('payroll status transitions are asymmetric (no backwards from paid)', function () {
    foreach (PayrollStatus::cases() as $from) {
        foreach (PayrollStatus::cases() as $to) {
            if ($from === PayrollStatus::PAID) {
                expect($from->canTransitionTo($to))->toBeFalse();
            }
        }
    }
});

test('payroll status labels and colors are defined for every case', function () {
    foreach (PayrollStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty()
            ->and($status->color())->toBeString()->not->toBeEmpty();
    }
});

test('payroll status string values match database convention', function () {
    expect(PayrollStatus::DRAFT->value)->toBe('draft')
        ->and(PayrollStatus::SUBMITTED->value)->toBe('submitted')
        ->and(PayrollStatus::VERIFIED->value)->toBe('verified')
        ->and(PayrollStatus::APPROVED->value)->toBe('approved')
        ->and(PayrollStatus::PAID->value)->toBe('paid');
});
