<?php

namespace App\Providers;

use App\Enums\Permission;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (Permission::cases() as $permission) {
            $value = $permission->value;

            Gate::define($value, function ($user) use ($value) {
                return $user->hasPermission($value)
                    || $user->allowsAdminPermission($value, legacyFallback: true);
            });

            $camelCase = lcfirst(str_replace('_', '', ucwords($value, '_')));

            if ($camelCase !== $value) {
                Gate::define($camelCase, function ($user) use ($value) {
                    return $user->hasPermission($value)
                        || $user->allowsAdminPermission($value, legacyFallback: true);
                });
            }
        }
    }
}
