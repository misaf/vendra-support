<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Misaf\VendraSupport\Concerns\RequiresCurrentTenant;

abstract class PermissionPolicySeeder extends Seeder
{
    use RequiresCurrentTenant;

    /**
     * @return list<string>
     */
    abstract protected function policies(): array;

    protected function seedPermissionPolicies(int|string $tenantKey): void
    {
        /** @var class-string<Model> $permissionModel */
        $permissionModel = Config::string('permission.models.permission');

        $guardName = Config::string('auth.defaults.guard');

        foreach (array_unique($this->policies()) as $policy) {
            /** @var Model $permission */
            $permission = $permissionModel::query()->make();
            $permission->fill([
                'name'       => $policy,
                'guard_name' => $guardName,
            ]);
            $permission->setAttribute('tenant_id', $tenantKey);
            $permission->save();
        }
    }
}
