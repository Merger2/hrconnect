<?php

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Regression — bug P1 (2026-08-12, ditemukan lewat audit register mandiri):
 * `/knowledge-base` melempar 500 "Attempt to read property \"value\" on string"
 * untuk SEMUA user selama ada dokumen di tabel knowledge_bases. Penyebab:
 * kolom `category` tidak di-cast ke enum KnowledgeBaseCategory, padahal blade
 * `knowledge-base/index.blade.php` dan resource API memakai `->value`.
 *
 * Fix: cast `'category' => KnowledgeBaseCategory::class` di model
 * (konsisten dengan `status`). Test ini guard agar halaman tetap render 200
 * dengan dokumen kategori string (nilai legacy `general` dsb).
 */
function kbUser(): User
{
    $user = User::factory()->create(['group' => 'user']);

    return $user;
}

it('renders /knowledge-base with documents whose category is a string', function () {
    KnowledgeBase::factory()->create([
        'title' => 'Kebijakan Cuti Tahunan',
        'category' => KnowledgeBaseCategory::GENERAL, // enum → disimpan string 'general'
        'status' => KnowledgeBaseStatus::READY,
        'source_document' => 'kebijakan-cuti.pdf',
        'content' => 'Setiap karyawan berhak atas cuti tahunan 12 hari.',
    ]);

    $response = actingAs(kbUser())->get('/knowledge-base');

    $response->assertOk();
    $response->assertSee('Kebijakan Cuti Tahunan');
});

it('exposes category enum value through the model attribute', function () {
    $kb = KnowledgeBase::factory()->create([
        'category' => 'general', // string legacy persis seperti data DB lama
        'status' => KnowledgeBaseStatus::READY,
        'source_document' => 'legacy.pdf',
    ]);

    /** @var KnowledgeBaseCategory $category */
    $category = $kb->category;

    expect($category)->toBeInstanceOf(KnowledgeBaseCategory::class)
        ->and($category->value)->toBe('general')
        ->and($category->label())->toBe('Umum');
});

it('still renders empty state when no documents exist', function () {
    KnowledgeBase::query()->delete();

    actingAs(kbUser())->get('/knowledge-base')->assertOk();
});
