<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Scopes\TeamScope;
use Misaf\VendraSupport\Scopes\TenantScope;
use Misaf\VendraSupport\Support\TenantSchema;

trait BelongsToTenant
{
    /**
     * @return BelongsTo<Model, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo($this->tenantModelClass());
    }

    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());
        static::addGlobalScope(new TeamScope());

        static::creating(function (Model $model): void {
            if ( ! TenantSchema::hasTenantColumn($model->getTable())) {
                return;
            }

            if ($tenantId = static::currentTenantId()) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    private static function currentTenantId(): ?int
    {
        if ( ! app()->bound(TenantResolver::class)) {
            return null;
        }

        return app(TenantResolver::class)->currentId();
    }

    /**
     * @return class-string<Model>
     */
    private function tenantModelClass(): string
    {
        if ( ! app()->bound(TenantResolver::class)) {
            return Model::class;
        }

        return app(TenantResolver::class)->modelClass();
    }
}
