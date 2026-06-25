<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

abstract class SeedCommand extends Command implements PromptsForMissingInput
{
    public function handle(): int
    {
        if (app()->isDownForMaintenance()) {
            $this->warn('Application is in maintenance mode. Command aborted.');

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

        if ( ! $this->prepareForSeeding()) {
            return self::FAILURE;
        }

        foreach ($seederClasses as $seederClass) {
            $exitCode = $this->call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
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
            'seeders' => fn() => $this->promptForSeeders(),
        ];
    }

    /**
     * @return array<string, class-string>
     */
    abstract protected function seeders(): array;

    protected function prepareForSeeding(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    private function seederArguments(): array
    {
        return array_values($this->argument('seeders'));
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
}
