<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TeamScope implements Scope
{
    /**
     * @param Builder<Model> $builder
     * @param Model $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->has('currentTenant')) {
            return;
        }

        $tenantId = auth()->user()?->tenant_id;

        if (null !== $tenantId) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
        }
    }
}
