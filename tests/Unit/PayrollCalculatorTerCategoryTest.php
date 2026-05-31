<?php

use App\Enums\FamilyRelationship;
use App\Enums\MaritalStatus;
use App\Enums\TerCategory;

/**
 * fix verification — getTERCategory() count CHILD only, bukan semua relasi.
 *
 * TerCategory mapping (resolveFromStatus):
 * - SINGLE/DIVORCED/WIDOWED + (0|1 dependent) = A (TK/0, TK/1)
 * - SINGLE/DIVORCED/WIDOWED + (2|3 dependent) = B (TK/2, TK/3)
 * - MARRIED + (0|1 dependent) = B (K/0, K/1)
 * - MARRIED + (2|3 dependent) = C (K/2, K/3)
 *
 * Bug yang difix: dependents count sebelumnya termasuk parent + spouse + sibling,
 * sekarang hanya CHILD (anak biologis/adopsi).
 */
test('TerCategory MARRIED dengan 0 anak masuk kategori B', function () {
    expect(TerCategory::resolveFromStatus(MaritalStatus::MARRIED, 0))
        ->toBe(TerCategory::B);
});

test('TerCategory MARRIED dengan 2 anak masuk kategori C', function () {
    expect(TerCategory::resolveFromStatus(MaritalStatus::MARRIED, 2))
        ->toBe(TerCategory::C);
});

test('TerCategory SINGLE dengan 0 anak masuk kategori A', function () {
    expect(TerCategory::resolveFromStatus(MaritalStatus::SINGLE, 0))
        ->toBe(TerCategory::A);
});

test('TerCategory dependent count di-cap pada 3', function () {
    // Bahkan kalau dependents = 5, tetap masuk kategori C (cap di 3)
    expect(TerCategory::resolveFromStatus(MaritalStatus::MARRIED, 5))
        ->toBe(TerCategory::C);
});

test('FamilyRelationship enum punya case CHILD untuk filter', function () {
    expect(FamilyRelationship::CHILD)->toBeInstanceOf(FamilyRelationship::class);
});
