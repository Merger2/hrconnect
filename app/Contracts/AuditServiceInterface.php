<?php

namespace App\Contracts;

/**
 * Enterprise Audit Service Interface (Stub for Thesis)
 * Originally from enterprise edition - replaced with unlocked stub
 */
interface AuditServiceInterface
{
    public function record(string $action, ?string $description = null);

    public function getTrail(array $filters = []): array;
}
