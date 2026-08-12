<?php

use App\Models\Employee;
use App\Services\DocumentTemplateRenderService;

/**
 * Regression test class bug "kolom salah nama di buildVariables" (audit
 * scan-value-pattern 2026-08-12):
 *   - hire_date → join_date (Employee tak punya hire_date → tag selalu '-')
 *   - position->title → position?->name (Position kolomnya `name`, bukan
 *     `title`) + nullsafe utk employee tanpa posisi/divisi (hindari 500).
 */
test('buildVariables merender employee_join_date dari kolom join_date', function () {
    $employee = Employee::factory()->create(['join_date' => '2023-05-17']);

    $vars = app(DocumentTemplateRenderService::class)->buildVariables($employee);

    expect($vars['employee_join_date'])->toBe('17 May 2023');
});

test('buildVariables merender posisi & divisi dari kolom name', function () {
    $employee = Employee::factory()->create();

    $vars = app(DocumentTemplateRenderService::class)->buildVariables($employee);

    expect($vars['employee_position'])->toBe($employee->position?->name ?? '-');
    expect($vars['employee_division'])->toBe($employee->division?->name ?? '-');
});
