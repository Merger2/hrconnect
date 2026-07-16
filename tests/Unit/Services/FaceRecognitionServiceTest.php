<?php

use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\Employee;
use App\Models\FaceDescriptor;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Dimension ─────────────────────────────────────────────────────────

test('getEmbeddingDimension returns 128', function () {
    $svc = new FaceRecognitionService;

    expect($svc->getEmbeddingDimension())->toBe(68);
});

// ─── No Face Registered ────────────────────────────────────────────────

test('throws FaceNotRegisteredException when employee has no face descriptor', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->make();

    expect(fn () => $svc->verifyFace($emp, array_fill(0, 128, 0.1)))
        ->toThrow(FaceNotRegisteredException::class, 'belum terdaftar');
});

// ─── Wrong Dimension ───────────────────────────────────────────────────

test('throws BusinessRuleException when vector is not 128D', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->make();

    // 64-dim
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 64, 0.1)))
        ->toThrow(BusinessRuleException::class, '128');

    // 256-dim
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 256, 0.1)))
        ->toThrow(BusinessRuleException::class, '128');

    // 0-dim
    expect(fn () => $svc->verifyFace($emp, []))
        ->toThrow(BusinessRuleException::class, '128');
});

// ─── Non-numeric Vector ────────────────────────────────────────────────

test('throws BusinessRuleException when vector contains non-numeric values', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->make();
    $emp->id = 1;

    // Ensure no descriptor exists
    FaceDescriptor::where('employee_id', $emp->id)->delete();

    $vector = array_fill(0, 128, 0.1);
    $vector[50] = 'not-a-number';

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class);
});

test('throws BusinessRuleException when vector contains null', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->make();
    $emp->id = 1;

    // Ensure no descriptor exists
    FaceDescriptor::where('employee_id', $emp->id)->delete();

    $vector = array_fill(0, 128, 0.1);
    $vector[50] = null;

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class);
});

// ─── Save Face Descriptor ──────────────────────────────────────────────

test('saveFaceDescriptor stores descriptor in face_descriptors table', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->create();

    $embedding = '['.implode(',', array_fill(0, 128, 0.1)).']';

    $svc->saveFaceDescriptor([
        'employee_id' => $emp->id,
        'embedding' => $embedding,
        'metadata' => ['source' => 'test'],
    ]);

    expect(FaceDescriptor::where('employee_id', $emp->id)->where('is_active', true)->exists())
        ->toBeTrue();
});

// ─── hasFaceEnrolled ───────────────────────────────────────────────────

test('hasFaceEnrolled returns true when descriptor exists', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->create();

    FaceDescriptor::create([
        'employee_id' => $emp->id,
        'embedding' => '['.implode(',', array_fill(0, 128, 0.1)).']',
        'is_active' => true,
    ]);

    expect($svc->hasFaceEnrolled($emp))->toBeTrue();
});

test('hasFaceEnrolled returns false when no descriptor', function () {
    $svc = new FaceRecognitionService;
    $emp = Employee::factory()->create();

    expect($svc->hasFaceEnrolled($emp))->toBeFalse();
});
