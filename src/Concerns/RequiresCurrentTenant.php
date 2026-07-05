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
                defined('static::MODULE_NAME') ? static::MODULE_NAME : static::class,
            ));
        }

        return $tenant;
    }
}
