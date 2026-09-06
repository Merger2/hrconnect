<?php

use App\Models\Role;
use App\Support\RoleAccessPreviewService;

test('role access preview lists modules available to a role', function () {
    $role = Role::create([
        'name' => 'HR Preview_'.uniqid(),
        'slug' => 'hr_preview__'.uniqid().uniqid(),
        'permission_keys' => [
            'view_employees',
            'view_hr_checklists',
        ],
    ]);

    $preview = app(RoleAccessPreviewService::class)->forRole($role);
    $labels = collect($preview)->pluck('label');

    expect($labels)->toContain(__('Employees'))
        ->and($labels)->toContain(__('HR Checklists'));
});
