<?php

use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\Employee;
use App\Services\FaceRecognitionService;

// ─── Dimension ─────────────────────────────────────────────────────────

test('getEmbeddingDimension returns 128', function () {
    $svc = new FaceRecognitionService;

    expect($svc->getEmbeddingDimension())->toBe(128);
});

// ─── No Face Registered ────────────────────────────────────────────────

test('throws FaceNotRegisteredException when employee has no face_embedding', function () {
    $svc = new FaceRecognitionService;
    $emp = new Employee;

    expect(fn () => $svc->verifyFace($emp, array_fill(0, 128, 0.1)))
        ->toThrow(FaceNotRegisteredException::class, 'Wajah karyawan ini belum terdaftar');
});

test('throws FaceNotRegisteredException when face_embedding is empty string', function () {
    $svc = new FaceRecognitionService;
    $emp = new Employee;
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');
    $prop->setValue($emp, ['face_embedding' => '']);

    expect(fn () => $svc->verifyFace($emp, array_fill(0, 128, 0.1)))
        ->toThrow(FaceNotRegisteredException::class);
});

// ─── Wrong Dimension ───────────────────────────────────────────────────

test('throws BusinessRuleException when vector is not 128D', function () {
    $svc = new FaceRecognitionService;
    $emp = new Employee;
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');
    $prop->setValue($emp, ['face_embedding' => '[0.1, 0.2]']);

    // 64-dim
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 64, 0.1)))
        ->toThrow(BusinessRuleException::class, 'harus 128D');

    // 256-dim
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 256, 0.1)))
        ->toThrow(BusinessRuleException::class, 'harus 128D');

    // 0-dim
    expect(fn () => $svc->verifyFace($emp, []))
        ->toThrow(BusinessRuleException::class, 'harus 128D');
});

// ─── Non-numeric Vector ────────────────────────────────────────────────

test('throws BusinessRuleException when vector contains non-numeric values', function () {
    $svc = new FaceRecognitionService;
    $emp = new Employee;
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');
    $prop->setValue($emp, ['face_embedding' => '[0.1, 0.2]']);

    $vector = array_fill(0, 128, 0.1);
    $vector[50] = 'not-a-number';

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class, 'tidak valid');
});

test('throws BusinessRuleException when vector contains null', function () {
    $svc = new FaceRecognitionService;
    $emp = new Employee;
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');
    $prop->setValue($emp, ['face_embedding' => '[0.1, 0.2]']);

    $vector = array_fill(0, 128, 0.1);
    $vector[50] = null;

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class, 'tidak valid');
});
