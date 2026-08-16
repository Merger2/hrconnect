<?php

use App\Models\Role;
use App\Models\User;

test('document workflow admin pages do not require the enterprise document feature', function () {

    $requestViewer = User::factory()->admin()->create();
    $templateManager = User::factory()->admin()->create();

    $requestRole = Role::create([
        'name' => 'Document Request Viewer_'.uniqid(),
        'slug' => 'document_request_viewer_'.uniqid(),
        'description' => 'Can access document requests without an enterprise document feature flag.',
        'permission_keys' => [
            'admin.document_requests.view',
        ],
    ]);
    $templateRole = Role::create([
        'name' => 'Document Template Manager_'.uniqid(),
        'slug' => 'document_template_manager_'.uniqid(),
        'description' => 'Can access document templates without an enterprise document feature flag.',
        // Q6 AUDIT fix: key lama 'admin.document_requests.templates' tidak resolve
        // ke gate route manapun — route admin.document-templates pakai gate
        // viewAdminDocumentRequests (keputusan K3). Dot-key legacy yang benar
        // untuk gate ini adalah 'admin.document_requests.view' (sama seperti
        // requestRole) — pemetaan via legacyAdminPermissionKey().
        'permission_keys' => [
            'admin.document_requests.view',
        ],
    ]);

    $requestViewer->roles()->sync([$requestRole->id]);
    $templateManager->roles()->sync([$templateRole->id]);

    $this->actingAs($requestViewer)
        ->get(route('admin.document-requests'))
        ->assertOk();

    $this->actingAs($templateManager)
        ->get(route('admin.document-templates'))
        ->assertOk();
});
