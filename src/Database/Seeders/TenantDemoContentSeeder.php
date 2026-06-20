<?php

declare(strict_types=1);

namespace Misaf\VendraSupport\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Misaf\VendraTenant\Models\Tenant;
use ReflectionClass;
use UnexpectedValueException;

abstract class TenantDemoContentSeeder extends Seeder
{
    protected const string FIXTURE_FILE = 'demo-content.json';

    final public function run(): void
    {
        $tenant = Tenant::current();

        if ( ! $tenant instanceof Tenant) {
            return;
        }

        if (app()->isProduction()) {
            $decodedRecords = json_decode(
                File::get($this->fixturePath()),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );

            if ( ! is_array($decodedRecords) || ! array_is_list($decodedRecords)) {
                throw new UnexpectedValueException('Fixture file must contain a JSON array.');
            }

            foreach ($decodedRecords as $decodedRecord) {
                if ( ! is_array($decodedRecord) || array_is_list($decodedRecord)) {
                    throw new UnexpectedValueException('Each fixture record must be a JSON object.');
                }

                /** @var array<string, mixed> $decodedRecord */
                $this->seedFixtureRecord($tenant, $decodedRecord);
            }

            return;
        }

        $this->seedFactoryRecords($tenant);
    }

    abstract protected function seedFactoryRecords(Tenant $tenant): void;

    /**
     * @param array<string, mixed> $record
     */
    abstract protected function seedFixtureRecord(Tenant $tenant, array $record): void;

    private function fixturePath(): string
    {
        $fileName = (new ReflectionClass(static::class))->getFileName();

        if (false === $fileName) {
            throw new UnexpectedValueException(sprintf('Unable to resolve fixture path for %s.', static::class));
        }

        return dirname($fileName) . '/../fixtures/' . static::FIXTURE_FILE;
    }
}
