<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Events\TenantProvisioned;
use Misaf\VendraSupport\Listeners\RunTenantSeeders;
use Misaf\VendraSupport\Support\NullTenantResolver;
use Misaf\VendraSupport\Support\TenantSeeders;

final class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/vendra-support.php', 'vendra-support');

        $this->app->singletonIf(TenantResolver::class, NullTenantResolver::class);
        $this->app->singleton(TenantSeeders::class);
    }

    public function boot(): void
    {
        Event::listen(TenantProvisioned::class, RunTenantSeeders::class);
    }
}
