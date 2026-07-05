<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Throwable;

final class TenantSchema
{
    public static function enabled(): bool
    {
        if ( ! app()->bound(TenantResolver::class)) {
            return false;
        }

        return app(TenantResolver::class)->available();
    }

    public static function addTenantColumn(Blueprint $table): void
    {
        if ( ! self::enabled()) {
            return;
        }

        $table->unsignedBigInteger('tenant_id');
    }

    public static function addTenantIndex(Blueprint $table): void
    {
        if ( ! self::enabled()) {
            return;
        }

        $table->index('tenant_id');
    }

    /**
     * @param string|list<string> $columns
     *
     * @return list<string>
     */
    public static function tenantIndex(string|array $columns): array
    {
        $columns = is_array($columns) ? array_values($columns) : [$columns];

        if ( ! self::enabled()) {
            return $columns;
        }

        return array_values(array_unique(['tenant_id', ...$columns]));
    }

    public static function hasTenantColumn(string $table): bool
    {
        try {
            return Schema::hasColumn($table, 'tenant_id');
        } catch (Throwable) {
            return false;
        }
    }
}
