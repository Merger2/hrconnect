<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\KnowledgeBase;
use App\Models\User;

/**
 * KnowledgeBasePolicy — authorization untuk KnowledgeBase RAG.
 *
 * Matrix:
 * - HR Manager → manage (CRUD + upload PDF + chat)
 * - Super Admin → all
 * - Employee → view + chat (read-only)
 *
 * Catatan: chat() adalah operasi viewAny yang dipakai semua employee.
 */
class KnowledgeBasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_KNOWLEDGEBASE->value);
    }

    public function view(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->can(Permission::VIEW_KNOWLEDGEBASE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_KNOWLEDGEBASE->value);
    }

    public function update(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->can(Permission::MANAGE_KNOWLEDGEBASE->value);
    }

    public function delete(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->can(Permission::MANAGE_KNOWLEDGEBASE->value);
    }

    /**
     * Chat AI — semua user yang punya VIEW_KNOWLEDGEBASE bisa tanya.
     * Default seeder: super-admin + hr-manager. Employee tidak punya by default
     * (sesuai SRS — bisa ditambah ke role employee jika kebijakan berubah).
     */
    public function chat(User $user): bool
    {
        return $user->can(Permission::VIEW_KNOWLEDGEBASE->value);
    }
}
