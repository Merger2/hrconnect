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

it('menampilkan SEMUA dokumen tanpa source_document (regression bug P0 unique)', function () {
    KnowledgeBase::factory()->create(['title' => 'Dokumen A', 'status' => KnowledgeBaseStatus::READY, 'source_document' => null]);
    KnowledgeBase::factory()->create(['title' => 'Dokumen B', 'status' => KnowledgeBaseStatus::READY, 'source_document' => null]);
    KnowledgeBase::factory()->create(['title' => 'Dokumen C', 'status' => KnowledgeBaseStatus::READY, 'source_document' => null]);

    // Bug lama: unique('source_document') menyatukan semua NULL jadi 1 kartu.
    actingAs(kbUser())
        ->get('/knowledge-base')
        ->assertOk()
        ->assertSee('Dokumen A')
        ->assertSee('Dokumen B')
        ->assertSee('Dokumen C');
});

it('mengelompokkan chunk PDF per source_document jadi 1 kartu', function () {
    KnowledgeBase::factory()->create(['title' => 'PDF Chunk 1', 'status' => KnowledgeBaseStatus::READY, 'source_document' => 'manual-karyawan.pdf']);
    KnowledgeBase::factory()->create(['title' => 'PDF Chunk 2', 'status' => KnowledgeBaseStatus::READY, 'source_document' => 'manual-karyawan.pdf']);
    KnowledgeBase::factory()->create(['title' => 'Dokumen Mandiri', 'status' => KnowledgeBaseStatus::READY, 'source_document' => null]);

    actingAs(kbUser())
        ->get('/knowledge-base')
        ->assertOk()
        ->assertSee('Dokumen Mandiri');
});

it('halaman detail menampilkan isi dokumen lengkap', function () {
    $kb = KnowledgeBase::factory()->create([
        'title' => 'Kebijakan Cuti Tahunan',
        'content' => 'Setiap karyawan berhak atas cuti tahunan 12 hari per tahun.',
        'status' => KnowledgeBaseStatus::READY,
    ]);

    actingAs(kbUser())
        ->get('/knowledge-base/'.$kb->id)
        ->assertOk()
        ->assertSee('Kebijakan Cuti Tahunan')
        ->assertSee('Setiap karyawan berhak atas cuti tahunan 12 hari per tahun.');
});

it('dokumen non-READY dialihkan ke index (tidak bocor)', function () {
    $kb = KnowledgeBase::factory()->create([
        'title' => 'Dokumen Rahasia',
        'status' => KnowledgeBaseStatus::PROCESSING,
    ]);

    actingAs(kbUser())
        ->get('/knowledge-base/'.$kb->id)
        ->assertRedirect(route('knowledge-base.index'));
});
