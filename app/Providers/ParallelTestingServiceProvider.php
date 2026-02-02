<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\ServiceProvider;

class ParallelTestingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        /**
         * One-time per parallel worker.
         * Put CTS setup here so MockCtsDatabase::create() cannot race other workers.
         */
        ParallelTesting::setUpProcess(function (int $token): void {
            $this->useCtsDatabaseForToken($token);

            // This is the important part: CTS schema reset once per worker DB
            Artisan::call('cts:migrate:fresh', ['--no-interaction' => true]);
        });

        /**
         * Normal Laravel test DB setup (default connection).
         */
        ParallelTesting::setUpTestDatabase(function (string $database, string $token): void {
            Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]);
            Artisan::call('db:seed', ['--no-interaction' => true]);
        });
    }

    private function useCtsDatabaseForToken(int $token): void
    {
        // Base DB name from config for CTS connection, e.g. "cts_test"
        $base = (string) Config::get('database.connections.cts.database');

        // Make it unique per worker, e.g. cts_test_1, cts_test_2...
        $dbName = "{$base}_{$token}";

        // Apply runtime config
        Config::set('database.connections.cts.database', $dbName);

        // Ensure Laravel actually reconnects using the new DB
        DB::purge('cts');

        // Ensure the database exists (MySQL/MariaDB)
        $this->createDatabaseIfMissing($dbName);

        DB::reconnect('cts');
    }

    private function createDatabaseIfMissing(string $dbName): void
    {
        $ctsConfig = Config::get('database.connections.cts');

        // Clone CTS config but without selecting a database
        $serverConfig = $ctsConfig;
        $serverConfig['database'] = null;

        Config::set('database.connections.cts_server', $serverConfig);

        DB::purge('cts_server');

        DB::connection('cts_server')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        DB::disconnect('cts_server');
    }
}
