<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Concerns;

use Illuminate\Database\Eloquent\Model;
use Misaf\VendraSupport\Contracts\TenantResolver;
use RuntimeException;

trait RequiresCurrentTenant
{
    protected function currentTenant(): Model
    {
        $tenant = app(TenantResolver::class)->current();

        if ( ! $tenant instanceof Model) {
            throw new RuntimeException(sprintf(
                '%s seeding requires a current tenant.',
                $this->tenantModuleName(),
            ));
        }

        return $tenant;
    }

    private function tenantModuleName(): string
    {
        $constant = static::class . '::MODULE_NAME';

        if ( ! defined($constant)) {
            return static::class;
        }

        $moduleName = constant($constant);

        if ( ! is_string($moduleName)) {
            return static::class;
        }

        return $moduleName;
    }
}
