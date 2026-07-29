<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'permission_keys' => 'array',
            'is_super_admin' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function grantsFullAdminAccess(): bool
    {
        return $this->is_super_admin ?? false;
    }
}
