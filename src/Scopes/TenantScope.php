<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Support\TenantSchema;

class TenantScope implements Scope
{
    /**
     * @param Builder<Model> $builder
     * @param Model $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ( ! TenantSchema::hasTenantColumn($model->getTable())) {
            return;
        }

        if ($tenantId = $this->currentTenantId()) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
        }
    }

    private function currentTenantId(): ?int
    {
        if ( ! app()->bound(TenantResolver::class)) {
            return null;
        }

        return app(TenantResolver::class)->currentId();
    }
}
