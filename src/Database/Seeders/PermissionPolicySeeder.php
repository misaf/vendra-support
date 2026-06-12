<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Misaf\VendraTenant\Models\Tenant;
use RuntimeException;

abstract class PermissionPolicySeeder extends Seeder
{
    protected const string MODULE_NAME = '';

    public function run(): void
    {
        $tenant = Tenant::current();

        if ( ! $tenant) {
            throw new RuntimeException(sprintf(
                '%s permission policy seeding requires a current tenant.',
                static::MODULE_NAME,
            ));
        }

        $this->seedPermissionPolicies($tenant);
    }

    /**
     * @return list<string>
     */
    abstract protected function policies(): array;

    private function seedPermissionPolicies(Tenant $tenant): void
    {
        /** @var class-string<Model> $permissionModel */
        $permissionModel = Config::string('permission.models.permission');

        $guardName = Config::string('auth.defaults.guard', 'web');

        foreach (array_unique($this->policies()) as $policy) {
            $permissionModel::query()->firstOrCreate([
                'tenant_id'  => $tenant->getKey(),
                'name'       => $policy,
                'guard_name' => $guardName,
            ]);
        }

        $this->forgetCachedPermissions();
    }

    private function forgetCachedPermissions(): void
    {
        $key = Config::string('permission.cache.key', 'spatie.permission.cache');

        Cache::forget($key);
    }
}
