<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Console\Commands;

use function Laravel\Prompts\search;

use Misaf\VendraSupport\Contracts\TenantResolver;

abstract class TenantSeedCommand extends SeedCommand
{
    private const int TENANT_SEARCH_LIMIT = 10;

    /**
     * @return array<string, callable>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'tenant' => fn() => search(
                label: 'Which tenant should receive seed data?',
                placeholder: 'Search tenant slug',
                options: fn(string $value): array => app(TenantResolver::class)->searchOptions($value, self::TENANT_SEARCH_LIMIT),
                hint: 'Seeders run only for the selected tenant.',
                scroll: self::TENANT_SEARCH_LIMIT,
            ),
            ...parent::promptForMissingArgumentsUsing(),
        ];
    }

    protected function prepareForSeeding(): bool
    {
        $tenantInput = $this->argument('tenant');
        $tenantResolver = app(TenantResolver::class);

        if ( ! is_int($tenantInput) && ! is_string($tenantInput)) {
            $this->error('Invalid tenant selection. Tenant must be an ID or slug.');

            return false;
        }

        if ( ! $tenantResolver->available()) {
            $this->error('Tenant module is not available.');

            return false;
        }

        if ( ! $tenantResolver->makeCurrent($tenantInput)) {
            $this->error(sprintf('Tenant [%s] was not found.', $tenantInput));

            return false;
        }

        return true;
    }
}
