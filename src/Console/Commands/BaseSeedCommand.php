<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\search;

use Misaf\VendraTenant\Models\Tenant;

abstract class BaseSeedCommand extends Command implements PromptsForMissingInput
{
    private const int TENANT_SEARCH_LIMIT = 10;

    protected const string MODULE_NAME = '';

    public function handle(): int
    {
        if (app()->isDownForMaintenance()) {
            $this->warn('Application is in maintenance mode. Command aborted.');

            return self::FAILURE;
        }

        $tenantInput = (string) $this->argument('tenant');
        $tenant = $this->resolveTenant($tenantInput);

        if ( ! $tenant) {
            $this->error(sprintf('Tenant [%s] was not found.', $tenantInput));

            return self::FAILURE;
        }

        $seederClasses = $this->resolveSeederClasses($this->seederArguments());

        if (null === $seederClasses) {
            $this->error(sprintf(
                'Invalid seeder selection. Available seeders: all, %s.',
                implode(', ', array_keys($this->seeders())),
            ));

            return self::FAILURE;
        }

        $tenant->makeCurrent();

        foreach ($seederClasses as $seederClass) {
            $exitCode = $this->call('db:seed', [
                '--module' => static::MODULE_NAME,
                '--class'  => $seederClass,
            ]);

            if (self::SUCCESS !== $exitCode) {
                $this->error(sprintf(
                    'Seeder [%s] failed with exit code [%d].',
                    $seederClass,
                    $exitCode,
                ));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, callable>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'tenant' => fn() => search(
                label: 'Which tenant should receive seed data?',
                placeholder: 'Search tenant slug',
                options: fn(string $value): array => $this->tenantSearchOptions($value),
                hint: 'Seeders run only for the selected tenant.',
                scroll: self::TENANT_SEARCH_LIMIT,
            ),
            'seeders' => fn() => $this->promptForSeeders(),
        ];
    }

    /**
     * @return array<string, class-string>
     */
    abstract protected function seeders(): array;

    /**
     * @return list<string>
     */
    private function seederArguments(): array
    {
        return array_values($this->argument('seeders'));
    }

    private function resolveTenant(string $tenant): ?Tenant
    {
        return Tenant::query()
            ->whereKey($tenant)
            ->orWhere('slug', $tenant)
            ->first();
    }

    /**
     * @return list<string>
     */
    private function promptForSeeders(): array
    {
        if (confirm(label: 'Run all seeders?', default: true)) {
            return ['all'];
        }

        return array_values(array_filter(multiselect(
            label: 'Which seeders should run?',
            options: $this->individualSeederOptions(),
            required: true,
            hint: 'Choose one or more seeders.',
        ), is_string(...)));
    }

    /**
     * @param list<string> $seeders
     *
     * @return list<class-string>|null
     */
    private function resolveSeederClasses(array $seeders): ?array
    {
        if ([] === $seeders) {
            return null;
        }

        if (in_array('all', $seeders, true)) {
            return array_values($this->seeders());
        }

        $classes = [];

        foreach ($seeders as $seeder) {
            if ( ! array_key_exists($seeder, $this->seeders())) {
                return null;
            }

            $classes[] = $this->seeders()[$seeder];
        }

        return $classes;
    }

    /**
     * @return array<string, string>
     */
    private function individualSeederOptions(): array
    {
        return array_combine(array_keys($this->seeders()), array_keys($this->seeders()));
    }

    /**
     * @return array<int, string>
     */
    private function tenantSearchOptions(string $value): array
    {
        $search = mb_trim($value);

        $tenants = Tenant::query()
            ->select(['id', 'slug'])
            ->when('' !== $search, fn(Builder $query): Builder => $query->where('slug', 'like', "%{$search}%"))
            ->limit(self::TENANT_SEARCH_LIMIT)
            ->get();

        $options = [];

        foreach ($tenants as $tenant) {
            $options[$tenant->id] = $tenant->slug;
        }

        return $options;
    }
}
